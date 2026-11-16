@extends('layouts.layout')

@section('title', 'Ringkasan - Inventaris TI')
@section('page_title', 'Ringkasan inventaris')

@section('content')
@php
    $metrics = [
        ['Total aset', 'total', null],
        ['Siap dipakai', 'standby', 'standby'],
        ['Sedang dipakai', 'digunakan', 'digunakan'],
        ['Dalam perbaikan', 'maintenance', 'maintenance'],
    ];
    $attention = ['rusak' => 'Rusak', 'fraud' => 'Hilang / investigasi', 'write_off' => 'Dihapus dari inventaris'];
    $rfidMetrics = [
        'with_rfid' => 'Memiliki tag',
        'without_rfid' => 'Tanpa tag',
        'rfid_aktif' => 'Tag aktif',
        'rfid_belum_terdaftar' => 'Belum terdaftar',
    ];
    $historyLabel = [
        'create' => 'Aset ditambahkan',
        'update' => 'Aset diperbarui',
        'delete' => 'Aset dihapus',
        'status_change' => 'Status diubah',
        'location_change' => 'Lokasi diubah',
        'assignment_change' => 'Penanggung jawab diubah',
        'rfid_change' => 'RFID diubah',
        'ticket_created' => 'Tiket kerusakan dibuat',
        'ticket_status_change' => 'Status tiket diubah',
    ];
@endphp
<div class="ops-dashboard">
    <section class="ops-surface ops-dashboard-intro" aria-labelledby="dashboard-summary">
        <div>
            <h2 id="dashboard-summary">Status aset TI</h2>
            @if(session('staff_it.location'))
                <p>{{ session('staff_it.location') }}</p>
            @endif
        </div>
        <div class="ops-inline-actions">
            <a href="{{ route('assets.create') }}" class="ops-button ops-button-primary">Tambah aset</a>
            <a href="{{ route('assets.rfid.validate') }}" class="ops-button ops-button-secondary">Validasi RFID</a>
        </div>
    </section>

    <section class="ops-metrics" aria-label="Ringkasan status aset">
        @foreach($metrics as [$label, $key, $status])
            <a class="ops-surface ops-metric" href="{{ route('assets.index', $status ? ['status' => $status] : []) }}">
                <span>{{ $label }}</span>
                <strong>{{ number_format($stats[$key], 0, ',', '.') }}</strong>
            </a>
        @endforeach
    </section>

    <div class="ops-dashboard-columns">
        <section class="ops-surface ops-dashboard-panel" aria-labelledby="dashboard-activity">
            <header><h2 class="ops-section-title" id="dashboard-activity">Aktivitas terbaru</h2><a href="{{ route('assets.history') }}">Lihat riwayat</a></header>
            <ul class="ops-data-list">
                @forelse($recentHistories as $history)
                    <li>
                        <div>
                            <small>{{ $historyLabel[$history->action] ?? 'Aktivitas aset' }}</small>
                            @if($history->asset_id)
                                <a href="{{ route('assets.show', $history->asset_id) }}">{{ $history->asset_name }}</a>
                            @else
                                <span>{{ $history->asset_name }}</span>
                            @endif
                            <small>{{ $history->changed_by_name }}</small>
                        </div>
                        <time datetime="{{ $history->created_at->toAtomString() }}">{{ $history->created_at->diffForHumans() }}</time>
                    </li>
                @empty
                    <li>Aktivitas aset akan muncul di sini setelah aset ditambahkan atau diperbarui.</li>
                @endforelse
            </ul>
        </section>

        <section class="ops-surface ops-dashboard-panel" aria-labelledby="dashboard-attention">
            <header><h2 class="ops-section-title" id="dashboard-attention">Perlu ditinjau</h2></header>
            <ul class="ops-data-list">
                @foreach($attention as $status => $label)
                    <li><a href="{{ route('assets.index', ['status' => $status]) }}">{{ $label }}</a><strong>{{ $stats[$status] }}</strong></li>
                @endforeach
            </ul>
        </section>
    </div>

    <section class="ops-surface ops-dashboard-panel" aria-labelledby="dashboard-rfid">
        <header><h2 class="ops-section-title" id="dashboard-rfid">Status RFID</h2><a href="{{ route('assets.rfid.history') }}">Riwayat RFID</a></header>
        <dl class="ops-metrics ops-rfid-metrics">
            @foreach($rfidMetrics as $key => $label)
                <div class="ops-metric">
                    <dt>{{ $label }}</dt>
                    <dd><strong>{{ number_format($rfidStats[$key], 0, ',', '.') }}</strong></dd>
                </div>
            @endforeach
        </dl>
    </section>

    <div class="ops-dashboard-columns">
        <section class="ops-surface ops-dashboard-panel" aria-labelledby="dashboard-rooms">
            <header><h2 class="ops-section-title" id="dashboard-rooms">Aset per ruangan</h2></header>
            <ul class="ops-data-list">
                @forelse($roomStats as $room)
                    <li><span>{{ $room->nama_ruangan }} <small>· Lantai {{ $room->lantai }}</small></span><strong>{{ $room->assets_count }}</strong></li>
                @empty
                    <li>Belum ada aset dengan ruangan tercatat.</li>
                @endforelse
            </ul>
        </section>
        <section class="ops-surface ops-dashboard-panel" aria-labelledby="dashboard-floors">
            <header><h2 class="ops-section-title" id="dashboard-floors">Aset per lantai</h2></header>
            <ul class="ops-data-list">
                @forelse($floorStats as $floor)
                    <li><span>Lantai {{ $floor->lantai }}</span><strong>{{ $floor->assets_count }}</strong></li>
                @empty
                    <li>Belum ada aset dengan lantai tercatat.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
