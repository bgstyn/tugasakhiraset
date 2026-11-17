const root = document.getElementById('raspi-device-status');
const badge = document.getElementById('raspi-device-badge');
const fields = Object.fromEntries(['mode', 'camera', 'rfid', 'pending'].map(key => [key, document.getElementById(`raspi-device-${key}`)]));
const modes = { check: 'Cek aset', register: 'Daftar RFID', invalid: 'Periksa switch' };
const devices = { ready: 'Siap', error: 'Belum siap', disabled: 'Dinonaktifkan' };
let controller;
let timer;

function clearFields() {
    Object.values(fields).forEach(field => { field.textContent = '—'; });
}

function render(payload) {
    const status = payload.data;
    badge.dataset.state = payload.online ? 'online' : status ? 'offline' : 'unknown';
    badge.textContent = payload.online ? 'Online' : status ? 'Offline' : 'Belum terhubung';
    if (!payload.online || !status) {
        clearFields();
    } else {
        fields.mode.textContent = modes[status.mode] || '—';
        fields.camera.textContent = devices[status.camera] || '—';
        fields.rfid.textContent = devices[status.rfid] || '—';
        fields.pending.textContent = status.pending_count === null ? 'Tidak tersedia' : `${status.pending_count} transaksi`;
    }
    if (status) {
        const date = new Date(status.last_seen_at);
        badge.title = `Terakhir terhubung: ${date.toLocaleString('id-ID')}.`;
    } else {
        badge.removeAttribute('title');
    }
}

function start() {
    controller?.abort();
    clearTimeout(timer);
    const request = new AbortController();
    controller = request;
    async function poll() {
        try {
            if (document.hidden) return;
            const response = await fetch(root.dataset.statusUrl, {
                cache: 'no-store', headers: { Accept: 'application/json' },
                signal: AbortSignal.any([request.signal, AbortSignal.timeout(5000)]),
            });
            if (!response.ok) throw new Error('Status unavailable');
            const payload = await response.json();
            if (!request.signal.aborted) render(payload);
        } catch {
            if (request.signal.aborted) return;
            clearFields();
            badge.dataset.state = 'unknown';
            badge.textContent = 'Tidak dapat diperiksa';
            badge.title = 'Koneksi ke server terputus. Mencoba lagi…';
        } finally {
            if (!request.signal.aborted) timer = setTimeout(poll, 5000);
        }
    }
    poll();
}

start();
window.addEventListener('pagehide', () => { controller?.abort(); clearTimeout(timer); });
window.addEventListener('pageshow', event => { if (event.persisted) start(); });
