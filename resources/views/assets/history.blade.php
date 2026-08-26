@extends('layouts.layout')

@section('title', 'Riwayat Aktivitas - IT Asset Management')
@section('page_title', 'Riwayat Perubahan & Aktivitas')

@section('content')
<div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h3 class="text-base md:text-lg font-bold text-white">Log Aktivitas Aset</h3>
            <p class="text-xs text-slate-400 mt-1">Daftar lengkap audit log aktivitas penambahan, pembaruan, dan penghapusan aset.</p>
        </div>
    </div>

    <!-- Timeline List -->
    @if($histories->isEmpty())
        <div class="text-center py-16 text-slate-500 border border-dashed border-slate-800 rounded-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-850 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm">Belum ada riwayat perubahan yang tercatat di sistem.</p>
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
                                <span class="h-10 w-10 shrink-0 rounded-xl flex items-center justify-center 
                                    @if($log->action == 'create') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @elseif($log->action == 'update') bg-blue-500/10 text-blue-400 border border-blue-500/20
                                    @else bg-rose-500/10 text-rose-400 border border-rose-500/20 @endif">
                                    
                                    @if($log->action == 'create')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    @elseif($log->action == 'update')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.2" />
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    @endif
                                </span>

                                <div class="space-y-1.5">
                                    <h4 class="text-sm font-semibold text-white">
                                        @if($log->action == 'create') Registrasi Aset Baru:
                                        @elseif($log->action == 'update') Pembaruan Aset:
                                        @else Penghapusan Aset: @endif
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
                                        Dilakukan oleh Staff: <span class="text-slate-300 font-semibold">{{ $log->changed_by_name }}</span> 
                                        ({{ $log->changed_by_position }} - <span class="uppercase">{{ $log->changed_by_location }}</span>)
                                    </p>

                                    <!-- JSON values details -->
                                    @if($log->action == 'update' && $log->new_values)
                                        <div class="text-xs text-slate-400 bg-slate-950/60 p-3 border border-slate-800/80 rounded-xl space-y-1 max-w-xl">
                                            @foreach($log->new_values as $key => $newVal)
                                                @php
                                                    $oldVal = $log->old_values[$key] ?? '-';
                                                @endphp
                                                @if($oldVal !== $newVal)
                                                    <div>
                                                        <span class="capitalize text-slate-500 font-mono">{{ $key }}:</span> 
                                                        <span class="line-through text-rose-500/90">{{ $oldVal ?? 'kosong' }}</span>
                                                        <span class="text-slate-500">→</span>
                                                        <span class="text-emerald-400 font-medium">{{ $newVal }}</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @elseif($log->action == 'create' && $log->new_values)
                                        <div class="text-xs text-slate-500 bg-slate-950/30 p-3 border border-slate-850 rounded-xl grid grid-cols-2 gap-x-4 gap-y-1 max-w-xl">
                                            @foreach($log->new_values as $key => $val)
                                                <div>
                                                    <span class="capitalize font-mono text-slate-600">{{ $key }}:</span> 
                                                    <span class="text-slate-400 font-medium">{{ $val ?? '-' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif($log->action == 'delete' && $log->old_values)
                                        <div class="text-xs text-slate-500 bg-slate-950/30 p-3 border border-slate-850 rounded-xl grid grid-cols-2 gap-x-4 gap-y-1 max-w-xl">
                                            @foreach($log->old_values as $key => $val)
                                                <div>
                                                    <span class="capitalize font-mono text-slate-600">{{ $key }}:</span> 
                                                    <span class="text-slate-400 line-through">{{ $val ?? '-' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Right block: Time -->
                            <div class="text-right shrink-0 md:self-start">
                                <span class="block text-xs font-semibold text-slate-400">{{ $log->created_at->format('d M Y, H:i') }}</span>
                                <span class="block text-[10px] text-slate-500 mt-1">{{ $log->created_at->diffForHumans() }}</span>
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
