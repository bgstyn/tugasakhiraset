@extends('layouts.layout')

@section('title', 'Scan Aset - IT Asset Management')
@section('page_title', 'Cek aset')

@section('content')
@include('assets.partials.check-modes')
<div id="asset-scanner" class="scanner-workspace"
     data-frame-url="{{ route('assets.scan.live-frame', [], false) }}"
     data-latest-url="{{ route('assets.scan.latest', [], false) }}"
     data-history-url="{{ route('assets.scan.history', [], false) }}"
     data-lookup-url="{{ route('assets.scan.lookup', [], false) }}"
     data-detail-url="{{ route('assets.show', ['asset' => 0], false) }}"
     data-opened-at="{{ (int) (microtime(true) * 1000) }}">
    <section class="ops-surface scanner-panel" aria-labelledby="scanner-camera-title">
        <div class="scanner-panel-heading">
            <div>
                <h2 id="scanner-camera-title">Temukan aset dengan QR atau RFID</h2>
                <p>Pilih mode Cek pada switch Pi, lalu scan salah satu identitas aset.</p>
            </div>
        </div>

        <div class="scanner-mode-guide">
            <strong>Mode Cek: cukup QR atau RFID</strong>
            <p>Hasil scan menampilkan informasi aset. Pemeriksaan ini tidak memasangkan tag atau mengubah lokasi.</p>
            <details><summary>Ingin menghubungkan tag RFID baru?</summary><ol><li>Lewat website: buka detail aset → Hubungkan RFID. Pi tetap di mode Cek.</li><li>Langsung di Pi: pilih mode Daftar, lalu scan QR dan RFID. Pasangan lengkap disimpan otomatis.</li></ol><p>Mode Daftar memerlukan aset yang sudah dibuat. Tahap pembacaan pasangan ditampilkan di LCD Pi.</p></details>
        </div>

        <div class="scanner-sources" role="group" aria-label="Sumber kamera">
            <button type="button" data-camera-mode="raspi" aria-pressed="true" aria-controls="raspi-container">Raspberry Pi</button>
            <button type="button" data-camera-mode="webcam" aria-pressed="false" aria-controls="webcam-container">Kamera browser</button>
        </div>

        <div id="raspi-container">
            <div class="scanner-preview">
                <img id="raspi-stream-img" alt="Tampilan langsung kamera Raspberry Pi" hidden>
                <div id="raspi-stream-empty" class="scanner-preview-empty" role="status" aria-live="polite">
                    <strong id="raspi-stream-state">Menghubungkan kamera</strong>
                    <p id="raspi-stream-hint">Menunggu gambar dari Raspberry Pi.</p>
                </div>
            </div>
            <div class="scanner-panel-footer">
                <span id="raspi-connection" data-state="connecting">Menghubungkan</span>
                <button type="button" id="raspi-refresh" class="ops-button ops-button-secondary">Muat ulang</button>
            </div>
        </div>

        <div id="webcam-container" hidden>
            <div id="reader"></div>
            <p id="browser-camera-error" class="scanner-info" hidden>Kamera browser belum tersedia. Periksa izin kamera atau gunakan Raspberry Pi.</p>
        </div>
        <p id="scan-status" class="scanner-info" role="status" aria-live="polite">Menunggu scan QR atau RFID.</p>
    </section>

    <section class="ops-surface scanner-panel scan-history-panel" aria-labelledby="scan-history-title">
        <div class="scanner-panel-heading">
            <div>
                <h2 id="scan-history-title">Riwayat cek aset <span id="scan-history-count" class="scan-history-count">0 / 100</span></h2>
                <p id="scan-history-connection">100 hasil terbaru disimpan di server. Hasil paling lama dihapus otomatis.</p>
            </div>
        </div>
        <p id="scan-history-empty" class="scan-history-empty">Belum ada hasil cek. Scan QR atau RFID untuk memulai.</p>
        <div class="scan-history-scroll">
            <table id="scan-history-table" class="scan-history-table" hidden>
                <thead><tr><th>Waktu</th><th>Aset</th><th>Kode aset</th><th>Metode</th><th>Status</th><th>Hasil</th></tr></thead>
                <tbody id="scan-history-body"></tbody>
            </table>
        </div>
    </section>
</div>
<dialog id="scan-result-dialog" class="scan-result-dialog" aria-labelledby="scan-dialog-title">
    <div class="scan-dialog-content">
        <header class="scan-dialog-header">
            <div><span class="scan-success-label">Pemeriksaan berhasil</span><h2 id="scan-dialog-title">Aset ditemukan</h2></div>
            <button type="button" id="scan-dialog-close" class="ops-icon-button" aria-label="Tutup hasil cek">×</button>
        </header>
        @include('assets.partials.scan-result')
    </div>
</dialog>
@endsection
