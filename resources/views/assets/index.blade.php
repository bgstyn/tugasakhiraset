@extends('layouts.layout')

@section('title', 'Daftar Aset - IT Asset Management')
@section('page_title', 'Daftar Aset IT')

@section('content')
<div class="ops-surface ops-inventory-panel">
    <div class="ops-inventory-toolbar">
        <div class="ops-inline-actions">
            <a href="{{ route('assets.create') }}" class="ops-button ops-button-primary">Tambah aset</a>
            <a href="{{ route('assets.export') }}" class="ops-button ops-button-secondary">Unduh CSV</a>
        </div>

        <form action="{{ route('assets.index') }}" method="GET" class="ops-inventory-filters" aria-label="Pencarian dan filter aset">
            @if(request()->filled('quick_filter'))
                <input type="hidden" name="quick_filter" value="{{ request('quick_filter') }}">
            @endif
            <div class="ops-field ops-inventory-search">
                <label for="asset-search">Cari aset</label>
                <input type="search" id="asset-search" name="search" value="{{ request('search') }}" placeholder="Nama, kode, nomor seri, atau penanggung jawab">
            </div>
            <div class="ops-field">
                <label for="asset-status">Status</label>
                <select id="asset-status" name="status">
                    <option value="">Semua status</option>
                    @foreach(['standby' => 'Standby', 'digunakan' => 'Digunakan', 'maintenance' => 'Dalam perbaikan', 'rusak' => 'Rusak', 'fraud' => 'Hilang / investigasi', 'write_off' => 'Write off'] as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ops-field">
                <label for="asset-location">Lokasi</label>
                <select id="asset-location" name="location_id">
                    <option value="">Semua lokasi</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(request('location_id') == $loc->id)>{{ $loc->kode_ruangan }} - {{ $loc->nama_ruangan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ops-field">
                <label for="asset-sort">Urutkan</label>
                <select id="asset-sort" name="sort_by">
                    @foreach(['terbaru' => 'Terbaru', 'terlama' => 'Terlama', 'nama_asc' => 'Nama A–Z', 'nama_desc' => 'Nama Z–A', 'inv_code' => 'Kode aset', 'status' => 'Status'] as $key => $label)
                        <option value="{{ $key }}" @selected(request('sort_by', 'terbaru') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ops-inline-actions">
                <button type="submit" class="ops-button ops-button-secondary">Terapkan filter</button>
                @if(request()->anyFilled(['search', 'status', 'location_id', 'quick_filter', 'sort_by']))
                    <a href="{{ route('assets.index') }}" class="ops-button ops-button-secondary">Hapus filter</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Quick Filter Pills -->
    <div class="flex flex-wrap items-center gap-2 mb-6 text-xs">
        @php
            $activeFilter = request('quick_filter');
            $filtersList = [
                '' => 'Semua Asset',
                'terbaru' => 'Terbaru',
                'hari_ini' => 'Hari Ini',
                '7_hari' => '7 Hari Terakhir',
                '30_hari' => '30 Hari Terakhir',
                'no_rfid' => 'Belum Aktivasi RFID',
                'pending_approval' => 'Pending Approval',
                'pending_fraud' => 'Fraud Pending',
                'pending_write_off' => 'Write Off Pending',
            ];
        @endphp

        @foreach($filtersList as $val => $label)
            <a href="{{ route('assets.index', array_merge(request()->except('quick_filter', 'page'), $val !== '' ? ['quick_filter' => $val] : [])) }}"
                class="px-3.5 py-2 rounded-full border transition font-medium whitespace-nowrap {{ $activeFilter == $val || ($val === '' && !$activeFilter) ? 'bg-[#4f46e5]/15 border-indigo-500 text-indigo-300 shadow-md shadow-indigo-600/10' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-900' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Table -->
    <div class="ops-inventory-scroll overflow-x-auto rounded-xl border border-slate-800 bg-slate-950/50" role="region" aria-label="Hasil pencarian aset" tabindex="0">
        <table aria-label="Daftar aset" role="table" class="ops-inventory-table w-full min-w-[920px] text-left border-collapse">
            <thead role="rowgroup">
                <tr role="row" class="bg-slate-900/60 border-b border-slate-800 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    <th scope="col" role="columnheader" class="py-4 px-6">ID Asset TI</th>
                    <th scope="col" role="columnheader" class="py-4 px-6">No. Inventaris / SN</th>
                    <th scope="col" role="columnheader" class="py-4 px-6">Nama Aset</th>
                    <th scope="col" role="columnheader" class="py-4 px-6">Penanggung Jawab</th>
                    <th scope="col" role="columnheader" class="py-4 px-6">Lokasi</th>
                    <th scope="col" role="columnheader" class="py-4 px-6">Tahun</th>
                    <th scope="col" role="columnheader" class="py-4 px-6 text-center">Status</th>
                    <th scope="col" role="columnheader" class="py-4 px-6 text-center">QR Code</th>
                    <th scope="col" role="columnheader" class="py-4 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody role="rowgroup" class="divide-y divide-slate-800/60 text-sm text-slate-300">
                @forelse($assets as $asset)
                    @php
                        $isNew = $asset->created_at && $asset->created_at->gt(now()->subHours(24));
                        $isHighlighted = request('highlight_id') == $asset->id;
                    @endphp
                    <tr role="row" id="asset-row-{{ $asset->id }}"
                        class="transition {{ $isHighlighted ? 'bg-indigo-950/30 border-l-4 border-l-indigo-500 hover:bg-indigo-900/20' : ($isNew ? 'bg-indigo-950/10 border-l-4 border-l-indigo-500/50 hover:bg-indigo-900/10' : 'hover:bg-slate-900/40') }}">
                        <td role="cell" data-label="Kode aset" class="py-4 px-6 font-mono text-xs text-indigo-300 font-semibold">
                            {{ $asset->asset_id }}
                        </td>
                        <td role="cell" data-label="No. inventaris / seri" class="py-4 px-6">
                            <div class="text-xs text-slate-200 font-mono">{{ $asset->government_inventory_number }}</div>
                            @if($asset->serial_number)
                                <div class="text-[10px] text-slate-500 font-mono mt-0.5">SN: {{ $asset->serial_number }}</div>
                            @endif
                        </td>
                        <td role="cell" data-label="Nama aset" class="py-4 px-6">
                            <div class="font-medium text-white flex items-center flex-wrap gap-1.5">
                                <a href="{{ route('assets.show', $asset->id) }}" class="hover:underline">{{ $asset->name }}</a>
                                @if($isNew)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-bold bg-indigo-500 text-white uppercase tracking-wider align-middle leading-none animate-pulse">BARU</span>
                                @endif
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                <span class="text-indigo-400 font-semibold">{{ $asset->category }}</span> &middot; {{ $asset->brand }} {{ $asset->model }}
                            </div>
                        </td>
                        <td role="cell" data-label="Penanggung jawab" class="py-4 px-6">
                            {{ $asset->current_user ?? '-' }}
                        </td>
                        <td role="cell" data-label="Lokasi" class="py-4 px-6">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-800 border border-slate-700 text-slate-300 uppercase">
                                {{ $asset->room ?? '-' }}
                            </span>
                        </td>
                        <td role="cell" data-label="Tahun" class="py-4 px-6 text-slate-400">
                            {{ $asset->year }}
                        </td>
                        <td role="cell" data-label="Status" class="py-4 px-6 text-center">
                            @if($asset->status == 'standby')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 border border-emerald-500/25 text-emerald-400">
                                    <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full mr-1.5"></span>
                                    Standby
                                </span>
                            @elseif($asset->status == 'digunakan')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-500/10 border border-blue-500/25 text-blue-400">
                                    <span class="w-1.5 h-1.5 bg-blue-400 rounded-full mr-1.5"></span>
                                    Digunakan
                                </span>
                            @elseif($asset->status == 'maintenance')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-500/10 border border-amber-500/25 text-amber-400">
                                    <span class="w-1.5 h-1.5 bg-amber-400 rounded-full mr-1.5"></span>
                                    Maintenance
                                </span>
                            @elseif($asset->status == 'rusak')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-500/10 border border-rose-500/25 text-rose-400">
                                    <span class="w-1.5 h-1.5 bg-rose-400 rounded-full mr-1.5"></span>
                                    Rusak
                                </span>
                            @elseif($asset->status == 'fraud')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 border border-red-500/25 text-red-400">
                                    <span class="w-1.5 h-1.5 bg-red-400 rounded-full mr-1.5"></span>
                                    Fraud / Hilang
                                </span>
                            @elseif($asset->status === 'write_off')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-500/10 border border-slate-500/25 text-slate-400">
                                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-1.5"></span>
                                    Write Off
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-500/10 border border-amber-500/25 text-amber-300">
                                    {{ match($asset->status) {
                                        'pending_fraud_approval' => 'Menunggu persetujuan investigasi',
                                        'pending_write_off_approval' => 'Menunggu persetujuan penghapusan',
                                        default => ucfirst(str_replace('_', ' ', $asset->status)),
                                    } }}
                                </span>
                            @endif
                        </td>
                        <td role="cell" data-label="Label QR" class="py-4 px-6 text-center">
                            <div class="ops-asset-actions justify-center">
                                <!-- Preview QR Code -->
                                <button type="button" 
                                        onclick="showQrModal(this.dataset.code, this.dataset.png, this.dataset.svg)" data-code="{{ $asset->asset_id }}" data-png="{{ $asset->qr_png_url }}" data-svg="{{ $asset->qr_svg_url }}"
                                        class="p-1.5 bg-slate-900 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-800 text-indigo-400 hover:text-indigo-300 rounded-lg transition" 
                                        title="Preview QR Code">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                
                                <!-- Download PNG -->
                                <a href="{{ $asset->qr_png_url }}" download="{{ $asset->asset_id }}.png"
                                   class="p-1.5 bg-slate-900 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition flex items-center justify-center" 
                                   title="Unduh PNG">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                                
                                <!-- Download SVG -->
                                <a href="{{ $asset->qr_svg_url }}" download="{{ $asset->asset_id }}.svg"
                                   class="p-1.5 bg-slate-900 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition flex items-center justify-center" 
                                   title="Unduh SVG">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M8 8a4 4 0 118 0v4h1v-4a5 5 0 00-10 0v4h1V8z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16h16M12 12v4m-3-3l3 3 3-3" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                        <td role="cell" data-label="Aksi" class="py-4 px-6 text-right">
                            <div class="ops-asset-actions justify-end">
                                <!-- View -->
                                <a href="{{ route('assets.show', $asset->id) }}" class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition" title="Detail Aset">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <!-- Edit -->
                                <a href="{{ route('assets.edit', $asset->id) }}" class="p-1.5 text-slate-400 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition" title="Edit Aset">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <!-- Delete -->
                                <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus aset {{ $asset->name }}? Semua riwayat juga akan dilepas.')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition cursor-pointer" title="Hapus Aset">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-12 px-6 text-center text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-800 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p class="text-sm">Tidak ada aset IT yang ditemukan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $assets->links() }}
    </div>
</div>

<!-- Modal Preview QR Code -->
<div id="qr-preview-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/80 backdrop-blur-sm animate-[fadeIn_0.2s_ease-out]">
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl w-full max-w-xs p-5 shadow-2xl relative space-y-4">
        <button onclick="closeQrModal()" class="absolute right-4 top-4 p-1 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        
        <div class="text-center space-y-1">
            <h4 class="font-bold text-white text-xs uppercase tracking-wider">Preview QR Code</h4>
            <p id="qr-modal-code" class="text-xs font-bold text-indigo-400 font-mono bg-indigo-500/10 px-2.5 py-0.5 rounded border border-indigo-500/20 inline-block">-</p>
        </div>
        
        <div class="p-3 bg-white rounded-xl shadow-inner max-w-[170px] mx-auto flex items-center justify-center">
            <img id="qr-modal-image" src="" alt="QR Code" class="w-full h-auto object-contain">
        </div>
        
        <div class="grid grid-cols-2 gap-2 pt-1.5 text-center">
            <a id="qr-modal-download-png" href="" download="" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition cursor-pointer flex items-center justify-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                PNG
            </a>
            <a id="qr-modal-download-svg" href="" download="" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white rounded-xl text-xs font-semibold transition cursor-pointer flex items-center justify-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16h16M12 12v4m-3-3l3 3 3-3" />
                </svg>
                SVG
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function showQrModal(code, pngUrl, svgUrl) {
        document.getElementById('qr-modal-code').textContent = code;
        document.getElementById('qr-modal-image').src = pngUrl;
        
        const dlPng = document.getElementById('qr-modal-download-png');
        dlPng.href = pngUrl;
        dlPng.download = `${code}.png`;
        
        const dlSvg = document.getElementById('qr-modal-download-svg');
        dlSvg.href = svgUrl;
        dlSvg.download = `${code}.svg`;
        
        document.getElementById('qr-preview-modal').classList.remove('hidden');
    }

    function closeQrModal() {
        document.getElementById('qr-preview-modal').classList.add('hidden');
    }

    // Close modal on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQrModal();
        }
    });
</script>

@if(request()->filled('highlight_id'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const row = document.getElementById('asset-row-{{ request('highlight_id') }}');
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Add temporary highlighting ring class
            row.classList.add('ring-2', 'ring-indigo-500', 'ring-opacity-50');
            setTimeout(() => {
                row.classList.remove('ring-2', 'ring-indigo-500', 'ring-opacity-50');
            }, 3000);
        }
    });
</script>
@endif
@endsection
