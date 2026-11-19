import { setText, showResult, showScanStatus } from './scan-result';
import { watchUid } from './rfid-input';

const { dataset } = document.getElementById('rfid-validator');
const form = document.getElementById('rfid-form');
const input = document.getElementById('rfid_input');
const result = document.getElementById('scan-result');
const submit = form.querySelector('[type="submit"]');
let lookup;
let device;
let feedConnected;
let serverUnavailable = false;
let phase;
let phaseTimer;
let healthRequest;
let healthTimer;
const live = document.getElementById('rfid-live-state');
const badge = document.getElementById('rfid-live-badge');

function renderLive() {
    const state = device?.data;
    let tone = 'checking';
    let label = 'Memeriksa…';
    let title = 'Memeriksa pembaca';
    let hint = 'Menunggu status Raspberry Pi…';
    if (serverUnavailable || feedConnected === false) {
        tone = 'error'; label = 'Koneksi terputus'; title = 'Aliran data belum tersedia';
        hint = 'Koneksi ke server akan dicoba lagi secara otomatis.';
    } else if (device && !device.online) {
        tone = 'offline'; label = 'Pi offline'; title = 'Pembaca belum terhubung';
        hint = 'Menunggu heartbeat Raspberry Pi. Input UID manual tetap tersedia.';
    } else if (device?.online && state?.rfid !== 'ready') {
        tone = 'warning'; label = 'Reader belum siap'; title = 'Periksa pembaca RC522';
        hint = state?.rfid === 'disabled' ? 'Pembaca RFID dinonaktifkan pada Pi.' : 'Pi terhubung, tetapi pembaca RFID belum siap.';
    } else if (device?.online && state?.mode !== 'check') {
        tone = 'warning'; label = state?.mode === 'register' ? 'Mode Daftar' : 'Periksa switch';
        title = 'Pilih mode Cek pada Pi'; hint = 'Setelah mode Cek aktif, tempelkan kartu pada RC522.';
    } else if (device?.online && feedConnected === true) {
        tone = 'ready'; label = 'Siap · Real-time'; title = 'Menunggu kartu RFID';
        hint = 'Tempelkan kartu pada RC522. UID akan diterima dan diperiksa otomatis.';
    }
    badge.dataset.state = tone;
    setText(badge.querySelector('span'), label);
    live.dataset.state = phase?.state || tone;
    setText(document.getElementById('rfid-live-title'), phase?.title || title);
    setText(document.getElementById('rfid-reader-status'), phase?.message || hint);
    setText(document.getElementById('rfid-device-state'), serverUnavailable ? 'Tidak dapat diperiksa' : device ? (device.online ? 'Pi online' : 'Pi offline') : 'Memeriksa…');
    setText(document.getElementById('rfid-switch-mode'), device?.online ? ({ check: 'Cek', register: 'Daftar', invalid: 'Periksa switch' }[state?.mode] || '—') : '—');
}

function setPhase(value, duration = 0) {
    clearTimeout(phaseTimer);
    phase = value;
    renderLive();
    if (duration) phaseTimer = setTimeout(() => { phase = null; renderLive(); }, duration);
}

function startHealth() {
    healthRequest?.abort();
    clearTimeout(healthTimer);
    device = undefined;
    serverUnavailable = false;
    renderLive();
    const request = new AbortController();
    healthRequest = request;
    async function poll() {
        try {
            if (document.hidden) return;
            const response = await fetch(dataset.statusUrl, {
                cache: 'no-store', headers: { Accept: 'application/json' },
                signal: AbortSignal.any([request.signal, AbortSignal.timeout(5000)]),
            });
            if (!response.ok) throw new Error('Device status unavailable');
            const payload = await response.json();
            if (request.signal.aborted) return;
            device = payload;
            serverUnavailable = false;
        } catch {
            if (request.signal.aborted) return;
            device = undefined;
            serverUnavailable = true;
        } finally {
            if (!request.signal.aborted) {
                renderLive();
                healthTimer = setTimeout(poll, 5000);
            }
        }
    }
    poll();
}

async function validateUid(origin = 'manual') {
    const uid = input.value.trim();
    if (!uid) return;
    lookup?.abort();
    const request = new AbortController();
    lookup = request;

    result.hidden = true;
    document.getElementById('rfid-inline-result').hidden = false;
    document.getElementById('scan-status').hidden = false;
    submit.disabled = true;
    showScanStatus('Memeriksa UID…');
    setPhase({ state: 'validating', title: 'Memeriksa UID…', message: origin === 'manual' ? 'Pemeriksaan UID manual melalui server.' : 'UID diterima. Memeriksa data aset…' });
    try {
        const params = new URLSearchParams({ rfid_uid: uid });
        const response = await fetch(dataset.lookupUrl + '?' + params, {
            headers: { Accept: 'application/json' },
            signal: request.signal,
        });
        const payload = await response.json();
        if (request.signal.aborted) return;
        if (response.ok && payload.success) {
            showResult(payload.data, dataset.detailUrl, 'RFID terdaftar.');
            setPhase({ state: 'success', title: 'Kartu terdaftar', message: `${payload.data.name || payload.data.asset_id} berhasil ditemukan.` }, 4000);
        } else {
            showScanStatus(payload.message || 'Validasi RFID gagal.', 'error');
            setPhase({ state: 'error', title: 'UID belum dapat digunakan', message: 'UID sudah diterima. Periksa rincian hasil validasi.' }, 5000);
        }
    } catch {
        if (!request.signal.aborted) {
            showScanStatus('Pemeriksaan gagal. Coba lagi atau muat ulang halaman.', 'error');
            setPhase({ state: 'error', title: 'Pemeriksaan terputus', message: 'Coba lagi setelah koneksi ke server pulih.' }, 5000);
        }
    } finally {
        if (lookup === request) submit.disabled = false;
    }
}

form.addEventListener('submit', event => {
    event.preventDefault();
    validateUid();
});
document.getElementById('scan-reset').addEventListener('click', () => {
    lookup?.abort();
    submit.disabled = false;
    result.hidden = true;
    input.value = '';
    document.getElementById('rfid-inline-result').hidden = true;
    setPhase(null);
    showScanStatus('Menunggu kartu RFID.');
    document.getElementById('scan-status').hidden = true;
    if (input.closest('details').open) input.focus();
    else document.getElementById('rfid-live-title').focus();
});

submit.textContent = 'Periksa RFID';
submit.disabled = false;
let stop;
function receiveUid(since) {
    stop?.();
    stop = watchUid(dataset.uidUrl, uid => {
        input.value = uid;
        setText(document.getElementById('rfid-last-uid'), uid);
        const received = new Date();
        const time = document.getElementById('rfid-last-read-time');
        time.dateTime = received.toISOString();
        setText(time, `Diterima ${received.toLocaleTimeString('id-ID')}`);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        validateUid('reader');
    }, connected => {
        feedConnected = connected;
        renderLive();
    }, since);
}
receiveUid(Number(dataset.openedAt));
startHealth();
window.addEventListener('pagehide', () => { stop?.(); lookup?.abort(); healthRequest?.abort(); clearTimeout(healthTimer); clearTimeout(phaseTimer); });
window.addEventListener('pageshow', event => {
    if (event.persisted) { phase = null; receiveUid(null); startHealth(); }
});
