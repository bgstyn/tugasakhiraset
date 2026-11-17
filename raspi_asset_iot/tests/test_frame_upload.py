import queue
import sqlite3
import sys
import threading
import time
import unittest
from types import SimpleNamespace
from unittest.mock import Mock, call, patch

from api import ApiClient, FrameUploader, RfidReadPublisher, HeartbeatPublisher
from app import Controller
from core import RegistrationPair, ScanEpoch, ScanReading
from hardware import QrDecoder, WebcamWorker


class FrameUploadTest(unittest.TestCase):
    def test_heartbeat_uses_a_separate_authenticated_request(self):
        stop = threading.Event()
        api = ApiClient('https://example.test/api', 'test-token')
        snapshot = {'mode': 'check', 'camera': 'ready', 'rfid': 'disabled', 'pending_count': 3}
        api.session.post = Mock(side_effect=lambda *a, **k: (stop.set(), Mock(status_code=200))[1])
        HeartbeatPublisher(api, stop, lambda: snapshot).run()
        api.session.post.assert_called_once_with('https://example.test/api/raspi/heartbeat', json=snapshot, timeout=2, allow_redirects=False)

    def test_heartbeat_recovers_from_a_snapshot_error_without_an_outbox(self):
        stop = threading.Event()
        stop.wait = Mock(return_value=False)
        api = ApiClient('https://example.test/api', 'test-token')
        api.session.post = Mock(side_effect=lambda *a, **k: (stop.set(), Mock(status_code=200))[1])
        snapshot = Mock(side_effect=[RuntimeError('temporary'), {'mode': 'invalid'}])
        HeartbeatPublisher(api, stop, snapshot).run()
        self.assertEqual(snapshot.call_count, 2)
        api.session.post.assert_called_once()
        stop.wait.assert_called_with(10)

    def test_health_snapshot_reports_stalled_workers_and_unknown_queue_honestly(self):
        controller = Controller.__new__(Controller)
        controller.mode = 'check'
        controller.cam = Mock(last_activity=98, is_alive=Mock(return_value=True))
        controller.reader = Mock(last_activity=90, is_alive=Mock(return_value=True))
        controller.outbox = Mock(pending_count=Mock(side_effect=sqlite3.OperationalError('busy')))
        with patch('app.time.monotonic', return_value=100):
            self.assertEqual(controller.health_snapshot(), {'mode': 'check', 'camera': 'ready', 'rfid': 'error', 'pending_count': None})
            controller.cam = None
            self.assertEqual(controller.health_snapshot()['camera'], 'disabled')

    def test_register_mode_publishes_uid_before_a_qr_pair_is_complete(self):
        controller = Controller.__new__(Controller)
        controller.uid_publisher = Mock()
        controller.mode = 'register'
        controller.config = SimpleNamespace(scan_max_age_seconds=5)
        controller.scan_epoch = ScanEpoch()
        controller.pair = RegistrationPair()
        controller.show = Mock()
        controller.outbox = Mock()
        controller.on_scan('rfid', ScanReading('NEW-TAG-001', 0, time.monotonic()))
        controller.uid_publisher.submit.assert_called_once_with('NEW-TAG-001')
        controller.outbox.enqueue.assert_not_called()
        self.assertEqual(controller.pair.uid, 'NEW-TAG-001')

    def test_unknown_uid_notification_does_not_register_or_validate_assets(self):
        stop = threading.Event()
        api = ApiClient('https://example.test/api', 'test-token')

        def post(*args, **kwargs):
            stop.set()
            return Mock(status_code=200)

        api.session.post = Mock(side_effect=post)
        publisher = RfidReadPublisher(api, stop)
        publisher.submit('NEW-TAG-001')
        publisher.start()
        publisher.join(2)
        self.assertFalse(publisher.is_alive())
        self.assertEqual(api.session.post.call_args.args, ('https://example.test/api/rfid/read',))
        payload = api.session.post.call_args.kwargs['json']
        self.assertEqual(payload['rfid_uid'], 'NEW-TAG-001')
        self.assertTrue(payload['scan_id'])

    def test_only_newest_camera_frame_is_sent_as_multipart(self):
        stop = threading.Event()
        api = ApiClient('https://example.test/api', 'test-token')

        def post(*args, **kwargs):
            stop.set()
            return Mock(status_code=200)

        api.session.post = Mock(side_effect=post)
        uploader = FrameUploader(api, stop)
        uploader.submit(b'first')
        uploader.submit(b'latest')
        uploader.start()
        uploader.join(2)

        self.assertFalse(uploader.is_alive())
        api.session.post.assert_called_once_with(
            'https://example.test/api/raspi/frame',
            files={'frame': ('camera.jpg', b'latest', 'image/jpeg')},
            timeout=2,
            allow_redirects=False,
        )

    def test_preview_is_sampled_at_five_fps_and_qr_uses_the_original_frame(self):
        stop = threading.Event()
        events = queue.Queue()
        frame = SimpleNamespace(shape=(480, 640, 3))
        preview = object()
        cv2 = Mock()
        cv2.VideoCapture.return_value.isOpened.return_value = True
        cv2.resize.return_value = preview
        cv2.imencode.return_value = (True, Mock(tobytes=Mock(return_value=b'jpeg')))
        detector = cv2.QRCodeDetector.return_value
        detector.detectAndDecode.return_value = ('TI-DEMO-001', None, None)

        timestamps = iter([0, .08, .16, .24, .32])
        clock = Mock(return_value=0)
        def read_frame():
            clock.return_value = next(timestamps)
            if clock.return_value == .32:
                stop.set()
            return True, frame

        cv2.VideoCapture.return_value.read.side_effect = read_frame
        publish = Mock()
        worker = WebcamWorker(events, stop, publish_frame=publish)
        with patch.dict(sys.modules, {'cv2': cv2, 'pyzbar': None}), patch('hardware.time.monotonic', clock):
            worker.run()

        self.assertEqual(publish.call_args_list, [call(b'jpeg'), call(b'jpeg')])
        self.assertEqual(cv2.resize.call_args_list, [call(frame, (480, 360), interpolation=cv2.INTER_AREA)] * 2)
        self.assertEqual(detector.detectAndDecode.call_args_list, [call(frame)] * 5)
        self.assertEqual(events.get_nowait(), ('qr', ScanReading('TI-DEMO-001', 0, 0)))
        self.assertTrue(events.empty())

    def test_zbar_precedes_opencv_and_invalid_text_can_fall_back(self):
        cv2 = Mock()
        detector = cv2.QRCodeDetector.return_value
        detector.detectAndDecode.return_value = ('TI-OPENCV', 'corners', None)
        zbar = Mock()
        zbar.decode.side_effect = [
            [SimpleNamespace(data=b'\xff'), SimpleNamespace(data=b' https://example.test/a/TI-DEMO-001 ')],
            [SimpleNamespace(data=b'\xff')],
        ]
        frame = object()
        with patch.dict(sys.modules, {'pyzbar': SimpleNamespace(pyzbar=zbar)}):
            decoder = QrDecoder(cv2)

        self.assertEqual(decoder.decode(frame), ('https://example.test/a/TI-DEMO-001', None))
        detector.detectAndDecode.assert_not_called()
        self.assertEqual(decoder.decode(frame), ('TI-OPENCV', 'corners'))
        detector.detectAndDecode.assert_called_once_with(frame)
