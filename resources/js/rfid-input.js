export function watchUid(url, onRead, onConnection = () => {}, since = null) {
    const controller = new AbortController();
    let initialized = false;
    let lastRead;
    let timer;

    async function poll() {
        if (controller.signal.aborted) return;
        try {
            const response = await fetch(url, {
                cache: 'no-store',
                headers: { Accept: 'application/json' },
                signal: AbortSignal.any([controller.signal, AbortSignal.timeout(5000)]),
            });
            if (!response.ok) throw new Error('UID feed unavailable');
            const envelope = await response.json();
            if (controller.signal.aborted) return;
            const read = envelope.data;
            const isNew = read?.scan_id && read.scan_id !== lastRead;
            const eligible = initialized || (since !== null && read?.received_at >= since);
            lastRead = read?.scan_id ?? lastRead;
            initialized = true;
            onConnection(true);
            if (isNew && eligible && typeof read.rfid_uid === 'string') onRead(read.rfid_uid);
        } catch {
            if (!controller.signal.aborted) onConnection(false);
        } finally {
            if (!controller.signal.aborted) timer = setTimeout(poll, 500);
        }
    }
    poll();

    return () => {
        controller.abort();
        clearTimeout(timer);
    };
}
