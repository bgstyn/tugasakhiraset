import queue
import tempfile
import threading
import time
import unittest
from unittest.mock import Mock, patch

import requests

import test_reliability as reliability
from api import ApiClient, RegistrationLookup
from core import Result, ScanReading


class StartupFeedbackTest(unittest.TestCase):
    controller = reliability.ReliabilityTest.controller
    scan = reliability.ReliabilityTest.scan

    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)

    def test_startup_checks_ready_hardware_then_arms_blue_standby(self):
        c = self.controller()
        c.indicator.lcd = object()
        c.health_snapshot = Mock(return_value={'camera': 'ready', 'rfid': 'ready'})
        with patch.object(c.stop, 'wait', return_value=False):
            c.startup_check()
        c.indicator.set_idle_color.assert_called_once_with('standby')
        c.show.assert_called_once_with('CEK KOMPONEN OK', 'SIAP SCAN ASET', 'success', seconds=2, beeps=1)
        # Sensor input captured before readiness cannot enqueue a transaction.
        c.on_scan('qr', ScanReading('STARTUP-SCAN', 0, time.monotonic()))
        self.assertEqual(c.outbox.pending_count(), 0)

    def test_failed_components_have_a_bounded_startup_and_keep_scanner_usable(self):
        c = self.controller()
        c.indicator.lcd = None
        c.health_snapshot = Mock(return_value={'camera': 'error', 'rfid': 'ready'})
        clock = [100.0]
        def wait(seconds):
            clock[0] += seconds
            return False
        with patch('app.time.monotonic', side_effect=lambda: clock[0]), patch.object(c.stop, 'wait', side_effect=wait):
            c.startup_check()
        self.assertLess(clock[0], 110)
        c.show.assert_called_once_with('KOMPONEN GAGAL', 'CAMERA,LCD', 'failure', seconds=2, beeps=2)
        self.scan(c, 'rfid', 'WORKING-TAG')
        self.assertEqual(c.outbox.pending_count(), 1)

    def test_stop_during_startup_does_not_enable_standby_or_accept_scans(self):
        c = self.controller()
        c.stop.set()
        c.startup_check()
        c.indicator.color.assert_not_called()
        c.indicator.set_idle_color.assert_not_called()
        c.show.assert_not_called()

    def test_partial_registration_beeps_and_requests_read_only_status(self):
        c = self.controller('register')
        c.registration_lookup = Mock()
        self.scan(c, 'rfid', 'AA11')
        self.assertEqual(c.show.call_args.kwargs['beeps'], 1)
        c.registration_lookup.submit.assert_called_once_with(c.lookup_context, 'AA11')
        self.assertEqual(c.outbox.pending_count(), 0)
        c.on_registration_status(c.lookup_context, Result('confirmed', 'RFID TERDAFTAR', 'ASSET-1'))
        c.show.assert_called_with('RFID TERDAFTAR', 'ASSET-1', 'success', seconds=3, beeps=0)
        self.assertEqual(c.base_lines(), ('RFID TERDAFTAR', 'SCAN QR CODE'))

    def test_lookup_cannot_overwrite_a_new_tag_a_completed_pair_or_changed_mode(self):
        for change in ('new_tag', 'complete', 'mode', 'timeout'):
            with self.subTest(change=change):
                c = self.controller('register')
                c.registration_lookup = Mock()
                self.scan(c, 'rfid', 'AA11')
                old = c.lookup_context
                if change == 'new_tag':
                    self.scan(c, 'rfid', 'BB22')
                elif change == 'complete':
                    self.scan(c, 'qr', 'ASSET-1')
                elif change == 'mode':
                    c.switch.read.return_value = 'check'
                    c.update_mode()
                else:
                    c.pair.updated = time.monotonic() - 100
                c.show.reset_mock()
                c.on_registration_status(old, Result('confirmed', 'RFID TERDAFTAR', 'OLD'))
                c.show.assert_not_called()

    def test_offline_lookup_never_labels_a_tag_as_unregistered_or_loses_pair(self):
        c = self.controller('register')
        c.registration_lookup = Mock()
        self.scan(c, 'rfid', 'AA11')
        c.on_registration_status(c.lookup_context, Result('retry', 'STATUS TAK DIKET.', 'CEK KONEKSI API'))
        c.show.assert_called_with('STATUS TAK DIKET.', 'CEK KONEKSI API', 'offline', seconds=3, beeps=2)
        self.assertEqual(c.pair.uid, 'AA11')
        self.scan(c, 'qr', 'ASSET-1')
        self.assertEqual(c.outbox.get_due()['payload'], {'asset_id': 'ASSET-1', 'rfid_uid': 'AA11'})


