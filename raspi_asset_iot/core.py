"""Logic that can be unit-tested without Raspberry Pi hardware."""
import json
import re
import sqlite3
import time
import uuid
from contextlib import contextmanager
from dataclasses import dataclass
from pathlib import Path
from urllib.parse import urlsplit, unquote

# Existing Laravel QR codes point to /a/<asset_id>. Registration only accepts a raw asset_id.
ASSET_ID_PATTERN = re.compile(r'^[^/?#\\\x00-\x1f]{1,120}$')


def normalize_qr(raw: str) -> str:
    value = raw.strip()
    if not value:
        raise ValueError('QR kosong')
    if value.lower().startswith(('https://', 'http://')):
        parts = urlsplit(value)
        path = unquote(parts.path)
        prefixes = ('/a/', '/assets/code/', '/assets/')
        segment = next((path.split(prefix, 1)[1] for prefix in prefixes if prefix in path), None)
        if segment is None or '/' in segment:
            raise ValueError('URL QR bukan alamat aset Laravel')
        value = segment
    value = value.strip()
    if not ASSET_ID_PATTERN.fullmatch(value):
        raise ValueError('Format QR bukan ID aset yang dikenal')
    return value


@dataclass(frozen=True)
class Result:
    state: str   # confirmed, rejected, retry
    title: str
    detail: str
    http_status: int = 0


class Outbox:
    """Durable queue; one connection per action so the worker thread can run independently."""
    def __init__(self, db_path: str, pending_limit: int = 10000):
        self.path = str(db_path)
        self.pending_limit = pending_limit
        Path(db_path).parent.mkdir(parents=True, exist_ok=True)
        with self._connect() as db:
            db.execute('PRAGMA journal_mode = WAL')
            db.execute('''CREATE TABLE IF NOT EXISTS events (
                id TEXT PRIMARY KEY,
                kind TEXT NOT NULL,
                payload TEXT NOT NULL,
                state TEXT NOT NULL DEFAULT 'pending',
                created_at REAL NOT NULL,
                next_attempt REAL NOT NULL,
                attempts INTEGER NOT NULL DEFAULT 0,
                last_error TEXT NOT NULL DEFAULT '',
                processed_at REAL
            )''')
            db.execute('CREATE INDEX IF NOT EXISTS idx_events_due ON events(state, next_attempt, created_at)')

    @contextmanager
    def _connect(self):
        db = sqlite3.connect(self.path, timeout=8)
        try:
            db.execute('PRAGMA synchronous = FULL')
            with db:
                yield db
        finally:
            db.close()

    def enqueue(self, kind: str, payload: dict):
        if kind not in ('check', 'register'):
            raise ValueError('Jenis kejadian tidak dikenal')
        data = json.dumps(payload, sort_keys=True, ensure_ascii=False)
        now = time.time()
        with self._connect() as db:
            db.execute('BEGIN IMMEDIATE')
            same = db.execute("SELECT id FROM events WHERE state='pending' AND kind=? AND payload=? LIMIT 1", (kind, data)).fetchone()
            if same:
                return same[0], False
            count = db.execute("SELECT COUNT(*) FROM events WHERE state='pending'").fetchone()[0]
            if count >= self.pending_limit:
                raise RuntimeError('Antrean penuh; data baru tidak ditambahkan')
            event_id = str(uuid.uuid4())
            db.execute('INSERT INTO events (id,kind,payload,created_at,next_attempt) VALUES (?,?,?,?,?)',
                       (event_id, kind, data, now, now))
            return event_id, True

    def get_due(self):
        with self._connect() as db:
            row = db.execute("SELECT id,kind,payload,attempts FROM events WHERE state='pending' AND next_attempt<=? ORDER BY created_at LIMIT 1", (time.time(),)).fetchone()
            if row:
                return {'id': row[0], 'kind': row[1], 'payload': json.loads(row[2]), 'attempts': row[3]}
        return None

    def finish(self, event_id: str, result: Result, attempts: int):
        with self._connect() as db:
            if result.state == 'retry':
                # exponential backoff (max 5 min); auth failures also back off
                delay = min(300, 5 * (2 ** min(attempts, 6)))
                db.execute("UPDATE events SET attempts=attempts+1,next_attempt=?,last_error=? WHERE id=? AND state='pending' AND attempts=?",
                           (time.time()+delay, result.detail[:300], event_id, attempts))
            else:
                db.execute("UPDATE events SET state=?,attempts=attempts+1,last_error=?,processed_at=? WHERE id=? AND state='pending'",
                           ('success' if result.state == 'confirmed' else 'rejected', result.detail[:300], time.time(), event_id))

    def pending_count(self):
        with self._connect() as db:
            return db.execute("SELECT COUNT(*) FROM events WHERE state='pending'").fetchone()[0]

    def stats(self):
        with self._connect() as db:
            return dict(db.execute('SELECT state,COUNT(*) FROM events GROUP BY state').fetchall())


class RegistrationPair:
    def __init__(self, timeout: float = 90):
        self.timeout = timeout
        self.reset()

    def reset(self):
        self.asset_id = None
        self.uid = None
        self.updated = None

    def expired(self, now=None):
        now = now if now is not None else time.monotonic()
        return self.updated is not None and now - self.updated >= self.timeout

    def add(self, kind: str, value: str, now=None):
        now = now if now is not None else time.monotonic()
        if self.expired(now):
            self.reset()
        if kind == 'qr':
            self.asset_id = normalize_qr(value)
        elif kind == 'rfid':
            self.uid = value
        else:
            raise ValueError('Jenis scan tidak dikenal')
        self.updated = now
        if self.asset_id and self.uid:
            result = {'asset_id': self.asset_id, 'rfid_uid': self.uid}
            self.reset()
            return result
        return None
