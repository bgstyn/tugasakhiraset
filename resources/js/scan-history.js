import { setText, formatStatus } from './scan-result';

export function createScanHistory({ historyUrl, openedAt }, onResult) {
    const records = new Map();
    const body = document.getElementById('scan-history-body');
    const empty = document.getElementById('scan-history-empty');
    const connection = document.getElementById('scan-history-connection');
    let cursor = 0;
    let controller;
    let timer;

    function render() {
        const rows = [...records.values()].sort((a, b) => b.id - a.id).slice(0, 100);
        records.clear();
        rows.forEach(row => records.set(row.id, row));
        body.replaceChildren();
        for (const row of rows) {
            const tr = document.createElement('tr');
            const values = [
                ['Waktu', new Date(row.received_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'medium' })],
                ['Aset', row.data.name || row.data.asset_id],
                ['Kode aset', row.data.asset_id],
                ['Metode', row.source === 'rfid' ? 'RFID' : 'QR'],
                ['Status', formatStatus(row.data.status)],
            ];
            for (const [label, value] of values) {
                const cell = document.createElement('td');
                cell.dataset.label = label;
                cell.textContent = value || '—';
                tr.append(cell);
            }
            const cell = document.createElement('td');
            cell.dataset.label = 'Hasil';
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'scan-history-open';
            button.textContent = 'Lihat hasil';
            button.setAttribute('aria-label', `Lihat hasil ${row.data.asset_id}`);
            button.addEventListener('click', () => onResult(row));
            cell.append(button);
            tr.append(cell);
            body.append(tr);
        }
        empty.hidden = rows.length > 0;
        document.getElementById('scan-history-table').hidden = rows.length === 0;
        setText(document.getElementById('scan-history-count'), `${rows.length} / 100`);
    }

    function accept(incoming, forceNotify = false) {
        let changed = false;
        const fresh = [];
        for (const row of [...incoming].sort((a, b) => a.id - b.id)) {
            if (!Number.isSafeInteger(row?.id) || !row.data?.id || records.has(row.id)) continue;
            records.set(row.id, row);
            changed = true;
            if (forceNotify || row.received_at >= Number(openedAt)) fresh.push(row);
        }
        if (changed) render();
        fresh.slice(-100).forEach(row => onResult(row));
    }

    function start() {
        controller?.abort();
        clearTimeout(timer);
        const request = new AbortController();
        controller = request;
        async function poll() {
            try {
                if (document.hidden) return;
                const response = await fetch(`${historyUrl}?after_id=${cursor}`, {
                    cache: 'no-store', headers: { Accept: 'application/json' },
                    signal: AbortSignal.any([request.signal, AbortSignal.timeout(5000)]),
                });
                if (!response.ok) throw new Error('History unavailable');
                const payload = await response.json();
                if (request.signal.aborted) return;
                if (!Array.isArray(payload.data)) throw new Error('Invalid history');
                accept(payload.data);
                cursor = Math.max(cursor, Number(payload.latest_id) || 0);
                setText(connection, '100 hasil terbaru disimpan di server. Hasil paling lama dihapus otomatis.');
            } catch {
                if (!request.signal.aborted) setText(connection, 'Riwayat belum dapat diperbarui. Mencoba lagi…');
            } finally {
                if (!request.signal.aborted) timer = setTimeout(poll, 2000);
            }
        }
        poll();
    }
    render();
    start();
    window.addEventListener('pagehide', () => { controller?.abort(); clearTimeout(timer); });
    window.addEventListener('pageshow', event => { if (event.persisted) start(); });
    return { accept: row => accept([row], true) };
}
