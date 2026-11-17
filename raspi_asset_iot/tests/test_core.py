import tempfile
import unittest
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from unittest.mock import Mock, patch

import requests
from core import Outbox, RegistrationPair, Result, normalize_qr
from api import ApiClient


class TestScanner(unittest.TestCase):
    def test_qr_clean_laravel_url(self):
        self.assertEqual(normalize_qr('https://example.test/a/TIPNP-2026-0001?scan=1'), 'TIPNP-2026-0001')
        self.assertEqual(normalize_qr('TIPNP-2026-0001'), 'TIPNP-2026-0001')
        self.assertEqual(normalize_qr('http://localhost/assets/code/AA%20BB'), 'AA BB')
        with self.assertRaises(ValueError):
            normalize_qr('https://example.test/anything/else')

    def test_pair_any_order(self):
        pair = RegistrationPair(90)
        self.assertIsNone(pair.add('rfid', 'AA11', now=1))
        self.assertEqual(pair.add('qr', 'https://server.test/a/ASSET-1', now=2), {'asset_id':'ASSET-1','rfid_uid':'AA11'})
        pair.reset()  # controller acknowledges the committed SQLite write
        self.assertIsNone(pair.add('qr', 'ASSET-2', now=3))
        self.assertEqual(pair.add('rfid', 'BB22', now=4), {'asset_id':'ASSET-2','rfid_uid':'BB22'})

    def test_pair_expires_and_resets(self):
        pair = RegistrationPair(10)
        pair.add('qr','TEST-1',now=1)
        self.assertIsNone(pair.add('rfid','RFID1',now=12))
        self.assertIsNone(pair.asset_id)

    def test_durable_outbox_and_retry(self):
        with tempfile.TemporaryDirectory() as temp:
            path = Path(temp)/'queue.db'
            q = Outbox(str(path))
            id1, inserted = q.enqueue('check', {'asset_id':'A1'})
            self.assertTrue(inserted)
            id2, inserted = q.enqueue('check', {'asset_id':'A1'})
            self.assertEqual(id1, id2)
            self.assertFalse(inserted)
            self.assertEqual(Outbox(str(path)).pending_count(), 1)
            event = q.get_due()
            self.assertEqual(event['id'], id1)
            q.finish(id1, Result('retry','OFFLINE','server mati'), 0)
            self.assertEqual(q.pending_count(), 1)
            self.assertIsNone(q.get_due())
            q.finish(id1, Result('confirmed','OK','success'), 1)
            self.assertEqual(q.pending_count(), 0)
            self.assertEqual(q.stats()['success'], 1)

    def test_parallel_enqueue_of_same_scan_creates_one_pending_event(self):
        with tempfile.TemporaryDirectory() as temp:
            outbox = Outbox(Path(temp) / 'queue.db')
            with ThreadPoolExecutor(max_workers=8) as pool:
                results = list(pool.map(lambda _: outbox.enqueue('check', {'asset_id': 'A1'}), range(16)))
            self.assertEqual(len({event_id for event_id, _ in results}), 1)
            self.assertEqual(sum(new for _, new in results), 1)

    def test_replay_after_restart_uses_same_key_even_when_reply_is_lost(self):
        client = ApiClient('https://example.test/api', 'token')
        success = Mock(status_code=200)
        success.json.return_value = {'success': True, 'data': {'asset_id': 'A1'}}
        with tempfile.TemporaryDirectory() as temp:
            path = Path(temp) / 'queue.db'
            payload = {'asset_id': 'A1', 'rfid_uid': '0AFF'}
            with patch('core.time.time', return_value=100), patch.object(client.session, 'post', side_effect=[requests.ReadTimeout(), success]) as post:
                outbox = Outbox(path)
                event_id, _ = outbox.enqueue('register', payload)
                first = outbox.get_due()
                failure = client.process(first)
                self.assertEqual(failure.state, 'retry')
                outbox.finish(first['id'], failure, first['attempts'])
                self.assertIsNone(outbox.get_due())
                with patch('core.time.time', return_value=110):
                    restarted = Outbox(path)
                    retry = restarted.get_due()
                    result = client.process(retry)
                    self.assertEqual(result.state, 'confirmed')
                    restarted.finish(retry['id'], result, retry['attempts'])
                self.assertEqual(restarted.stats(), {'success': 1})
                self.assertEqual(post.call_args_list[0], post.call_args_list[1])
                self.assertEqual(post.call_args.kwargs['headers'], {'Idempotency-Key': event_id})
                self.assertFalse(post.call_args.kwargs['allow_redirects'])
                self.assertTrue(post.call_args.args[0].endswith('/rfid/register'))
        client.close()

    def test_validation_is_permanent_but_proxy_or_auth_failures_retry(self):
        client = ApiClient('https://example.test/api', 'token')
        event = {'id': 'key', 'kind': 'check', 'payload': {'rfid_uid': 'AABB'}}
        for status, body, expected in [
            (404, {'success': False}, 'rejected'),
            (400, {'success': False}, 'rejected'),
            (401, {'success': False}, 'retry'),
            (302, None, 'retry'),
            (404, None, 'retry'),
            (429, None, 'retry'),
            (502, None, 'retry'),
            (200, {'success': True}, 'retry'),
        ]:
            with self.subTest(status=status, body=body):
                response = Mock(status_code=status)
                response.json = Mock(side_effect=ValueError) if body is None else Mock(return_value=body)
                with patch.object(client.session, 'post', return_value=response):
                    self.assertEqual(client.process(event).state, expected)
        client.close()


if __name__ == '__main__':
    unittest.main()
