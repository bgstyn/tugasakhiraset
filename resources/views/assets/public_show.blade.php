@extends('layouts.public')

@section('title', $asset->name . ' - Inventaris TI')

@section('content')
@php
    $status = match($asset->status) {
        'standby' => 'Siap dipakai',
        'digunakan' => 'Sedang dipakai',
        'maintenance' => 'Dalam perbaikan',
        'rusak' => 'Rusak',
        'fraud' => 'Hilang / investigasi',
        'write_off' => 'Dihapus dari inventaris',
        default => ucfirst(str_replace('_', ' ', $asset->status)),
    };
    $location = collect([$asset->building, $asset->floor ? 'Lantai '.$asset->floor : null, $asset->room])->filter()->join(' / ');
@endphp
<article class="ops-surface ops-public-panel">
    <header>
        <h1>{{ $asset->name }}</h1>
        <p>{{ $asset->asset_id }}</p>
    </header>

    @if($asset->photo)
        <img src="{{ asset($asset->photo) }}" class="ops-asset-photo" alt="Foto {{ $asset->name }}">
    @endif

    <dl class="scanner-details ops-public-meta">
        <div><dt>Status</dt><dd>{{ $status }}</dd></div>
        <div><dt>No. inventaris</dt><dd>{{ $asset->government_inventory_number }}</dd></div>
        <div><dt>Nomor seri</dt><dd>{{ $asset->serial_number ?: '—' }}</dd></div>
        <div><dt>Kategori</dt><dd>{{ $asset->category ?: '—' }}</dd></div>
        <div><dt>Merek dan model</dt><dd>{{ trim($asset->brand . ' ' . $asset->model) ?: '—' }}</dd></div>
        <div><dt>Lokasi</dt><dd>{{ $location ?: '—' }}</dd></div>
    </dl>

    <a href="{{ route('tickets.public.create', $asset->id, false) }}" class="ops-button ops-button-primary">Laporkan kerusakan</a>
</article>
@endsection
