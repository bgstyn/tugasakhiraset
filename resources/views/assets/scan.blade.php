@extends('layouts.layout')

@section('title', 'Scan Aset - IT Asset Management')
@section('page_title', 'Scan aset')

@section('content')
<div id="asset-scanner" class="scanner-workspace"
     data-frame-url="{{ route('assets.scan.live-frame', [], false) }}"
     data-latest-url="{{ route('assets.scan.latest', [], false) }}"
     data-lookup-url="{{ route('assets.scan.lookup', [], false) }}"
     data-detail-url="{{ route('assets.show', ['asset' => 0], false) }}"
     data-opened-at="{{ (int) (microtime(true) * 1000) }}">
    <section class="ops-surface scanner-panel" aria-labelledby="scanner-camera-title">
        <div class="scanner-panel-heading">
            <div>
                <h2 id="scanner-camera-title">Kamera</h2>
                <p>Arahkan QR aset ke kamera, atau tempel kartu pada pembaca RFID Pi.</p>
            </div>
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
    </section>

    <section class="ops-surface scanner-panel" aria-labelledby="scanner-result-title">
        <div class="scanner-panel-heading">
            <div>
                <h2 id="scanner-result-title">Hasil scan</h2>
                <p id="scan-status" class="scanner-info" role="status" aria-live="polite">Menunggu scan QR atau RFID.</p>
            </div>
        </div>
        @include('assets.partials.scan-result')
    </section>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/html5-qrcode" defer></script>
@endsection

