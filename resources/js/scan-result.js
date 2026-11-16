const status = document.getElementById('scan-status');
const result = document.getElementById('scan-result');

export function setText(element, value) {
    if (element.textContent !== value) element.textContent = value;
}

export function showScanStatus(text, tone = '') {
    setText(status, text);
    status.dataset.tone = tone;
}

export function showResult(asset, detailUrl, message) {
    if (!asset?.id) return;
    setText(document.getElementById('scan-asset-name'), asset.name || asset.asset_id);
    const location = [asset.building, asset.floor && 'Lantai ' + asset.floor, asset.room || asset.location]
        .filter(value => value && value !== '-').join(' / ');
    const values = { ...asset, location, status: asset.status?.replaceAll('_', ' ') };
    result.querySelectorAll('[data-field]').forEach(field => {
        setText(field, values[field.dataset.field] || '—');
    });
    document.getElementById('scan-detail-link').href = detailUrl.replace(/0$/, encodeURIComponent(asset.id));
    result.hidden = false;
    showScanStatus(message || 'Aset ditemukan.', 'success');
}

export function watchScans({ latestUrl, detailUrl, openedAt }, { rfidOnly = false, requestPending } = {}) {
    let lastScan;
    let disconnected = false;
    async function poll() {
        try {
            const response = await fetch(latestUrl, { cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Gagal mengambil hasil scan');
            const envelope = await response.json();
            const scan = envelope.data;
            if (disconnected && result.hidden && !requestPending?.()) showScanStatus('Menunggu scan.');
            disconnected = false;
            const receivedAt = scan?.received_at ?? scan?.timestamp * 1000 + 999;
            if (envelope.success && scan?.data?.id && receivedAt >= Number(openedAt) && !requestPending?.()
                && (!rfidOnly || (scan.source ?? (scan.data.rfid_uid ? 'rfid' : 'qr')) === 'rfid')) {
                const key = scan.scan_id ?? scan.timestamp + ':' + scan.data.id;
                if (key !== lastScan) {
                    lastScan = key;
                    showResult(scan.data, detailUrl, scan.message);
                }
            }
        } catch {
            disconnected = true;
            if (result.hidden && !requestPending?.()) showScanStatus('Koneksi hasil scan terputus. Mencoba lagi…', 'error');
        } finally {
            setTimeout(poll, 1200);
        }
    }
    poll();
}
