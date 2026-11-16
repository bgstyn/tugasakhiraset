import { showResult, showScanStatus, watchScans } from './scan-result';

const { dataset } = document.getElementById('rfid-validator');
const form = document.getElementById('rfid-form');
const input = document.getElementById('rfid_input');
const result = document.getElementById('scan-result');
const submit = form.querySelector('[type="submit"]');

form.addEventListener('submit', async event => {
    event.preventDefault();
    const uid = input.value.trim();
    if (!uid || submit.disabled) return;

    result.hidden = true;
    submit.disabled = true;
    showScanStatus('Memeriksa UID…');
    try {
        const params = new URLSearchParams({ rfid_uid: uid });
        const response = await fetch(dataset.lookupUrl + '?' + params, {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();
        if (response.ok && payload.success) {
            showResult(payload.data, dataset.detailUrl, 'RFID terdaftar.');
        } else {
            showScanStatus(payload.message || 'Validasi RFID gagal.', 'error');
        }
    } catch {
        showScanStatus('Pemeriksaan gagal. Coba lagi atau muat ulang halaman.', 'error');
    } finally {
        submit.disabled = false;
    }
});
document.getElementById('scan-reset').addEventListener('click', () => {
    result.hidden = true;
    input.value = '';
    showScanStatus('Menunggu kartu RFID.');
    input.focus();
});

submit.textContent = 'Periksa RFID';
submit.disabled = false;
watchScans(dataset, { rfidOnly: true, requestPending: () => submit.disabled });
