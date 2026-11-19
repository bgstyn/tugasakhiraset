import queue
import sqlite3
import tempfile
import threading
import time
import unittest
from dataclasses import replace
from pathlib import Path
from types import SimpleNamespace
from unittest.mock import Mock, patch

from api import ApiClient
from app import Controller
from config import Config
from core import Outbox, RegistrationPair, Result, ScanEpoch, ScanReading, Worker
from hardware import RC522, RfidWorker
from test_indicator import FakeOutput


class ReliabilityTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)

    def controller(self, mode='check', limit=10000):
        c = Controller.__new__(Controller)
        c.config = Config(api_base_url='https://example.test/api', api_token='test-token')
        c.stop = threading.Event()
        c._closed = False
        c.events = queue.Queue()
        c.scan_epoch = ScanEpoch()
        c.mode = mode
        c.pair = RegistrationPair()
        c.registration_retry_at = 0
        c.latest_event_times, c.event_epochs = {}, {}
        c.outbox = Outbox(Path(self.temp.name) / 'queue.db', pending_limit=limit)
        c.indicator = Mock(workers=(), outputs_stopped=True)
        c.indicator.close.return_value = True
        c.pin_factory = Mock()
        c.switch = Mock(read=Mock(return_value=mode))
        c.reader = c.cam = c.sender = c.frames = c.uid_publisher = c.heartbeat = None
        c.registration_lookup = None
        c.lookup_context = c.tag_status = None
        c.show = Mock()
        return c

    def scan(self, c, source, value, epoch=None, age=0):
        reading = ScanReading(value, c.scan_epoch.snapshot() if epoch is None else epoch, time.monotonic() - age)
        c.on_scan(source, reading)

    def test_old_mode_scan_is_rejected_even_after_switching_back(self):
        c = self.controller()
        old = ScanReading('OLD-ASSET', 0, time.monotonic())
        c.switch.read.return_value = 'register'
        c.update_mode()
        c.on_scan('qr', old)
        self.assertIsNone(c.pair.asset_id)
        c.switch.read.return_value = 'check'
        c.update_mode()
        c.on_scan('qr', old)
        self.assertEqual(c.outbox.pending_count(), 0)
        self.scan(c, 'qr', 'FRESH-ASSET')
        self.assertEqual(c.outbox.get_due()['payload'], {'asset_id': 'FRESH-ASSET'})

    def test_stale_uid_is_not_sent_to_browser_or_added_to_outbox(self):
        c = self.controller()
        c.uid_publisher = Mock()
        self.scan(c, 'rfid', 'OLD-TAG', age=6)
        c.uid_publisher.submit.assert_not_called()
        self.assertEqual(c.outbox.pending_count(), 0)

    def test_health_snapshot_timestamps_cannot_race_a_worker_update(self):
        c = self.controller()
        clock = Mock(return_value=100)
        class UpdatingWorker:
            def is_alive(self):
                return True
            @property
            def last_activity(self):
                clock.return_value = 101
                return 101
        c.reader = UpdatingWorker()
        with patch('app.time.monotonic', clock):
            self.assertEqual(c.health_snapshot()['rfid'], 'ready')

    def test_full_outbox_retains_pair_across_mode_change_and_retries_without_rescan(self):
        c = self.controller('register', limit=1)
        old, _ = c.outbox.enqueue('check', {'asset_id': 'EXISTING'})
        self.scan(c, 'qr', 'NEW-ASSET')
        with self.assertLogs('asset-iot', level='ERROR'):
            self.scan(c, 'rfid', 'NEW-TAG')
        expected = {'asset_id': 'NEW-ASSET', 'rfid_uid': 'NEW-TAG'}
        self.assertEqual(c.pair.payload, expected)
        self.assertEqual(c.pair.add('rfid', 'WRONG-TAG'), expected)
        self.assertFalse(c.pair.expired(now=time.monotonic() + 999))
        c.switch.read.return_value = 'check'
        c.update_mode()
        self.assertEqual(c.pair.payload, expected)
        c.outbox.finish(old, Result('confirmed', 'OK', ''), 0)
        c.save_registration()
        self.assertIsNone(c.pair.payload)
        event = Outbox(c.outbox.path).get_due()
        self.assertEqual((event['kind'], event['payload']), ('register', expected))

    def test_database_error_keeps_pair_until_a_successful_commit(self):
        c = self.controller('register')
        self.scan(c, 'qr', 'ASSET-1')
        with patch.object(c.outbox, 'enqueue', side_effect=sqlite3.OperationalError('locked')):
            with self.assertLogs('asset-iot', level='ERROR'):
                self.scan(c, 'rfid', 'TAG-1')
        self.assertEqual(c.outbox.pending_count(), 0)
        self.assertIsNotNone(c.pair.payload)
        c.save_registration()
        self.assertEqual(c.outbox.pending_count(), 1)
        self.assertIsNone(c.pair.payload)

    def test_late_result_from_an_old_mode_does_not_overwrite_indicators(self):
        c = self.controller()
        c.latest_event_times['old'] = time.monotonic()
        c.event_epochs['old'] = 0
        c.scan_epoch.advance()
        c.on_result({'id': 'old', 'kind': 'check'}, Result('confirmed', 'OLD SUCCESS', ''))
        c.show.assert_not_called()
        self.assertEqual(c.latest_event_times, {})
        self.assertEqual(c.event_epochs, {})

    def test_only_old_successes_are_pruned_in_bounded_batches(self):
        q = Outbox(Path(self.temp.name) / 'retention.db')
        for index, state in enumerate(['success', 'success', 'rejected', 'pending']):
            with patch('core.time.time', return_value=100):
                event_id, _ = q.enqueue('check', {'asset_id': str(index)})
                if state != 'pending':
                    q.finish(event_id, Result('confirmed' if state == 'success' else 'rejected', '', ''), 0)
        recent, _ = q.enqueue('check', {'asset_id': 'RECENT'})
        q.finish(recent, Result('confirmed', '', ''), 0)
        self.assertEqual(q.prune_success(0), 0)
        self.assertEqual(q.prune_success(30, batch_size=1), 1)
        self.assertEqual(q.stats(), {'pending': 1, 'rejected': 1, 'success': 2})

    def test_any_dead_or_stalled_worker_is_detected(self):
        c = self.controller()
        c.heartbeat = Worker(name='heartbeat', daemon=True)
        with self.assertRaisesRegex(RuntimeError, 'heartbeat berhenti'):
            c.check_workers()
        release = threading.Event()
        c.heartbeat = Worker(target=release.wait, name='heartbeat', daemon=True)
        c.heartbeat.start()
        try:
            c.heartbeat.progress_at = time.monotonic() - 31
            with self.assertRaisesRegex(RuntimeError, 'heartbeat tidak merespons'):
                c.check_workers()
        finally:
            release.set()
            c.heartbeat.join(1)

    def test_shutdown_waits_for_reader_before_closing_gpio(self):
        c = self.controller()
        order = []
        c.reader = Worker(target=lambda: (c.stop.wait(), order.append('reader stopped')), daemon=True)
        c.pin_factory.close.side_effect = lambda: order.append('factory closed')
        c.reader.start()
        c.close()
        self.assertEqual(order, ['reader stopped', 'factory closed'])
        c.close()
        c.pin_factory.close.assert_called_once()

    def test_shutdown_does_not_close_gpio_under_a_hung_reader(self):
        c = self.controller()
        c.config = replace(c.config, shutdown_timeout_seconds=.05)
        release = threading.Event()
        c.reader = Worker(target=release.wait, name='hung-reader', daemon=True)
        c.reader.start()
        try:
            with self.assertLogs('asset-iot', level='ERROR'), self.assertRaisesRegex(RuntimeError, 'Shutdown worker'):
                c.close()
            c.pin_factory.close.assert_not_called()
        finally:
            release.set()
            c.reader.join(1)

    def test_inactive_tag_is_a_permanent_rejection_with_clear_lcd_title(self):
        api = ApiClient('https://example.test/api', 'token')
        self.addCleanup(api.close)
        response = Mock(status_code=409)
        response.json.return_value = {'success': False, 'code': 'RFID_INACTIVE', 'message': 'RFID tidak aktif.'}
        with patch.object(api.session, 'post', return_value=response):
            result = api.process({'id': 'event', 'kind': 'check', 'payload': {'rfid_uid': 'AA11'}})
        self.assertEqual((result.state, result.title), ('rejected', 'RFID NONAKTIF'))

    def test_invalid_timeouts_are_rejected_before_opening_gpio(self):
        config = Config(api_base_url='https://example.test/api', api_token='test-token')
        config.validate()
        for values in [dict(http_timeout_seconds=0), dict(pair_timeout_seconds=float('nan')), dict(scan_repeat_seconds=-1),
                       dict(worker_stall_seconds=2), dict(shutdown_timeout_seconds=2), dict(scan_max_age_seconds=0), dict(success_retention_days=-1),
                       dict(startup_check_seconds=0), dict(startup_check_seconds=float('inf'))]:
            with self.subTest(values=values), self.assertRaises(ValueError):
                replace(config, **values).validate()

    def test_full_registration_lifecycle_connects_workers_outbox_feedback_and_shutdown(self):
        stop = threading.Event()
        factory, switch, lcd = Mock(), Mock(), Mock()
        switch.read.return_value = 'register'
        clients = []
        def api_factory(*args):
            api = Mock(base_url='https://example.test/api', timeout=6)
            api.session.post.return_value = Mock(status_code=200)
            api.process.return_value = Result('confirmed', 'DAFTAR BERHASIL', 'ASSET-INTEGRATION')
            clients.append(api)
            return api
        class Sensor(Worker):
            def __init__(self, source, value, events, stopped, epoch):
                super().__init__(name=source, daemon=True)
                self.last_activity = None
                self.source, self.value, self.events, self.stopped, self.epoch = source, value, events, stopped, epoch
            def run(self):
                self.last_activity = time.monotonic()
                self.events.put((self.source, ScanReading(self.value, self.epoch(), self.last_activity)))
                while not self.stopped.is_set():
                    self.touch()
                    self.last_activity = time.monotonic()
                    self.stopped.wait(.02)
                self.last_activity = None
        def camera(events, stopped, *args, **kwargs):
            return Sensor('qr', 'ASSET-INTEGRATION', events, stopped, kwargs['scan_epoch'])
        def reader(events, stopped, *args, **kwargs):
            return Sensor('rfid', 'AABB0011', events, stopped, kwargs['scan_epoch'])
        modules = {'gpiozero': SimpleNamespace(DigitalOutputDevice=FakeOutput),
                   'gpiozero.pins': SimpleNamespace(), 'gpiozero.pins.lgpio': SimpleNamespace(LGPIOFactory=lambda: factory)}
        config = Config(api_base_url='https://example.test/api', api_token='token', db_path=str(Path(self.temp.name) / 'integration.db'),
                        led_enabled=True, buzzer_enabled=True, lcd_enabled=True)
        with patch.dict('sys.modules', modules), patch('hardware.ModeSwitch', return_value=switch), patch('hardware.Lcd1602', return_value=lcd), \
             patch('hardware.WebcamWorker', side_effect=camera), patch('hardware.RfidWorker', side_effect=reader), patch('app.ApiClient', side_effect=api_factory), \
             patch.object(Controller, 'startup_check'):
            c = Controller(config, stop)
            feedback_seen = threading.Event()
            def finish_after_feedback():
                deadline = time.monotonic() + 4
                while time.monotonic() < deadline and not stop.is_set():
                    if c.indicator.buzzer.transitions == [True, False] * 2 and not c.indicator.led['green'].level:
                        feedback_seen.set()
                        break
                    stop.wait(.01)
                stop.set()
            finisher = threading.Thread(target=finish_after_feedback, daemon=True)
            finisher.start()
            try:
                c.run()
            finally:
                stop.set()
                c.close()
                finisher.join(1)
        self.assertTrue(feedback_seen.is_set())
        self.assertEqual(c.outbox.stats(), {'success': 1})
        sent = clients[0].process.call_args.args[0]
        self.assertEqual(sent['payload'], {'asset_id': 'ASSET-INTEGRATION', 'rfid_uid': 'AABB0011'})
        self.assertTrue(all(not worker.is_alive() for worker in (*c.workers(), *c.indicator.workers)))
        self.assertTrue(all(device.closed and device.level for device in c.indicator.led.values()))
        self.assertFalse(c.indicator.buzzer.level)
        for api in clients:
            api.close.assert_called_once()
        factory.close.assert_called_once()


