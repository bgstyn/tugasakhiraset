@extends('layouts.layout')

@section('title', 'Asset Bundle - IT Asset Management')
@section('page_title', 'Asset Bundle')

@section('content')
<div class="space-y-6 animate-[fadeIn_0.4s_ease-out_forwards]">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white">Asset Bundle</h1>
            <p class="text-slate-400 text-sm mt-0.5">Kelola paket/set aset gabungan, misalnya "1 Set Komputer Lab".</p>
        </div>
        <a href="{{ route('bundles.create') }}"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold transition shadow-md shadow-indigo-600/20 w-fit">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Bundle
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-300 text-sm">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Search --}}
    <form method="GET" class="flex gap-3">
        <div class="relative flex-1 max-w-md">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Cari nama atau kode bundle..."
                class="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500/60 focus:ring-1 focus:ring-indigo-500/30 transition">
        </div>
        <button type="submit" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-sm font-medium transition cursor-pointer">Cari</button>
        @if(request('search'))
            <a href="{{ route('bundles.index') }}" class="px-4 py-2.5 bg-slate-800/50 hover:bg-slate-800 text-slate-400 hover:text-slate-300 rounded-xl text-sm transition">Reset</a>
        @endif
    </form>

    {{-- Bundle Grid --}}
    @if($bundles->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($bundles as $bundle)
            <div class="group bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 hover:border-indigo-500/30 hover:shadow-lg hover:shadow-indigo-500/5 transition-all duration-200 flex flex-col gap-4">
                {{-- Bundle Header --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 border border-indigo-500/25 flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div class="overflow-hidden">
                            <p class="font-mono text-[11px] text-indigo-400 font-semibold">{{ $bundle->code }}</p>
                            <h3 class="font-semibold text-slate-200 text-sm leading-snug truncate" title="{{ $bundle->name }}">{{ $bundle->name }}</h3>
                        </div>
                    </div>
                </div>

                {{-- Meta --}}
                <div class="space-y-1.5 text-xs text-slate-400">
                    <div class="flex items-center gap-2">
                        <svg class="h-3.5 w-3.5 text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>{{ $bundle->location ? $bundle->location->full_location : 'Lokasi tidak ditentukan' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-3.5 w-3.5 text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span><strong class="text-slate-200">{{ $bundle->assets_count }}</strong> aset dalam bundle</span>
                    </div>
                    @if($bundle->description)
                    <p class="text-slate-500 leading-relaxed line-clamp-2 pt-0.5">{{ $bundle->description }}</p>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 mt-auto pt-2 border-t border-slate-800/60">
                    <a href="{{ route('bundles.show', $bundle) }}"
                        class="flex-1 flex items-center justify-center gap-1.5 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 border border-indigo-500/20 text-indigo-400 rounded-xl text-xs font-semibold transition">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Detail
                    </a>
                    <a href="{{ route('bundles.edit', $bundle) }}"
                        class="flex-1 flex items-center justify-center gap-1.5 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </a>
                    <form action="{{ route('bundles.destroy', $bundle) }}" method="POST"
                        onsubmit="return confirm('Hapus bundle \'{{ $bundle->name }}\'? Aset individual tidak akan terhapus.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="px-3 py-2 bg-slate-800 hover:bg-rose-950/40 border border-slate-700 hover:border-rose-900 text-slate-500 hover:text-rose-400 rounded-xl text-xs transition cursor-pointer">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($bundles->hasPages())
            <div class="mt-2">{{ $bundles->links() }}</div>
        @endif

    @else
        {{-- Empty State --}}
        <div class="text-center py-20 bg-slate-900/40 border border-slate-800/60 rounded-2xl">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500/10 to-purple-500/10 border border-indigo-500/15 flex items-center justify-center mx-auto mb-5">
                <svg class="h-10 w-10 text-indigo-500/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <h3 class="text-slate-300 font-bold text-lg">Belum ada bundle</h3>
            <p class="text-slate-500 text-sm mt-2 mb-6">
                @if(request('search'))
                    Tidak ada bundle yang cocok dengan pencarian "<strong class="text-slate-400">{{ request('search') }}</strong>".
                @else
                    Buat bundle pertama untuk mengelompokkan aset menjadi satu set.
                @endif
            </p>
            @if(!request('search'))
                <a href="{{ route('bundles.create') }}"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold transition shadow-lg shadow-indigo-600/20">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Buat Bundle Pertama
                </a>
            @endif
        </div>
    @endif
</div>
@endsection
