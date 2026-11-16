@extends('layouts.layout')

@section('title', 'Validasi RFID - Inventaris TI')
@section('page_title', 'Validasi RFID')

@section('content')
<div id="rfid-validator" class="scanner-workspace"
     data-lookup-url="{{ route('assets.rfid.validate', [], false) }}"
     data-latest-url="{{ route('assets.scan.latest', [], false) }}"
     data-detail-url="{{ route('assets.show', ['asset' => 0], false) }}"
     data-opened-at="{{ (int) (microtime(true) * 1000) }}">
    <section class="ops-surface scanner-panel" aria-labelledby="rfid-input-heading">
        <div class="scanner-panel-heading">
            <div>
                <h2 id="rfid-input-heading">Masukkan UID</h2>
                <p>Tempelkan kartu pada pembaca Raspberry Pi dalam mode Cek, atau masukkan UID secara manual.</p>
            </div>
        </div>

        <form id="rfid-form" class="ops-form">
            <div class="ops-field">
                <label for="rfid_input">UID RFID</label>
                <input type="text" name="rfid_uid" id="rfid_input" required maxlength="255" autocomplete="off" spellcheck="false">
            </div>
            <div class="ops-inline-actions">
                <button type="submit" class="ops-button ops-button-primary" disabled>Memuat…</button>
                <a href="{{ route('assets.rfid.history') }}" class="ops-button ops-button-secondary">Riwayat RFID</a>
            </div>
        </form>
    </section>
    <section class="ops-surface scanner-panel" aria-labelledby="rfid-result-heading">
        <div class="scanner-panel-heading">
            <div>
                <h2 id="rfid-result-heading">Hasil validasi</h2>
                <p id="scan-status" class="scanner-info" role="status" aria-live="polite">Menunggu kartu RFID.</p>
            </div>
        </div>
        @include('assets.partials.scan-result')
    </section>
</div>
@endsection
