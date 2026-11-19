@extends('layouts.layout')

@section('title', 'Cek aset - Inventaris TI')
@section('page_title', 'Cek aset')

@section('content')
@include('assets.partials.check-modes')
<div id="rfid-validator" class="scanner-workspace"
     data-status-url="{{ route('raspi.status', [], false) }}"
     data-lookup-url="{{ route('assets.rfid.validate', [], false) }}"
     data-latest-url="{{ route('assets.scan.latest', [], false) }}"
     data-uid-url="{{ route('assets.scan.latest-rfid', [], false) }}"
     data-detail-url="{{ route('assets.show', ['asset' => 0], false) }}"
     data-opened-at="{{ (int) (microtime(true) * 1000) }}">
    <section class="ops-surface scanner-panel rfid-live-card" aria-labelledby="rfid-live-title">
        <header class="rfid-live-header">
            <span id="rfid-live-badge" class="rfid-live-badge" data-state="checking"><i aria-hidden="true"></i><span>Memeriksa…</span></span>
        </header>
        <div id="rfid-live-state" class="rfid-live-state" data-state="checking" role="status" aria-live="polite">
            <div class="rfid-reader-symbol" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2"><rect x="24" y="17" width="16" height="30" rx="3"/><path stroke-linecap="round" d="M17 23a17 17 0 0 0 0 18m30-18a17 17 0 0 1 0 18M9 15a28 28 0 0 0 0 34m46-34a28 28 0 0 1 0 34"/><path d="M29 39h6"/></svg></div>
            <h2 id="rfid-live-title" tabindex="-1">Memeriksa pembaca</h2>
            <p id="rfid-reader-status">Menunggu status Raspberry Pi…</p>
        </div>
        <div class="rfid-last-read"><span>UID terakhir diterima</span><output id="rfid-last-uid">—</output><time id="rfid-last-read-time">Belum ada kartu</time></div>
        <div id="rfid-inline-result" class="rfid-inline-result" hidden>
            <p id="scan-status" class="scanner-info" role="status" aria-live="polite" hidden></p>
            @include('assets.partials.scan-result', ['compact' => true])
        </div>
        <dl class="rfid-reader-meta">
            <div><dt>Perangkat</dt><dd id="rfid-device-state">Memeriksa…</dd></div>
            <div><dt>Mode switch</dt><dd id="rfid-switch-mode">—</dd></div>
        </dl>
        <details class="rfid-manual-entry">
            <summary>Masukkan UID secara manual</summary>
            <form id="rfid-form" class="ops-form">
            <div class="ops-field">
                <label for="rfid_input">UID RFID</label>
                <input type="text" name="rfid_uid" id="rfid_input" required maxlength="255" autocomplete="off" spellcheck="false">
            </div>
            <div class="ops-inline-actions">
                <button type="submit" class="ops-button ops-button-primary" disabled>Memuat…</button>
            </div>
            </form>
        </details>
        <a href="{{ route('assets.rfid.history') }}" class="rfid-history-link">Lihat riwayat RFID <span aria-hidden="true">→</span></a>
    </section>
</div>
@endsection
