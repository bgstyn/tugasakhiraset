<div id="scan-result" hidden>
    <h3 id="scan-asset-name"></h3>
    <dl class="scanner-details">
        <div><dt>Kode aset</dt><dd data-field="asset_id"></dd></div>
        @unless($compact ?? false)
            <div><dt>UID RFID</dt><dd data-field="rfid_uid"></dd></div>
        @endunless
        <div><dt>Status</dt><dd data-field="status"></dd></div>
        <div><dt>Lokasi</dt><dd data-field="location"></dd></div>
        @unless($compact ?? false)
            <div><dt>Penanggung jawab</dt><dd data-field="current_user"></dd></div>
        @endunless
    </dl>
    <div class="scanner-actions">
        <a id="scan-detail-link" class="ops-button ops-button-primary">Buka detail aset</a>
        <button type="button" id="scan-reset" class="ops-button ops-button-secondary">Scan lagi</button>
    </div>
</div>
