@extends('layouts.layout')

@section('title', 'Tambah Bundle - IT Asset Management')
@section('page_title', 'Tambah Asset Bundle')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 animate-[fadeIn_0.4s_ease-out_forwards]">

    {{-- Back --}}
    <a href="{{ route('bundles.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali ke Daftar Bundle
    </a>

    <form action="{{ route('bundles.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Basic Info Card --}}
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-5">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Informasi Bundle
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Name --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                        Nama Bundle <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="200"
                        placeholder="Contoh: 1 Set Komputer Lab Jaringan 01"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500/60 focus:ring-1 focus:ring-indigo-500/30 transition @error('name') border-rose-500/60 @enderror">
                    @error('name')<p class="text-rose-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Kode Bundle (auto-generated, readonly) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                        Kode Bundle
                        <span class="ml-1 text-slate-600 normal-case font-normal">(otomatis)</span>
                    </label>
                    <div class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-500 text-sm font-mono flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        BDL-XXXX (digenerate otomatis)
                    </div>
                </div>

                {{-- Location --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Lokasi Utama</label>
                    <select name="location_id"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-200 text-sm focus:outline-none focus:border-indigo-500/60 focus:ring-1 focus:ring-indigo-500/30 transition">
                        <option value="">-- Tidak Ditentukan --</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                Lantai {{ $loc->lantai }} – {{ $loc->kode_ruangan }} ({{ $loc->nama_ruangan }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Description --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Deskripsi</label>
                    <textarea name="description" rows="2" maxlength="1000"
                        placeholder="Deskripsi singkat mengenai bundle ini (opsional)..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500/60 focus:ring-1 focus:ring-indigo-500/30 resize-none transition">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Asset Selector Card --}}
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Pilih Aset
                    <span id="selectedCount" class="inline-flex items-center justify-center min-w-[22px] h-5 px-1.5 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-[10px] font-bold">0</span>
                </h2>
                {{-- Search filter --}}
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" id="assetSearch" placeholder="Filter aset..."
                        class="pl-9 pr-4 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500/60 transition w-52">
                </div>
            </div>

            @if($assets->isEmpty())
                <p class="text-slate-500 text-sm text-center py-8">Belum ada aset yang terdaftar di sistem.</p>
            @else
                <div class="space-y-4 max-h-[480px] overflow-y-auto pr-1 custom-scrollbar" id="assetList">
                    @foreach($assets as $category => $categoryAssets)
                    <div class="asset-category-group">
                        {{-- Category Header --}}
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $category ?: 'Lainnya' }}</span>
                            <div class="flex-1 h-px bg-slate-800"></div>
                            <button type="button" onclick="selectCategory('{{ addslashes($category) }}')"
                                class="text-[10px] text-indigo-400 hover:text-indigo-300 font-medium transition cursor-pointer">Pilih Semua</button>
                        </div>
                        {{-- Assets in category --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($categoryAssets as $asset)
                            <label for="asset_{{ $asset->id }}"
                                class="asset-item flex items-start gap-3 p-3 rounded-xl border border-slate-800 hover:border-indigo-500/30 hover:bg-slate-800/40 cursor-pointer transition group has-[:checked]:bg-indigo-500/5 has-[:checked]:border-indigo-500/30"
                                data-name="{{ strtolower($asset->name . ' ' . $asset->inventory_code . ' ' . $asset->category) }}"
                                data-category="{{ addslashes($category) }}">
                                <input type="checkbox" id="asset_{{ $asset->id }}" name="asset_ids[]" value="{{ $asset->id }}"
                                    {{ in_array($asset->id, old('asset_ids', [])) ? 'checked' : '' }}
                                    class="mt-0.5 h-4 w-4 rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500/30 cursor-pointer"
                                    onchange="updateSelectedCount()">
                                <div class="overflow-hidden flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-200 group-hover:text-white transition leading-snug truncate">{{ $asset->name }}</p>
                                    <p class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $asset->inventory_code }}</p>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-400">{{ $asset->category }}</span>
                                        @php
                                            $statusClasses = [
                                                'standby'                  => 'bg-emerald-500/10 text-emerald-400',
                                                'digunakan'                => 'bg-blue-500/10 text-blue-400',
                                                'maintenance'              => 'bg-amber-500/10 text-amber-400',
                                                'rusak'                    => 'bg-rose-500/10 text-rose-400',
                                                'fraud'                    => 'bg-red-500/10 text-red-400',
                                                'write_off'                => 'bg-slate-500/10 text-slate-400',
                                                'pending_fraud_approval'   => 'bg-amber-500/10 text-amber-300',
                                                'pending_write_off_approval'=> 'bg-amber-500/10 text-amber-300',
                                            ];
                                            $cls = $statusClasses[$asset->status] ?? 'bg-slate-500/10 text-slate-400';
                                        @endphp
                                        <span class="text-[10px] px-1.5 py-0.5 rounded {{ $cls }}">{{ str_replace('_', ' ', $asset->status) }}</span>
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Submit --}}
        <div class="flex gap-3 justify-end">
            <a href="{{ route('bundles.index') }}" class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-sm font-semibold transition">Batal</a>
            <button type="submit" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-bold transition shadow-md shadow-indigo-600/20 cursor-pointer flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Simpan Bundle
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function updateSelectedCount() {
    const count = document.querySelectorAll('input[name="asset_ids[]"]:checked').length;
    document.getElementById('selectedCount').textContent = count;
}

function selectCategory(category) {
    document.querySelectorAll('.asset-item').forEach(item => {
        if (item.dataset.category === category) {
            const cb = item.querySelector('input[type="checkbox"]');
            cb.checked = true;
        }
    });
    updateSelectedCount();
}

document.getElementById('assetSearch').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    document.querySelectorAll('.asset-item').forEach(item => {
        const match = !query || item.dataset.name.includes(query);
        item.style.display = match ? '' : 'none';
    });
    // Hide empty category groups
    document.querySelectorAll('.asset-category-group').forEach(group => {
        const visible = Array.from(group.querySelectorAll('.asset-item')).some(i => i.style.display !== 'none');
        group.style.display = visible ? '' : 'none';
    });
});

// Init count
updateSelectedCount();
</script>
@endsection