class LookupApiTest(unittest.TestCase):
    def setUp(self):
        self.api = ApiClient('https://example.test/api', 'token')
        self.addCleanup(self.api.close)

    def test_lookup_distinguishes_unknown_inactive_known_and_invalid_api(self):
        cases = [
            (404, {'success': False}, 'missing'),
            (200, {'success': True, 'data': {'asset_id': 'ASSET-1', 'rfid_uid': 'AA11', 'rfid_status': 'aktif'}}, 'confirmed'),
            (200, {'success': True, 'data': {'asset_id': 'ASSET-1', 'rfid_uid': 'AA11', 'rfid_status': 'nonaktif'}}, 'rejected'),
            (200, {'success': True, 'data': {'asset_id': 'ASSET-1', 'rfid_uid': 'OTHER'}}, 'retry'),
            (401, {'success': False}, 'retry'), (500, {'success': False}, 'retry'), (404, {}, 'retry'),
        ]
        for status, body, expected in cases:
            with self.subTest(status=status, body=body):
                response = Mock(status_code=status, json=Mock(return_value=body))
                with patch.object(self.api.session, 'get', return_value=response) as get:
                    self.assertEqual(self.api.lookup_rfid('AA11').state, expected)
                    get.assert_called_once_with('https://example.test/api/rfid/asset', params={'rfid_uid': 'AA11'}, timeout=2, allow_redirects=False)

    def test_lookup_timeout_or_html_is_unknown(self):
        with patch.object(self.api.session, 'get', side_effect=requests.Timeout):
            self.assertEqual(self.api.lookup_rfid('AA11').state, 'retry')
        with patch.object(self.api.session, 'get', return_value=Mock(json=Mock(side_effect=ValueError))):
            self.assertEqual(self.api.lookup_rfid('AA11').state, 'retry')

    def test_registration_result_names_existing_tag_and_conflicting_mapping(self):
        for status, body, expected in [
            (200, {'success': True, 'data': {'asset_id': 'ASSET-1', 'already_registered': True}}, ('confirmed', 'RFID TERDAFTAR')),
            (409, {'success': False, 'code': 'RFID_ALREADY_PAIRED'}, ('rejected', 'RFID TERDAFTAR')),
            (409, {'success': False, 'code': 'ASSET_ALREADY_PAIRED'}, ('rejected', 'ASET ADA RFID')),
        ]:
            with self.subTest(body=body), patch.object(self.api.session, 'post', return_value=Mock(status_code=status, json=Mock(return_value=body))):
                result = self.api.process({'id': 'event', 'kind': 'register', 'payload': {}})
                self.assertEqual((result.state, result.title), expected)

    def test_lookup_worker_only_keeps_latest_waiting_request_and_closes_its_session(self):
        stop, events = threading.Event(), queue.Queue()
        api = Mock()
        api.lookup_rfid.return_value = Result('missing', 'BELUM TERDAFTAR', 'SCAN QR CODE')
        worker = RegistrationLookup(api, stop, events)
        worker.submit('old', 'OLD')
        worker.submit('new', 'NEW')
        worker.start()
        try:
            kind, (context, result) = events.get(timeout=2)
            self.assertEqual((kind, context, result.state), ('registration_status', 'new', 'missing'))
        finally:
            stop.set()
            worker.join(2)
        self.assertFalse(worker.is_alive())
        api.lookup_rfid.assert_called_once_with('NEW')
        api.close.assert_called_once()
