import { watchUid } from './rfid-input';

const modal = document.getElementById('pair-rfid-modal');
const input = document.getElementById('rfid_uid_input');
const status = document.getElementById('pair-rfid-status');
let stop;

function start() {
    stop?.();
    let disconnected = false;
    status.textContent = 'Tempelkan kartu ke pembaca Pi untuk mengisi UID.';
    stop = watchUid(modal.dataset.uidUrl, uid => {
        input.value = uid;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        status.textContent = 'UID diterima dari Pi. Tekan Simpan RFID untuk memasangkan kartu.';
    }, connected => {
        if (!connected) status.textContent = 'Koneksi ke server terputus. Mencoba lagi…';
        else if (disconnected) status.textContent = 'Server terhubung. Tempelkan kartu ke pembaca Pi dalam mode Cek.';
        disconnected = !connected;
    });
}

modal.addEventListener('rfid-pair-open', start);
modal.addEventListener('rfid-pair-close', () => { stop?.(); stop = undefined; });
window.addEventListener('pagehide', () => stop?.());
window.addEventListener('pageshow', event => {
    if (event.persisted && !modal.classList.contains('hidden')) start();
});
if (!modal.classList.contains('hidden')) start();
