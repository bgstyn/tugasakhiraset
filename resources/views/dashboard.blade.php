@extends('layouts.layout')
@section('title', 'Dashboard - Inventaris TI')
@section('page_title', 'Dashboard')
@section('content')
@php
    $metrics = [
        ['Total aset', $stats['total'], route('assets.index'), 'Seluruh inventaris tercatat', 'M4 4h16v16H4zM4 9h16M9 9v11'],
        ['Sedang digunakan', $stats['digunakan'], route('assets.index', ['status' => 'digunakan']), 'Aset dengan status digunakan', 'M3 4h18v13H3zM8 21h8m-4-4v4'],
        ['Aset rusak', $stats['rusak'], route('assets.index', ['status' => 'rusak']), 'Perlu pemeriksaan petugas', 'm12 3 10 18H2L12 3zm0 6v5m0 3h.01'],
        ['Tiket aktif', $stats['open_tickets'], route('tickets.index'), 'Termasuk menunggu suku cadang', 'M4 5h16v5a2 2 0 0 0 0 4v5H4v-5a2 2 0 0 0 0-4V5zm9 3v8'],
    ];
    $historyLabel = ['create' => 'Aset ditambahkan', 'update' => 'Aset diperbarui', 'delete' => 'Aset dihapus', 'status_change' => 'Status diubah', 'location_change' => 'Lokasi diubah', 'assignment_change' => 'Penanggung jawab diubah', 'rfid_change' => 'RFID dihubungkan', 'ticket_created' => 'Tiket dibuat', 'ticket_status_change' => 'Status tiket diubah'];
    $chartMax = max(2, (int) ceil(max(1, $ticketTrend->max('opened'), $ticketTrend->max('completed')) / 2) * 2);
    $points = fn ($field) => $ticketTrend->map(fn ($item, $index) => (48 + $index * 108).','.round(182 - $item[$field] / $chartMax * 152, 2))->implode(' ');
    $tagPercent = $stats['total'] ? round($rfidStats['with_rfid'] / $stats['total'] * 100) : 0;
