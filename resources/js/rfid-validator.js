import { showResult, showScanStatus } from './scan-result';
import { watchUid } from './rfid-input';

const { dataset } = document.getElementById('rfid-validator');
const form = document.getElementById('rfid-form');
const input = document.getElementById('rfid_input');
const result = document.getElementById('scan-result');
const submit = form.querySelector('[type="submit"]');
let lookup;

async function validateUid() {
    const uid = input.value.trim();
    if (!uid) return;
    lookup?.abort();
    const request = new AbortController();
    lookup = request;

    result.hidden = true;
    submit.disabled = true;
    showScanStatus('Memeriksa UID…');
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
        } else {
            showScanStatus(payload.message || 'Validasi RFID gagal.', 'error');
        }
    } catch {
        if (!request.signal.aborted) showScanStatus('Pemeriksaan gagal. Coba lagi atau muat ulang halaman.', 'error');
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
    showScanStatus('Menunggu kartu RFID.');
    input.focus();
});

submit.textContent = 'Periksa RFID';
submit.disabled = false;
const hint = document.getElementById('rfid-reader-status');
let stop;
function receiveUid(since) {
    stop?.();
    stop = watchUid(dataset.uidUrl, uid => {
        input.value = uid;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        validateUid();
    }, connected => {
        hint.textContent = connected ? 'Terhubung ke server. Menunggu UID dari Raspberry Pi.' : 'Koneksi ke server terputus. Mencoba lagi…';
    }, since);
}
receiveUid(Number(dataset.openedAt));
window.addEventListener('pagehide', () => { stop?.(); lookup?.abort(); });
window.addEventListener('pageshow', event => {
    if (event.persisted) receiveUid(null);
});