class ReaderHealthTest(unittest.TestCase):
    def test_missing_chip_is_rejected_and_resources_are_released(self):
        for version in (0, 255):
            spi, rst = Mock(), Mock()
            spi.xfer2.return_value = [0, version]
            with patch.dict('sys.modules', {'spidev': SimpleNamespace(SpiDev=lambda: spi), 'gpiozero': SimpleNamespace(DigitalOutputDevice=Mock(return_value=rst))}), patch('hardware.time.sleep'):
                with self.assertRaises(OSError):
                    RC522(None)
            spi.close.assert_called_once()
            rst.close.assert_called_once()

    def test_documented_chip_version_and_compatible_clone_are_supported(self):
        reader = RC522.__new__(RC522)
        for version in (0x91, 0x92, 0x88):
            reader._rd = Mock(return_value=version)
            reader.version = version
            self.assertEqual(reader.check_health(), version)
            reader._rd.assert_called_with(0x37)
        reader._rd.return_value = 0
        with self.assertRaises(OSError):
            reader.check_health()

    def test_scan_captures_mode_before_io_and_duplicate_cooldown_is_used(self):
        stop, epoch = threading.Event(), ScanEpoch()
        clock = Mock(return_value=0)
        samples = iter([(0, b'\xaa'), (.2, None), (1, None), (1.1, b'\xaa'), (1.2, None), (2, None), (2.1, b'\xaa'), (2.2, None), (3, None), (4.2, b'\xaa')])
        reader = Mock()
        def read():
            now, uid = next(samples)
            clock.return_value = now
            if now == 0:
                epoch.advance()  # switch moved during device I/O
            return uid
        reader.read_uid.side_effect = read
        results = []
        worker = RfidWorker(queue.Queue(), stop, None, 'hex', scan_epoch=epoch.snapshot, repeat_seconds=3)
        def emitted(event):
            results.append(event)
            if len(results) == 3:
                stop.set()
        # First old-epoch read, then a new-epoch read, then the allowed rescan.
        worker._emit = emitted
        stop.wait = Mock(return_value=False)
        with patch('hardware.RC522', return_value=reader), patch('hardware.time.monotonic', clock):
            worker.run()
        readings = [event[1] for event in results]
        self.assertEqual([reading.epoch for reading in readings], [0, 1, 1])
        self.assertTrue(all(event[0] == 'rfid' for event in results))
        self.assertEqual([reading.captured_at for reading in readings], [0, 1.1, 4.2])
