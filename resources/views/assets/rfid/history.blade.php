@extends('layouts.layout')

@section('title', 'Riwayat Pergantian RFID - IT Asset Management')
@section('page_title', 'Riwayat Aktivitas & Pergantian RFID')

@section('content')
<div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-base md:text-lg font-bold text-white">Log Aktivitas RFID</h3>
            <p class="text-xs text-slate-400 mt-1">Daftar lengkap audit log aktivitas penambahan, pergantian, pembaruan status, dan penghapusan RFID pada aset.</p>
        </div>
        <a href="{{ route('assets.rfid.validate') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition flex items-center gap-2 cursor-pointer w-fit">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Validasi RFID
        </a>
    </div>

    <!-- Timeline List -->
    @if($histories->isEmpty())
        <div class="text-center py-16 text-slate-500 border border-dashed border-slate-800 rounded-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-850 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm">Belum ada riwayat aktivitas RFID yang tercatat di sistem.</p>
        </div>
    @else
        <div class="space-y-6">
            <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950/40">
                <div class="divide-y divide-slate-800">
                    @foreach($histories as $log)
                        <div class="p-4 sm:p-5 hover:bg-slate-900/20 transition flex flex-col md:flex-row md:items-start justify-between gap-4">
                            <!-- Left block: Action and details -->
                            <div class="flex items-start gap-4">
                                <!-- Icon status -->
                                <span class="h-10 w-10 shrink-0 rounded-xl flex items-center justify-center bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 14v1m8-9h1M3 12h1m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l-.707.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                                    </svg>
                                </span>

                                <div class="space-y-1.5">
                                    <h4 class="text-sm font-semibold text-white">
                                        Aktivitas RFID pada Aset:
                                        <span class="text-indigo-400 font-bold font-sans">
                                            @if($log->asset_id)
                                                <a href="{{ route('assets.show', $log->asset_id) }}" class="hover:underline">{{ $log->asset_name }}</a>
                                            @else
                                                {{ $log->asset_name }}
                                            @endif
                                        </span>
                                    </h4>

                                    <!-- Author details -->
                                    <p class="text-xs text-slate-500">
                                        Dilakukan oleh: <span class="text-slate-300 font-semibold">{{ $log->changed_by_name }}</span> 
                                        ({{ $log->changed_by_position }} - <span class="uppercase">{{ $log->changed_by_location }}</span>)
                                    </p>

                                    <!-- Changes detailed list -->
                                    @if($log->new_values)
                                        <div class="text-xs text-slate-400 bg-slate-950/60 p-3 border border-slate-800/80 rounded-xl space-y-1.5 max-w-xl font-mono">
                                            @foreach($log->new_values as $key => $newVal)
                                                @php
                                                    $oldVal = $log->old_values[$key] ?? '-';
                                                @endphp
                                                @if($oldVal !== $newVal)
                                                    <div>
                                                        <span class="capitalize text-slate-500">{{ str_replace('_', ' ', $key) }}:</span> 
                                                        <span class="line-through text-rose-500/90 bg-rose-500/5 px-1.5 py-0.5 rounded border border-rose-500/10">{{ $oldVal ?: 'kosong' }}</span>
                                                        <span class="text-slate-500">→</span>
                                                        <span class="text-emerald-400 font-medium bg-emerald-500/5 px-1.5 py-0.5 rounded border border-emerald-500/10">{{ $newVal ?: 'kosong' }}</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Right block: Time -->
                            <div class="text-right shrink-0 md:self-start">
                                <span class="block text-xs font-semibold text-slate-400">{{ $log->created_at->format('d M Y, H:i') }}</span>
                                <span class="block text-[10px] text-slate-550 mt-1">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Pagination -->
            <div>
                {{ $histories->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
