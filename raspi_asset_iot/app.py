#!/usr/bin/env python3
"""Asset scanner for Raspberry Pi 5 and existing Laravel API.

Two-position SPDT switch:
  GPIO17 to GND -> CHECK; GPIO27 to GND -> REGISTER
QR or RFID may arrive in any order during registration.
"""
import argparse
import logging
import queue
import signal
import sqlite3
import threading
import time
from pathlib import Path

from api import ApiClient, FrameUploader, RfidReadPublisher, HeartbeatPublisher
from config import Config
from core import Outbox, RegistrationPair, ScanEpoch, ScanReading, Worker, normalize_qr

LOG = logging.getLogger('asset-iot')


def diagnose(config):
    print('Pi Asset IoT - DIAGNOSTIK (tanpa mengaktifkan GPIO)')
    print('API:', config.api_base_url or '(belum diisi)')
    print('Token API:', 'TERISI' if config.api_token and not config.api_token.startswith('GANTI_') else 'BELUM DIISI')
    print('SQLite:', config.db_path)
    print('LCD:', 'aktif' if config.lcd_enabled else 'nonaktif', hex(config.lcd_address))
    print('Buzzer:', 'aktif' if config.buzzer_enabled else 'nonaktif')
    print('Streaming kamera:', 'aktif' if config.camera_enabled and config.camera_stream_enabled else 'nonaktif')
    print('LED:', config.led_type, 'aktif' if config.led_enabled else 'nonaktif')
    print('GPIO switch:',config.switch_check_gpio, config.switch_register_gpio)
    for path in (f'/dev/spidev0.{config.rfid_spi_device}', '/dev/i2c-1', f'/dev/video{config.camera_index}'):
        print(path, 'ADA' if Path(path).exists() else 'TIDAK ADA')
    for module in ('cv2', 'pyzbar.pyzbar', 'spidev', 'gpiozero', 'lgpio', 'smbus', 'requests', 'dotenv'):
        try:
            __import__(module)
            print('python', module, 'OK')
        except Exception as exc:
            print('python', module, 'BELUM SIAP:', exc)


class Sender(Worker):
    def __init__(self, outbox, api, events, stop, retention_days=30):
        super().__init__(daemon=True, name='sender')
        self.outbox, self.api, self.events, self.stop = outbox, api, events, stop
        self.retention_days = retention_days

    def run(self):
        next_cleanup = 0
        try:
            while not self.stop.is_set():
                self.touch()
                try:
                    if time.monotonic() >= next_cleanup:
                        next_cleanup = time.monotonic() + 300
                        self.outbox.prune_success(self.retention_days)
                    event = self.outbox.get_due()
                    if not event:
                        self.stop.wait(0.6)
                        continue
                    result = self.api.process(event)
                    self.outbox.finish(event['id'], result, event['attempts'])
                except sqlite3.Error:
                    LOG.exception('Gagal membaca/menyimpan status antrean')
                    self.stop.wait(1)
                    continue
                while not self.stop.is_set():
                    try:
                        self.events.put(('result', (event, result)), timeout=0.2)
                        break
                    except queue.Full:
                        continue
        finally:
            self.api.close()


