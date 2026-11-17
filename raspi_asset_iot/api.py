"""Laravel API integration for scanner events and live camera frames."""
import logging
import threading
import time
import uuid
from collections import deque

import requests

from core import Result

LOG = logging.getLogger(__name__)


class ApiClient:
    def __init__(self, base_url: str, token: str, timeout: float = 6):
        self.base_url = base_url.rstrip('/')
        self.timeout = timeout
        self.session = requests.Session()
        self.session.headers.update({
            'X-API-Token': token,
            'Accept': 'application/json',
        })

    def process(self, event):
        registration = event['kind'] == 'register'
        path = '/rfid/register' if registration else '/rfid-scan'
        try:
            response = self.session.post(
                self.base_url + path, json=event['payload'], timeout=self.timeout,
                headers={'Idempotency-Key': event['id']}, allow_redirects=False,
            )
        except requests.RequestException as exc:
            return Result('retry', 'GAGAL KIRIM', f'Koneksi gagal ({type(exc).__name__})')

        status = response.status_code
        if status in (401, 403):
            return Result('retry', 'TOKEN/API ERROR', 'Periksa RFID_API_TOKEN Laravel', status)
        if status in (408, 429) or status >= 500:
            return Result('retry', 'SERVER ERROR', f'HTTP {status}', status)
        try:
            body = response.json()
        except ValueError:
            body = None
        if not isinstance(body, dict) or not isinstance(body.get('success'), bool):
            return Result('retry', 'API ERROR', f'Respons API tidak valid (HTTP {status})', status)
        if status == 200 and body['success']:
            data = body.get('data')
            if isinstance(data, dict) and data.get('asset_id'):
                title = 'DAFTAR BERHASIL' if registration else 'ASET TERDAFTAR'
                return Result('confirmed', title, str(data['asset_id']), status)
            return Result('retry', 'API ERROR', 'Respons API tidak memuat ID aset', status)
        if status in (400, 404, 409, 422) and not body['success']:
            title = 'DAFTAR DITOLAK' if registration else 'DATA DITOLAK'
            if status == 404:
                title = 'ASET TIDAK ADA' if registration else 'BELUM TERDAFTAR'
            message = body.get('message') or 'Validasi gagal'
            return Result('rejected', title, str(message), status)
        return Result('retry', 'API ERROR', f'Respons API tidak diharapkan (HTTP {status})', status)

    def close(self):
        self.session.close()


class FrameUploader(threading.Thread):
    """Upload recent JPEGs independently of the durable scan sender."""

    def __init__(self, api, stop):
        super().__init__(daemon=True, name='frame-uploader')
        self.api = api
        self.stop = stop
        self.frames = deque(maxlen=1)
        self.frame_ready = threading.Event()

    def submit(self, jpeg):
        self.frames.append((time.monotonic(), jpeg))
        self.frame_ready.set()

    def run(self):
        previous = None
        try:
            while not self.stop.is_set():
                self.frame_ready.wait(0.1)
                self.frame_ready.clear()
                if self.stop.is_set():
                    break
                try:
                    captured, jpeg = self.frames.pop()
                except IndexError:
                    continue
                if time.monotonic() - captured > 2:
                    continue
                retry_delay = 1
                try:
                    response = self.api.session.post(
                        self.api.base_url + '/raspi/frame',
                        files={'frame': ('camera.jpg', jpeg, 'image/jpeg')},
                        timeout=min(self.api.timeout, 2),
                        allow_redirects=False,
                    )
                    problem = None if response.status_code == 200 else f'HTTP {response.status_code}'
                    if response.status_code in (401, 403, 429):
                        retry_delay = 5
                except requests.RequestException as exc:
                    problem = type(exc).__name__
                if problem:
                    if problem != previous:
                        LOG.warning('Upload kamera gagal: %s', problem)
                    previous = problem
                    self.stop.wait(retry_delay)
                elif previous is not False:
                    LOG.info('Frame kamera diterima Laravel')
                    previous = False
        finally:
            self.api.close()


class RfidReadPublisher(threading.Thread):
    """Short-lived UID notifications for browser forms. Asset changes use Outbox."""

    def __init__(self, api, stop):
        super().__init__(daemon=True, name='rfid-read-publisher')
        self.api, self.stop = api, stop
        self.reads = deque(maxlen=1)
        self.ready = threading.Event()

    def submit(self, uid):
        self.reads.append((time.monotonic(), {'rfid_uid': uid, 'scan_id': str(uuid.uuid4())}))
        self.ready.set()

    def run(self):
        try:
            while not self.stop.is_set():
                self.ready.wait(0.1)
                self.ready.clear()
                try:
                    captured, payload = self.reads.pop()
                except IndexError:
                    continue
                if self.stop.is_set() or time.monotonic() - captured > 3:
                    continue
                try:
                    response = self.api.session.post(
                        self.api.base_url + '/rfid/read', json=payload,
                        timeout=min(self.api.timeout, 2), allow_redirects=False,
                    )
                    if response.status_code != 200:
                        LOG.warning('Notifikasi UID browser gagal: HTTP %s', response.status_code)
                except requests.RequestException:
                    LOG.warning('Notifikasi UID browser gagal: koneksi tidak tersedia')
        finally:
            self.api.close()


class HeartbeatPublisher(threading.Thread):
    """Current device health only; never enqueue or replay old heartbeats."""

    def __init__(self, api, stop, snapshot):
        super().__init__(daemon=True, name='heartbeat-publisher')
        self.api, self.stop, self.snapshot = api, stop, snapshot

    def run(self):
        try:
            while not self.stop.is_set():
                try:
                    response = self.api.session.post(
                        self.api.base_url + '/raspi/heartbeat', json=self.snapshot(),
                        timeout=min(self.api.timeout, 2), allow_redirects=False,
                    )
                    if response.status_code != 200:
                        LOG.warning('Heartbeat Pi gagal: HTTP %s', response.status_code)
                except Exception:
                    LOG.warning('Heartbeat Pi gagal; akan dicoba lagi')
                self.stop.wait(10)
        finally:
            self.api.close()
