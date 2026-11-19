import { setText, showResult, showScanStatus } from './scan-result';
import { createScanHistory } from './scan-history';

const root = document.getElementById('asset-scanner');
const { frameUrl, lookupUrl, detailUrl } = root.dataset;
const picker = [...root.querySelectorAll('[data-camera-mode]')];
const raspberryPanel = document.getElementById('raspi-container');
const browserPanel = document.getElementById('webcam-container');
const preview = document.getElementById('raspi-stream-img');
const previewEmpty = document.getElementById('raspi-stream-empty');
const connection = document.getElementById('raspi-connection');
const streamState = document.getElementById('raspi-stream-state');
const streamHint = document.getElementById('raspi-stream-hint');
const result = document.getElementById('scan-result');
const cameraError = document.getElementById('browser-camera-error');
const dialog = document.getElementById('scan-result-dialog');
const pendingResults = [];
let leaving = false;
let resumeAt = 0;
const history = createScanHistory(root.dataset, record => {
    pendingResults.push(record);
    if (pendingResults.length > 100) pendingResults.shift();
    showNextResult();
});

function showNextResult() {
    if (leaving || dialog.open || !pendingResults.length) return;
    const record = pendingResults.shift();
    showResult(record.data, detailUrl, 'Aset berhasil ditemukan.');
    dialog.showModal();
}
document.getElementById('scan-dialog-close').addEventListener('click', () => dialog.close());
dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
dialog.addEventListener('keydown', event => {
    if (event.key !== 'Tab' || event.ctrlKey || event.metaKey || event.altKey) return;
    const controls = [...dialog.querySelectorAll('a[href], button:not(:disabled), [tabindex="0"]')]
        .filter(element => element.getClientRects().length);
    const index = controls.indexOf(document.activeElement);
    if (index < 0 || (event.shiftKey && index === 0) || (!event.shiftKey && index === controls.length - 1)) {
        event.preventDefault();
        controls[event.shiftKey ? controls.length - 1 : 0]?.focus();
    }
});
dialog.addEventListener('close', () => {
    result.hidden = true;
    showScanStatus('Siap untuk scan QR atau RFID berikutnya.');
    queueMicrotask(showNextResult);
});

let frameRequest;
let frameTag;
let browserScanner;
let cameraCleanup = Promise.resolve();
let lastCode;
let retryLookupAt = 0;
let lookupPending = false;

function clearPreview() {
    if (preview.src.startsWith('blob:')) URL.revokeObjectURL(preview.src);
    preview.removeAttribute('src');
    frameTag = undefined;
}

function setCameraState(state, title, hint) {
    connection.dataset.state = state;
    setText(connection, title);
    setText(streamState, title);
    setText(streamHint, hint);
    preview.hidden = state !== 'online';
    previewEmpty.hidden = state === 'online';
}

async function pollFrames(request) {
    if (request.signal.aborted) return;
    if (document.hidden) {
        setTimeout(() => pollFrames(request), 1000);
        return;
    }
    const startedAt = performance.now();
    let interval = 175;
    try {
        const response = await fetch(frameUrl, {
            cache: 'no-store',
            signal: request.signal,
            headers: frameTag ? { 'If-None-Match': frameTag } : {},
        });
        if (request.signal.aborted) return;
        if (response.status === 304) {
            setCameraState('online', 'Kamera terhubung', '');
        } else if (response.ok && response.headers.get('X-Raspi-Status') === 'online'
            && response.headers.get('Content-Type')?.includes('image/jpeg')) {
            const frame = await response.blob();
            if (request.signal.aborted) return;
            const previous = preview.src;
            preview.src = URL.createObjectURL(frame);
            if (previous.startsWith('blob:')) URL.revokeObjectURL(previous);
            frameTag = response.headers.get('ETag');
            setCameraState('online', 'Kamera terhubung', '');
        } else {
            interval = 1500;
            clearPreview();
            if (response.redirected || response.status === 401 || response.status === 403) {
                setCameraState('offline', 'Sesi perlu diperbarui', 'Muat ulang halaman dan masuk kembali.');
            } else {
                setCameraState('offline', 'Menunggu gambar', 'Periksa webcam, internet Pi, dan service asset-iot.');
            }
        }
    } catch {
        if (request.signal.aborted) return;
        interval = 2000;
        clearPreview();
        setCameraState('offline', 'Koneksi terputus', 'Periksa koneksi internet. Kamera akan dicoba lagi.');
    }
    if (!request.signal.aborted) {
        setTimeout(() => pollFrames(request), Math.max(0, interval - (performance.now() - startedAt)));
    }
}

function refreshFrames() {
    frameRequest?.abort();
    frameRequest = new AbortController();
    setCameraState('connecting', 'Menghubungkan kamera', 'Menunggu gambar dari Raspberry Pi.');
    pollFrames(frameRequest);
}

async function lookupQr(code) {
    if (dialog.open || performance.now() < resumeAt || lookupPending || (code === lastCode && performance.now() < retryLookupAt)) return;
    lookupPending = true;
    lastCode = code;
    retryLookupAt = performance.now() + 3000;
    showScanStatus('Memeriksa kode QR…');
    try {
        const response = await fetch(lookupUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ code }),
        });
        const data = await response.json();
        if (response.ok && data.success) {
            retryLookupAt = Infinity;
            history.accept(data.history);
        } else {
            showScanStatus(data.message || 'Kode aset tidak ditemukan.', 'error');
        }
    } catch {
        showScanStatus('Pencarian gagal. Muat ulang halaman bila sesi sudah berakhir.', 'error');
    } finally {
        lookupPending = false;
    }
}

async function selectCamera(mode) {
    const raspberryActive = mode === 'raspi';
    if (raspberryActive ? !raspberryPanel.hidden : !browserPanel.hidden) return;
    raspberryPanel.hidden = !raspberryActive;
    browserPanel.hidden = raspberryActive;
    picker.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.cameraMode === mode)));
    if (raspberryActive) {
        if (browserScanner) {
            cameraCleanup = browserScanner.clear().catch(() => {});
            browserScanner = null;
        }
        refreshFrames();
        return;
    }
    frameRequest?.abort();
    clearPreview();
    await cameraCleanup;
    if (browserPanel.hidden || browserScanner) return;

    try {
        const { Html5QrcodeScanner } = await import('html5-qrcode');
        if (browserPanel.hidden || browserScanner) return;
        cameraError.hidden = true;
        browserScanner = new Html5QrcodeScanner('reader', { fps: 10, rememberLastUsedCamera: true }, false);
        browserScanner.render(code => {
            if (!browserPanel.hidden) lookupQr(code);
        });
    } catch {
        browserScanner = null;
        cameraError.hidden = false;
    }
}

picker.forEach(button => button.addEventListener('click', () => selectCamera(button.dataset.cameraMode)));
document.getElementById('raspi-refresh').addEventListener('click', refreshFrames);
document.getElementById('scan-reset').addEventListener('click', () => {
    dialog.close();
    lastCode = undefined;
    resumeAt = performance.now() + 2000;
    showScanStatus('Menunggu scan QR atau RFID.');
});
window.addEventListener('pagehide', () => {
    leaving = true;
    pendingResults.length = 0;
    dialog.close();
    frameRequest?.abort();
    clearPreview();
});
window.addEventListener('pageshow', event => {
    leaving = false;
    if (event.persisted && !raspberryPanel.hidden) refreshFrames();
});

refreshFrames();
