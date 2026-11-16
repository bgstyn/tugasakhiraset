@extends('layouts.public')

@section('title', 'Laporan Diterima - Inventaris TI')

@section('content')
<section class="ops-surface ops-public-panel">
    <header>
        <h1>Laporan diterima</h1>
        <p>Simpan nomor tiket untuk rujukan saat menghubungi teknisi.</p>
    </header>
    <dl class="scanner-details ops-public-meta">
        <div><dt>Nomor tiket</dt><dd class="select-all">{{ $ticket->ticket_number }}</dd></div>
        <div><dt>Aset</dt><dd>{{ $ticket->asset->name }} · {{ $ticket->asset->asset_id }}</dd></div>
        <div><dt>Pelapor</dt><dd>{{ $ticket->reporter_name }}</dd></div>
        <div><dt>Prioritas</dt><dd>{{ ['low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi', 'critical' => 'Kritis'][$ticket->priority] ?? $ticket->priority }}</dd></div>
    </dl>
    <a href="{{ $ticket->asset->publicQrUrl() }}" class="ops-button ops-button-secondary">Kembali ke aset</a>
</section>
@endsection
