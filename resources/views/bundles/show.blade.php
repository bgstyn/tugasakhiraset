@extends('layouts.layout')

@section('title', $bundle->name . ' - IT Asset Management')
@section('page_title', 'Detail Bundle')

@section('content')
<div class="space-y-6 animate-[fadeIn_0.4s_ease-out_forwards]">

    {{-- Top bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('bundles.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition w-fit">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Daftar Bundle
        </a>
        <div class="flex gap-2">
            <a href="{{ route('bundles.edit', $bundle) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit Bundle
            </a>
            <form action="{{ route('bundles.destroy', $bundle) }}" method="POST"
                onsubmit="return confirm('Hapus bundle ini? Aset individual tidak akan terpengaruh.')">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-rose-950/40 border border-slate-700 hover:border-rose-900 text-slate-400 hover:text-rose-400 rounded-xl text-xs font-semibold transition cursor-pointer">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus
                </button>
            </form>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-300 text-sm">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Bundle Hero Card --}}
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 md:p-8 relative overflow-hidden">
        <div class="absolute right-0 top-0 translate-x-6 -translate-y-6 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-0 bottom-0 -translate-x-6 translate-y-6 w-32 h-32 bg-purple-500/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-start gap-5">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-purple-600/20 border border-indigo-500/25 flex items-center justify-center shrink-0">
                <svg class="h-8 w-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="font-mono text-sm font-bold text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-1 rounded-lg">{{ $bundle->code }}</span>
                    <span class="text-xs px-2.5 py-1 bg-slate-800 border border-slate-700 text-slate-400 rounded-lg">
                        {{ $bundle->assets->count() }} Aset
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-white">{{ $bundle->name }}</h1>
                @if($bundle->description)
                    <p class="text-slate-400 text-sm mt-2 leading-relaxed">{{ $bundle->description }}</p>
                @endif
            </div>
        </div>

        {{-- Meta row --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 pt-6 border-t border-slate-800/60">
            <div>
                <span class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Lokasi</span>
                <p class="text-slate-200 text-sm mt-1 font-medium">
                    {{ $bundle->location ? $bundle->location->full_location : '—' }}
                </p>
            </div>
            <div>
                <span class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Dibuat</span>
                <p class="text-slate-200 text-sm mt-1">{{ $bundle->created_at->format('d M Y, H:i') }}</p>
            </div>
            <div>
                <span class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Terakhir Diperbarui</span>
                <p class="text-slate-200 text-sm mt-1">{{ $bundle->updated_at->diffForHumans() }}</p>
            </div>
        </div>
    </div>

    {{-- Asset Table --}}
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800/80">
            <h2 class="font-bold text-white flex items-center gap-2">
                <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Daftar Aset dalam Bundle
            </h2>
            <span class="text-xs text-slate-500">{{ $bundle->assets->count() }} item</span>
        </div>

        @if($bundle->assets->isEmpty())
            <div class="text-center py-14">
                <svg class="h-10 w-10 text-slate-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-slate-500 text-sm">Bundle ini belum memiliki aset.</p>
                <a href="{{ route('bundles.edit', $bundle) }}" class="inline-flex items-center gap-1.5 mt-3 text-indigo-400 hover:text-indigo-300 text-xs font-semibold transition">
                    Tambahkan Aset →
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-800/80 bg-slate-900/80">
                            <th class="text-left px-5 py-4 text-xs uppercase tracking-wider text-slate-500 font-semibold">No</th>
                            <th class="text-left px-5 py-4 text-xs uppercase tracking-wider text-slate-500 font-semibold">Aset</th>
                            <th class="text-left px-5 py-4 text-xs uppercase tracking-wider text-slate-500 font-semibold hidden md:table-cell">Kategori</th>
                            <th class="text-left px-5 py-4 text-xs uppercase tracking-wider text-slate-500 font-semibold hidden lg:table-cell">Lokasi</th>
                            <th class="text-left px-5 py-4 text-xs uppercase tracking-wider text-slate-500 font-semibold">Status</th>
                            <th class="text-right px-5 py-4 text-xs uppercase tracking-wider text-slate-500 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/50">
                        @foreach($bundle->assets as $i => $asset)
                        <tr class="hover:bg-slate-800/20 transition-colors duration-150">
                            <td class="px-5 py-4 text-slate-500 text-xs">{{ $i + 1 }}</td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-200">{{ $asset->name }}</p>
                                <p class="text-xs font-mono text-slate-500 mt-0.5">{{ $asset->asset_id }}</p>
                                @if($asset->brand || $asset->model)
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $asset->brand }} {{ $asset->model }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 hidden md:table-cell">
                                <span class="text-xs px-2 py-0.5 rounded bg-slate-800 border border-slate-700/60 text-slate-400">{{ $asset->category }}</span>
                            </td>
                            <td class="px-5 py-4 hidden lg:table-cell text-slate-400 text-xs">
                                {{ $asset->room ?? '—' }}
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $badges = [
                                        'standby'                   => ['cls' => 'bg-emerald-500/10 border-emerald-500/25 text-emerald-400', 'dot' => 'bg-emerald-400', 'label' => 'Standby'],
                                        'digunakan'                 => ['cls' => 'bg-blue-500/10 border-blue-500/25 text-blue-400',     'dot' => 'bg-blue-400',    'label' => 'Digunakan'],
                                        'maintenance'               => ['cls' => 'bg-amber-500/10 border-amber-500/25 text-amber-400',  'dot' => 'bg-amber-400',   'label' => 'Maintenance'],
                                        'rusak'                     => ['cls' => 'bg-rose-500/10 border-rose-500/25 text-rose-400',     'dot' => 'bg-rose-400',    'label' => 'Rusak'],
                                        'fraud'                     => ['cls' => 'bg-red-500/10 border-red-500/25 text-red-400',        'dot' => 'bg-red-400',     'label' => 'Fraud'],
                                        'write_off'                 => ['cls' => 'bg-slate-600/10 border-slate-600/25 text-slate-400',  'dot' => 'bg-slate-400',   'label' => 'Write Off'],
                                        'pending_fraud_approval'    => ['cls' => 'bg-amber-500/10 border-amber-500/25 text-amber-300',  'dot' => 'bg-amber-400',   'label' => '⏳ Pending Fraud'],
                                        'pending_write_off_approval'=> ['cls' => 'bg-amber-500/10 border-amber-500/25 text-amber-300',  'dot' => 'bg-amber-400',   'label' => '⏳ Pending W/O'],
                                    ];
                                    $b = $badges[$asset->status] ?? ['cls' => 'bg-slate-700 border-slate-600 text-slate-400', 'dot' => 'bg-slate-400', 'label' => $asset->status];
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-xs font-semibold {{ $b['cls'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $b['dot'] }}"></span>
                                    {{ $b['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('assets.show', $asset->id) }}"
                                    class="text-indigo-400 hover:text-indigo-300 text-xs font-medium hover:underline transition">
                                    Detail →
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