class Controller:
    def __init__(self, config, stop):
        from gpiozero.pins.lgpio import LGPIOFactory
        from hardware import Indicator, ModeSwitch, RfidWorker, WebcamWorker

        self.config = config
        self.stop = stop
        self.events = queue.Queue(maxsize=150)
        self.scan_epoch = ScanEpoch()
        self._closed = False
        self.outbox = Outbox(config.db_path, config.pending_limit)
        self.pin_factory = LGPIOFactory()
        self.indicator = self.switch = None
        self.sender = self.cam = self.reader = self.frames = self.uid_publisher = self.heartbeat = None
        try:
            self.indicator = Indicator(self.pin_factory, config)
            self.switch = ModeSwitch(self.pin_factory, config.switch_check_gpio, config.switch_register_gpio)
        except Exception:
            self.close()
            raise
        self.pair = RegistrationPair(config.pair_timeout_seconds)
        self.mode = 'invalid'
        self.transient_until = 0
        self.registration_retry_at = 0
        self.latest_event_times = {}  # local-only; bound in cleanup
        self.event_epochs = {}
        api = ApiClient(config.api_base_url, config.api_token, config.http_timeout_seconds)
        self.sender = Sender(self.outbox, api, self.events, self.stop, config.success_retention_days)
        if config.rfid_enabled:
            uid_api = ApiClient(config.api_base_url, config.api_token, config.http_timeout_seconds)
            self.uid_publisher = RfidReadPublisher(uid_api, self.stop)
        if config.camera_enabled:
            if config.camera_stream_enabled:
                frame_api = ApiClient(config.api_base_url, config.api_token, config.http_timeout_seconds)
                self.frames = FrameUploader(frame_api, self.stop)
            self.cam = WebcamWorker(
                self.events, self.stop, config.camera_index, self.frames.submit if self.frames else None,
                stream_fps=config.camera_stream_fps,
                scan_epoch=self.scan_epoch.snapshot, repeat_seconds=config.scan_repeat_seconds,
            )
        self.reader = RfidWorker(self.events, self.stop, self.pin_factory, config.rfid_uid_format, config.rfid_rst_gpio, config.rfid_spi_device,
                                 scan_epoch=self.scan_epoch.snapshot, repeat_seconds=config.scan_repeat_seconds) if config.rfid_enabled else None
        heartbeat_api = ApiClient(config.api_base_url, config.api_token, config.http_timeout_seconds)
        self.heartbeat = HeartbeatPublisher(heartbeat_api, self.stop, self.health_snapshot)

    def health_snapshot(self):
        def state(worker):
            if worker is None:
                return 'disabled'
            activity = worker.last_activity
            return 'ready' if worker.is_alive() and activity is not None and 0 <= time.monotonic() - activity < 5 else 'error'

        try:
            pending = self.outbox.pending_count()
        except sqlite3.Error:
            pending = None
        return {'mode': self.mode, 'camera': state(self.cam), 'rfid': state(self.reader), 'pending_count': pending}

    def show(self, line1, line2, color=None, seconds=2.2, beeps=0):
        now = time.monotonic()
        self.transient_until = now + seconds
        self.indicator.color(color, seconds=seconds)
        if beeps:
            self.indicator.beep(beeps)
        self.indicator.display(line1, line2)

    def base_lines(self):
        if self.pair.payload:
            return 'DAFTAR TERTUNDA', 'MENUNGGU SIMPAN'
        try:
            pending = self.outbox.pending_count()
        except sqlite3.Error:
            return 'SQLITE ERROR', 'PERIKSA STORAGE'
        if self.mode == 'invalid':
            return 'SWITCH ERROR', 'PERIKSA KABEL'
        if self.mode == 'check':
            if pending:
                return 'MODE CEK ASET', f'ANTREAN: {pending}'
            return 'MODE CEK ASET', 'SCAN QR / RFID'
        if self.pair.asset_id:
            return 'QR OK', 'SCAN RFID'
        if self.pair.uid:
            return 'RFID OK', 'SCAN QR CODE'
        if pending:
            return 'MODE DAFTAR', f'ANTREAN: {pending}'
        return 'MODE DAFTAR', 'SCAN QR + RFID'

    def update_mode(self):
        mode = self.switch.read()
        if mode != self.mode:
            LOG.info('Switch %s -> %s', self.mode, mode)
            self.mode = mode
            self.scan_epoch.advance()
            if not self.pair.payload:
                self.pair.reset()  # completed scans survive a temporary storage failure
            self.indicator.color(None)
            self.transient_until = 0
            self.indicator.display(*self.base_lines())

    def on_scan(self, source, reading):
        if not isinstance(reading, ScanReading):
            LOG.warning('Scan tanpa metadata diabaikan')
            return
        if reading.epoch != self.scan_epoch.snapshot() or not 0 <= time.monotonic() - reading.captured_at <= self.config.scan_max_age_seconds:
            LOG.info('Scan lama/pergantian mode diabaikan')
            return
        raw = reading.value
        if source == 'rfid' and self.uid_publisher:
            self.uid_publisher.submit(raw)
        if self.mode == 'invalid':
            self.show('PILIH MODE', 'CEK / DAFTAR', 'failure', 1.8, beeps=2)
            return
        try:
            val = normalize_qr(raw) if source == 'qr' else raw
        except ValueError:
            self.show('SCAN QR GAGAL', 'KODE TIDAK VALID', 'failure', beeps=2)
            return
        try:
            if self.mode == 'check':
                payload = {'asset_id': val} if source == 'qr' else {'rfid_uid': val}
                event_id, new = self.outbox.enqueue('check', payload)
                self.latest_event_times[event_id] = time.monotonic()
                self.event_epochs[event_id] = self.scan_epoch.snapshot()
                self.show('MEMERIKSA ASET', val[:16] if new else 'SUDAH DIANTRI', seconds=1.8)
            else:
                if self.pair.payload:
                    self.show('DAFTAR TERTUNDA', 'MENUNGGU SIMPAN', seconds=1.8)
                    return
                pair = self.pair.add(source, val)
                if not pair:
                    self.show('QR DITERIMA' if source == 'qr' else 'RFID DITERIMA',
                              'SCAN RFID' if source == 'qr' else 'SCAN QR CODE', seconds=1.7)
                    return
                self.save_registration()
                return
            LOG.info('Masuk antrean %s id=%s', self.mode, event_id)
        except (RuntimeError, OSError, sqlite3.Error) as exc:
            LOG.exception('Tidak dapat menyimpan scan ke SQLite')
            self.show('ANTRIAN GAGAL', str(exc)[:16], 'failure', seconds=3, beeps=2)

    def save_registration(self, notify_failure=True):
        pair = self.pair.payload
        if pair is None:
            return
        try:
            event_id, new = self.outbox.enqueue('register', pair)
        except (RuntimeError, OSError, sqlite3.Error):
            self.registration_retry_at = time.monotonic() + 2
            LOG.exception('Pasangan dipertahankan; SQLite belum dapat menyimpan')
            if notify_failure:
                self.show('SIMPAN GAGAL', 'PASANGAN DITAHAN', 'failure', seconds=3, beeps=2)
            return
        self.pair.reset()  # only acknowledge after the SQLite transaction commits
        self.registration_retry_at = 0
        self.latest_event_times[event_id] = time.monotonic()
        self.event_epochs[event_id] = self.scan_epoch.snapshot()
        self.show('DAFTAR RFID', pair['asset_id'][:16] if new else 'SUDAH DIANTRI', seconds=1.8)

    def on_result(self, event, result):
        LOG.info('Hasil event=%s jenis=%s status=%s pesan=%s', event['id'], event['kind'], result.state, result.detail)
        # Avoid a beep for delayed/offline reconnection; no unexpected sounds overnight.
        elapsed = time.monotonic() - self.latest_event_times.get(event['id'], -99999)
        same_mode = self.event_epochs.get(event['id'], self.scan_epoch.snapshot()) == self.scan_epoch.snapshot()
        fresh = same_mode and 0 <= elapsed < 40
        if result.state == 'retry':
            if fresh:
                self.show('GAGAL POST DATA', 'SIMPAN ANTREAN', 'offline', seconds=3, beeps=2)
        elif fresh:
            if result.state == 'confirmed':
                self.show(result.title, result.detail[:16], 'success', seconds=3, beeps=1)
            else:
                self.show(result.title, result.detail[:16], 'failure', seconds=3, beeps=2)
        elif same_mode and result.state == 'confirmed' and self.outbox.pending_count() == 0:
            self.show('ANTREAN TERKIRIM', 'SINKRON SELESAI', 'success', seconds=2)
        # Delayed replays otherwise only affect the pending count, not an operator's LCD.
        self.latest_event_times.pop(event['id'], None)
        self.event_epochs.pop(event['id'], None)

    def workers(self):
        return tuple(w for w in (self.reader, self.cam, self.sender, self.frames, self.uid_publisher, self.heartbeat) if w is not None)

    def check_workers(self):
        now = time.monotonic()
        for worker in (*self.workers(), *self.indicator.workers):
            if not worker.is_alive():
                raise RuntimeError(f'Worker {worker.name} berhenti')
            if now - worker.progress_at > self.config.worker_stall_seconds:
                raise RuntimeError(f'Worker {worker.name} tidak merespons')

    def run(self):
        self.update_mode()
        self.sender.start()
        if self.uid_publisher:
            self.uid_publisher.start()
        if self.frames:
            self.frames.start()
        if self.cam:
            self.cam.start()
        if self.reader:
            self.reader.start()
        self.heartbeat.start()
        LOG.info('Service siap, koneksi internet tidak diperlukan untuk menerima scan')
        last_idle_draw = 0
        while not self.stop.is_set():
            self.check_workers()
            self.update_mode()
            if self.pair.payload and time.monotonic() >= self.registration_retry_at:
                self.save_registration(notify_failure=False)
            if self.mode == 'register' and self.pair.expired():
                self.pair.reset()
                self.show('WAKTU HABIS', 'ULANGI QR+RFID', 'failure', beeps=2)
            try:
                kind, value = self.events.get(timeout=0.1)
            except queue.Empty:
                kind = None
            self.update_mode()  # the switch may have moved while waiting for an event
            if kind in ('qr', 'rfid'):
                self.on_scan(kind, value)
            elif kind == 'result':
                self.on_result(*value)
            elif kind == 'scan_error':
                self.show('QR TIDAK TERBACA', 'COBA DEKATKAN', 'failure', beeps=2)
            elif kind == 'camera_error':
                self.show('KAMERA ERROR', 'CEK USB WEBCAM', 'failure', seconds=2, beeps=2)
            elif kind == 'rfid_error':
                self.show('RFID ERROR', 'CEK SPI RC522', 'failure', seconds=2, beeps=2)
            now = time.monotonic()
            if now >= self.transient_until and now - last_idle_draw > 1:
                self.indicator.display(*self.base_lines())
                last_idle_draw = now
            # Limit growth in memory for events received during long offline periods.
            if len(self.latest_event_times) > 2000:
                cutoff = now - 120
                self.latest_event_times = {k:v for k,v in self.latest_event_times.items() if v >= cutoff}
                self.event_epochs = {k:v for k,v in self.event_epochs.items() if k in self.latest_event_times}

    def close(self):
        if self._closed:
            return
        self._closed = True
        self.stop.set()
        deadline = time.monotonic() + self.config.shutdown_timeout_seconds
        indicators_done = True
        try:
            if self.indicator:
                indicators_done = self.indicator.close(timeout=min(2, self.config.shutdown_timeout_seconds))
        finally:
            try:
                for worker in self.workers():
                    if worker.ident is not None:
                        worker.join(max(0, deadline - time.monotonic()))
                    else:
                        api = getattr(worker, 'api', None)
                        if api:
                            api.close()
            finally:
                if self.switch:
                    self.switch.close()
                hardware_stopped = (self.reader is None or not self.reader.is_alive()) and (self.indicator is None or self.indicator.outputs_stopped)
                if hardware_stopped:
                    self.pin_factory.close()
                else:
                    LOG.error('GPIO factory tidak ditutup di bawah worker hardware yang masih aktif')
        alive = [worker.name for worker in self.workers() if worker.is_alive()]
        if alive or not indicators_done:
            raise RuntimeError(f'Shutdown worker melewati batas waktu: {alive or "indikator"}')


def main():
    parser = argparse.ArgumentParser(description='Raspberry Pi 5 asset QR+RFID scanner')
    parser.add_argument('--diagnose', action='store_true', help='Print readiness without opening GPIO')
    parser.add_argument('--queue-stats', action='store_true', help='Show SQLite queue counts without hardware')
    args = parser.parse_args()
    logging.basicConfig(level=logging.INFO, format='%(asctime)s %(levelname)s %(name)s %(message)s')
    conf = Config()
    if args.diagnose:
        diagnose(conf)
        return
    if args.queue_stats:
        print(Outbox(conf.db_path).stats())
        return
    conf.validate()
    stop = threading.Event()
    def stop_handler(signum, frame):
        stop.set()
    signal.signal(signal.SIGTERM, stop_handler)
    signal.signal(signal.SIGINT, stop_handler)
    controller = Controller(conf, stop)
    try:
        controller.run()
    finally:
        controller.close()


if __name__ == '__main__':
    main()
