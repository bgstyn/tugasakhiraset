import threading
import unittest
from unittest.mock import Mock

from api import ApiClient, FrameUploader


class FrameUploadTest(unittest.TestCase):
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
            timeout=6,
            allow_redirects=False,
        )
