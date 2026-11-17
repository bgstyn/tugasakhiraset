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
from core import Outbox, RegistrationPair, normalize_qr

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


class Sender(threading.Thread):
    def __init__(self, outbox, api, events, stop):
        super().__init__(daemon=True, name='sender')
        self.outbox, self.api, self.events, self.stop = outbox, api, events, stop

    def run(self):
        try:
            while not self.stop.is_set():
                try:
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
        self.led_until = 0
        self.latest_event_times = {}  # local-only; bound in cleanup
        api = ApiClient(config.api_base_url, config.api_token, config.http_timeout_seconds)
        self.sender = Sender(self.outbox, api, self.events, self.stop)
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
            )
        self.reader = RfidWorker(self.events, self.stop, self.pin_factory, config.rfid_uid_format, config.rfid_rst_gpio, config.rfid_spi_device) if config.rfid_enabled else None
        heartbeat_api = ApiClient(config.api_base_url, config.api_token, config.http_timeout_seconds)
        self.heartbeat = HeartbeatPublisher(heartbeat_api, self.stop, self.health_snapshot)

    def health_snapshot(self):
        now = time.monotonic()

        def state(worker):
            if worker is None:
                return 'disabled'
            activity = worker.last_activity
            return 'ready' if worker.is_alive() and activity is not None and 0 <= now - activity < 5 else 'error'

        try:
            pending = self.outbox.pending_count()
        except sqlite3.Error:
            pending = None
        return {'mode': self.mode, 'camera': state(self.cam), 'rfid': state(self.reader), 'pending_count': pending}

    def show(self, line1, line2, color=None, seconds=2.2, beeps=0):
        self.indicator.display(line1, line2)
        now = time.monotonic()
        self.transient_until = now + seconds
        self.indicator.color(color)
        self.led_until = now + seconds if color else 0
        if beeps:
            self.indicator.beep(beeps)

    def base_lines(self):
        pending = self.outbox.pending_count()
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
            self.pair.reset()  # discard unpaired scans on mode changes
            self.indicator.color(None)
            self.led_until = 0
            self.transient_until = 0
            if self.cam:
                self.cam.reset_detection.set()
            if self.reader:
                self.reader.reset_detection.set()
            self.indicator.display(*self.base_lines())

    def on_scan(self, source, raw):
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
                self.show('MEMERIKSA ASET', val[:16] if new else 'SUDAH DIANTRI', seconds=1.8)
            else:
                pair = self.pair.add(source, val)
                if not pair:
                    self.show('QR DITERIMA' if source == 'qr' else 'RFID DITERIMA',
                              'SCAN RFID' if source == 'qr' else 'SCAN QR CODE', seconds=1.7)
                    return
                event_id, new = self.outbox.enqueue('register', pair)
                self.latest_event_times[event_id] = time.monotonic()
                self.show('DAFTAR RFID', pair['asset_id'][:16] if new else 'SUDAH DIANTRI', seconds=1.8)
            LOG.info('Masuk antrean %s id=%s', self.mode, event_id)
        except (RuntimeError, OSError, sqlite3.Error) as exc:
            LOG.exception('Tidak dapat menyimpan scan ke SQLite')
            self.show('ANTRIAN GAGAL', str(exc)[:16], 'failure', seconds=3, beeps=2)

    def on_result(self, event, result):
        LOG.info('Hasil event=%s jenis=%s status=%s pesan=%s', event['id'], event['kind'], result.state, result.detail)
        # Avoid a beep for delayed/offline reconnection; no unexpected sounds overnight.
        elapsed = time.monotonic() - self.latest_event_times.get(event['id'], -99999)
        fresh = 0 <= elapsed < 40
        if result.state == 'retry':
            if fresh:
                self.show('GAGAL POST DATA', 'SIMPAN ANTREAN', 'offline', seconds=3, beeps=2)
        elif fresh:
            if result.state == 'confirmed':
                self.show(result.title, result.detail[:16], 'success', seconds=3, beeps=1)
            else:
                self.show(result.title, result.detail[:16], 'failure', seconds=3, beeps=2)
        elif result.state == 'confirmed' and self.outbox.pending_count() == 0:
            self.show('ANTREAN TERKIRIM', 'SINKRON SELESAI', 'success', seconds=2)
        # Delayed replays otherwise only affect the pending count, not an operator's LCD.
        self.latest_event_times.pop(event['id'], None)

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
            if not self.sender.is_alive():
                raise RuntimeError('Pengirim antrean berhenti')
            self.update_mode()
            if self.mode == 'register' and self.pair.expired():
                self.pair.reset()
                self.show('WAKTU HABIS', 'ULANGI QR+RFID', 'failure', beeps=2)
            try:
                kind, value = self.events.get(timeout=0.1)
            except queue.Empty:
                kind = None
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
            if self.led_until and now > self.led_until:
                self.indicator.color(None)
                self.led_until = 0
            if now >= self.transient_until and now - last_idle_draw > 1:
                self.indicator.display(*self.base_lines())
                last_idle_draw = now
            # Limit growth in memory for events received during long offline periods.
            if len(self.latest_event_times) > 2000:
                cutoff = now - 120
                self.latest_event_times = {k:v for k,v in self.latest_event_times.items() if v >= cutoff}

    def close(self):
        self.stop.set()
        try:
            if self.indicator:
                self.indicator.close()
        finally:
            try:
                if self.switch:
                    self.switch.close()
                for worker in (self.reader, self.cam, self.sender, self.frames, self.uid_publisher, self.heartbeat):
                    if worker and worker.ident is not None:
                        worker.join(timeout=1)
            finally:
                self.pin_factory.close()


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