@endphp
<div class="ops-dashboard">
    @include('partials.raspi-status')
    <section class="ops-metrics" aria-label="Ringkasan inventaris">
        @foreach($metrics as [$label, $value, $url, $hint, $icon])
            <a class="ops-surface dashboard-metric" href="{{ $url }}"><div><span>{{ $label }}</span><strong>{{ number_format($value, 0, ',', '.') }}</strong><small>{{ $hint }}</small></div><span class="metric-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg></span></a>
        @endforeach
    </section>
    <div class="dashboard-chart-grid">
        <section class="ops-surface dashboard-card" aria-labelledby="ticket-chart-title">
            <header class="dashboard-card-header"><div><h2 id="ticket-chart-title">Tren tiket perbaikan</h2><p>Laporan masuk dan tiket selesai per bulan</p></div><span class="dashboard-period">6 bulan terakhir</span></header>
            <div class="chart-legend"><span><i style="--series:#206bc4"></i>Laporan masuk</span><span><i style="--series:#2fb344"></i>Selesai</span></div>
            <svg class="ticket-trend-chart" viewBox="0 0 640 222" role="img" aria-labelledby="trend-svg-title trend-svg-desc">
                <title id="trend-svg-title">Tren tiket selama enam bulan terakhir</title><desc id="trend-svg-desc">Jumlah tiket masuk berdasarkan tanggal laporan. Tiket selesai berdasarkan riwayat penyelesaian. Rincian tersedia pada tabel di bawah grafik.</desc>
                @foreach([0, .5, 1] as $level)
                    @php($y = 182 - $level * 152)
                    <line x1="48" y1="{{ $y }}" x2="588" y2="{{ $y }}" class="chart-grid-line"/><text x="32" y="{{ $y + 4 }}" text-anchor="end" class="chart-axis-label">{{ $chartMax * $level }}</text>
                @endforeach
                <polyline points="{{ $points('opened') }}" fill="none" stroke="#206bc4" stroke-width="2.5" stroke-linejoin="round"/>
                <polyline points="{{ $points('completed') }}" fill="none" stroke="#2fb344" stroke-width="2.5" stroke-dasharray="5 3" stroke-linejoin="round"/>
                @foreach($ticketTrend as $month)
                    @php($x = 48 + $loop->index * 108)
                    <text x="{{ $x }}" y="210" text-anchor="middle" class="chart-axis-label">{{ $month['label'] }}</text>
                    @foreach(['opened' => '#206bc4', 'completed' => '#2fb344'] as $field => $color)
                        <circle cx="{{ $x }}" cy="{{ 182 - $month[$field] / $chartMax * 152 }}" r="4" fill="{{ $color }}" stroke="white" stroke-width="1.5"><title>{{ $month['full_label'] }}: {{ $month[$field] }} {{ $field === 'opened' ? 'laporan masuk' : 'tiket selesai' }}</title></circle>
                    @endforeach
                @endforeach
            </svg>
            <details class="chart-data"><summary>Lihat angka per bulan</summary><div class="chart-data-scroll"><table><caption class="sr-only">Rincian tren tiket</caption><thead><tr><th scope="col">Bulan</th><th scope="col">Masuk</th><th scope="col">Selesai</th></tr></thead><tbody>@foreach($ticketTrend as $month)<tr><th scope="row">{{ $month['full_label'] }}</th><td>{{ $month['opened'] }}</td><td>{{ $month['completed'] }}</td></tr>@endforeach</tbody></table></div></details>
        </section>
        <section class="ops-surface dashboard-card" aria-labelledby="status-chart-title">
            <header class="dashboard-card-header"><div><h2 id="status-chart-title">Kondisi aset</h2><p>Komposisi seluruh inventaris</p></div></header>
            <div class="status-donut"><svg viewBox="0 0 200 200" role="img" aria-labelledby="donut-title"><title id="donut-title">{{ $stats['total'] }} aset. Rincian status pada daftar di bawah grafik.</title>
                <circle cx="100" cy="100" r="76" fill="none" class="donut-track" stroke-width="23"/>
                @php($offset = 0)
                @foreach($statusChart->where('value', '>', 0) as $segment)
                    @php($share = $segment['value'] / max(1, $stats['total']) * 100)
                    <circle cx="100" cy="100" r="76" fill="none" pathLength="100" stroke="{{ $segment['color'] }}" stroke-width="23" stroke-dasharray="{{ $share }} {{ 100 - $share }}" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 100 100)"><title>{{ $segment['label'] }}: {{ $segment['value'] }} aset</title></circle>
                    @php($offset += $share)
                @endforeach
                <text x="100" y="100" text-anchor="middle" class="donut-value">{{ number_format($stats['total'], 0, ',', '.') }}</text><text x="100" y="123" text-anchor="middle" class="chart-axis-label">Total aset</text>
            </svg></div>
            <ul class="status-chart-legend">@foreach($statusChart->filter(fn ($item) => $item['value'] > 0 || in_array($item['key'], ['standby', 'digunakan', 'maintenance', 'rusak'])) as $segment)<li><a href="{{ route('assets.index', ['status' => $segment['key']]) }}"><span><i style="--series:{{ $segment['color'] }}"></i>{{ $segment['label'] }}</span><strong>{{ $segment['value'] }}</strong></a></li>@endforeach</ul>
        </section>
        <section class="ops-surface dashboard-card" aria-labelledby="location-chart-title">
            <header class="dashboard-card-header"><div><h2 id="location-chart-title">Aset per ruangan</h2><p>{{ $roomStats->count() > 6 ? '6 ruangan dengan aset terbanyak' : 'Jumlah aset berdasarkan lokasi tercatat' }}</p></div><a href="{{ route('locations.index') }}">Lihat lokasi</a></header>
            <ul class="location-bars">@forelse($roomStats->take(6) as $room)<li><div><span>{{ $room->nama_ruangan }}<small>{{ $room->building ?: 'Gedung belum diisi' }} · Lantai {{ $room->lantai ?: '—' }}</small></span><strong>{{ $room->assets_count }}</strong></div><div class="location-bar-track" aria-hidden="true"><span style="width:{{ $room->assets_count / max(1, $roomStats->max('assets_count')) * 100 }}%"></span></div></li>@empty<li class="dashboard-empty">Belum ada lokasi aset. Tambahkan lokasi saat membuat atau mengedit aset.</li>@endforelse</ul>
            <details class="chart-data"><summary>Semua ruangan dan lantai</summary><div class="chart-data-scroll"><table><thead><tr><th scope="col">Ruangan</th><th scope="col">Gedung / lantai</th><th scope="col">Aset</th></tr></thead><tbody>@foreach($roomStats as $room)<tr><th scope="row">{{ $room->nama_ruangan }}</th><td>{{ $room->building }} / {{ $room->lantai }}</td><td>{{ $room->assets_count }}</td></tr>@endforeach</tbody></table><p class="chart-footnote">Per lantai: @forelse($floorStats as $floor) Lantai {{ $floor->lantai }} ({{ $floor->assets_count }}){{ $loop->last ? '.' : ' ·' }} @empty Belum ada data. @endforelse</p></div></details>
        </section>
        <section class="ops-surface dashboard-card" aria-labelledby="dashboard-rfid">
            <header class="dashboard-card-header"><div><h2 id="dashboard-rfid">Pemasangan RFID</h2><p>Tag yang sudah terhubung ke aset</p></div></header>
            <div class="rfid-coverage"><strong>{{ $tagPercent }}<small>%</small></strong><span>{{ $rfidStats['with_rfid'] }} dari {{ $stats['total'] }} aset memiliki tag</span></div>
            <div class="rfid-progress" role="progressbar" aria-label="Persentase aset memiliki RFID" aria-valuenow="{{ $tagPercent }}" aria-valuemin="0" aria-valuemax="100"><span style="width:{{ $tagPercent }}%"></span></div>
            <dl class="rfid-summary"><div><dt>Tag aktif</dt><dd>{{ $rfidStats['rfid_aktif'] }}</dd></div><div><dt>Belum memiliki tag</dt><dd>{{ $rfidStats['without_rfid'] }}</dd></div></dl>
            <a class="ops-button ops-button-secondary rfid-setup-link" href="{{ route('assets.index', ['quick_filter' => 'no_rfid']) }}">Hubungkan RFID ke aset <span aria-hidden="true">→</span></a>
            <div class="rfid-quick-guide"><strong>Baru memasang tag?</strong><p>Pilih aset → Hubungkan RFID → tempelkan tag ke Pi dalam mode <b>Cek</b> → simpan.</p><a href="{{ route('assets.rfid.validate') }}">Periksa tag yang sudah terpasang →</a></div>
        </section>
    </div>
    <section class="ops-surface dashboard-card" aria-labelledby="dashboard-activity">
        <header class="dashboard-card-header"><div><h2 id="dashboard-activity">Aktivitas terbaru</h2><p>Perubahan terakhir pada inventaris</p></div><a href="{{ route('assets.history') }}">Semua aktivitas →</a></header>
        <div class="activity-table-wrap"><table class="dashboard-activity-table"><thead><tr><th scope="col">Aktivitas / aset</th><th scope="col">Oleh</th><th scope="col">Waktu</th></tr></thead><tbody>@forelse($recentHistories as $history)<tr><td><span class="activity-event">{{ $historyLabel[$history->action] ?? 'Aktivitas aset' }}</span>@if($history->asset_id)<a href="{{ route('assets.show', $history->asset_id) }}">{{ $history->asset_name }}</a>@else<span>{{ $history->asset_name }}</span>@endif</td><td>{{ $history->changed_by_name }}</td><td><time datetime="{{ $history->created_at->toAtomString() }}">{{ $history->created_at->locale('id')->diffForHumans() }}</time></td></tr>@empty<tr><td colspan="3" class="dashboard-empty">Belum ada aktivitas. Mulai dengan menambahkan aset pertama.</td></tr>@endforelse</tbody></table></div>
    </section>
    <p class="dashboard-data-note">Data inventaris saat halaman dimuat · {{ now()->locale('id')->translatedFormat('d F Y, H:i') }} ({{ config('app.timezone') }})</p>
</div>
@endsection
