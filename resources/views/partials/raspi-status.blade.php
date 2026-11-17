<section id="raspi-device-status" class="ops-surface raspi-status-panel" data-status-url="{{ route('raspi.status', [], false) }}" aria-labelledby="raspi-status-title">
    <div class="raspi-status-identity">
        <div><h2 id="raspi-status-title">Raspberry Pi</h2><span id="raspi-device-badge" class="device-badge" data-state="unknown" role="status">Memeriksa…</span></div>
    </div>
    <dl class="raspi-device-details">
        <div><dt>Mode switch</dt><dd id="raspi-device-mode">—</dd></div>
        <div><dt>Kamera</dt><dd id="raspi-device-camera">—</dd></div>
        <div><dt>Pembaca RFID</dt><dd id="raspi-device-rfid">—</dd></div>
        <div><dt>Antrean kirim</dt><dd id="raspi-device-pending">—</dd></div>
    </dl>
</section>
