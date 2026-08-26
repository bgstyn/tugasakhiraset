@extends('layouts.layout')

@section('title', 'Bulk Asset Berhasil Disimpan - IT Asset Management')
@section('page_title', 'Aset Berhasil Dibuat')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 animate-[fadeIn_0.4s_ease-out_forwards]">
    
    {{-- Success Hero Card --}}
    <div class="bg-slate-900 border border-emerald-500/20 rounded-2xl p-6 md:p-8 shadow-xl relative overflow-hidden text-center">
        <!-- Background decorative green blur -->
        <div class="absolute inset-0 bg-emerald-500/5 pointer-events-none"></div>
        <div class="absolute right-0 top-0 translate-x-12 -translate-y-12 w-36 h-36 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-16 h-16 bg-emerald-500/10 border border-emerald-500/25 rounded-2xl flex items-center justify-center mx-auto text-emerald-400 mb-4 shadow-lg shadow-emerald-500/5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2".5 d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h3 class="text-2xl font-bold text-white leading-tight">Pembuatan Aset Masal Berhasil!</h3>
        <p class="text-slate-400 text-sm mt-2 max-w-lg mx-auto">
            Sebanyak <strong class="text-emerald-400">{{ $assets->count() }} unit</strong> aset <strong class="text-white">"{{ $assets->first()->name }}"</strong> telah berhasil didaftarkan ke sistem dan ditempatkan pada <strong class="text-slate-200">{{ $assets->first()->building }}, Lantai {{ $assets->first()->floor }}, Ruangan {{ $assets->first()->room }}</strong>.
        </p>

        {{-- Actions --}}
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">

            
            <a href="{{ route('assets.index') }}"
                class="px-5 py-3 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition cursor-pointer">
                Daftar Aset Utama
            </a>

            <a href="{{ route('admin.assets.bulk.create') }}"
                class="px-5 py-3 bg-slate-950 hover:bg-slate-900 border border-slate-850 text-slate-400 hover:text-slate-200 rounded-xl text-xs font-semibold transition cursor-pointer">
                Buat Bulk Baru
            </a>
        </div>
    </div>

    {{-- Created Assets List Details --}}
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-5 border-b border-slate-800/60 bg-slate-950/20">
            <h4 class="font-bold text-white text-sm uppercase tracking-wider">Aset yang Baru Ditambahkan</h4>
        </div>
        <div class="divide-y divide-slate-850 max-h-[350px] overflow-y-auto">
            @foreach($assets as $index => $asset)
                <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-950/20 transition">
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500 font-mono font-medium">#{{ $index + 1 }}</span>
                        <div>
                            <span class="font-semibold text-white text-sm block">{{ $asset->name }}</span>
                            <span class="text-[11px] text-slate-500 block mt-0.5 font-mono">No. Inventaris: {{ $asset->government_inventory_number }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="px-2.5 py-1 bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 rounded font-mono text-xs font-semibold">
                            {{ $asset->asset_id }}
                        </span>
                        
                        <a href="{{ route('assets.show', $asset->id) }}" 
                            class="p-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-lg text-indigo-400 hover:text-indigo-300 transition"
                            title="Buka Detail Aset">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
