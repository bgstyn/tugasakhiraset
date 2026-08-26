@extends('layouts.layout')

@section('title', 'Daftar Tiket Perbaikan - IT Asset Management')
@section('page_title', 'Manajemen Tiket Perbaikan')

@section('content')
<div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg">
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-base md:text-lg font-bold text-white">Daftar Tiket Maintenance</h3>
            <p class="text-xs text-slate-400 mt-1">Kelola perbaikan, klaim penugasan teknisi, dan pantau progres kerusakan aset IT JTI.</p>
        </div>
    </div>

    {{-- Filter & Search Form --}}
    <form action="{{ route('tickets.index') }}" method="GET" class="mb-6 bg-slate-950/40 p-4 border border-slate-800/80 rounded-xl space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Search input --}}
            <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}"
                    class="w-full pl-9 pr-4 py-2.5 bg-slate-950 border border-slate-800 focus:border-indigo-500 rounded-xl text-slate-200 placeholder-slate-500 outline-none text-xs transition"
                    placeholder="Cari Tiket/Aset/Pelapor...">
                <div class="absolute left-3 top-3 text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            {{-- Status filter --}}
            <div>
                <select name="status" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 focus:border-indigo-500 rounded-xl text-slate-300 text-xs outline-none cursor-pointer transition">
                    <option value="">Semua Status</option>
                    @foreach([
                        'open' => 'Open',
                        'assigned' => 'Assigned',
                        'in_progress' => 'In Progress',
                        'waiting_sparepart' => 'Waiting Sparepart',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled'
                    ] as $val => $label)
                        <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Priority filter --}}
            <div>
                <select name="priority" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 focus:border-indigo-500 rounded-xl text-slate-300 text-xs outline-none cursor-pointer transition">
                    <option value="">Semua Prioritas</option>
                    @foreach([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical'
                    ] as $val => $label)
                        <option value="{{ $val }}" {{ request('priority') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-750 text-white rounded-xl text-xs font-semibold transition cursor-pointer">
                    Cari & Filter
                </button>
                <a href="{{ route('tickets.index') }}" class="py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-xs font-semibold transition flex items-center justify-center cursor-pointer">
                    Reset
                </a>
            </div>
        </div>
    </form>

    {{-- Tickets Table --}}
    @if($tickets->isEmpty())
        <div class="text-center py-16 text-slate-500 border border-dashed border-slate-800 rounded-xl bg-slate-950/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-800 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p class="text-sm">Tidak ada tiket perbaikan yang terdaftar.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-slate-800">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-950/60 border-b border-slate-800 text-[10px] uppercase font-bold tracking-wider text-slate-400">
                        <th class="p-4">Nomor Tiket</th>
                        <th class="p-4">Aset</th>
                        <th class="p-4">Pelapor</th>
                        <th class="p-4">Prioritas</th>
                        <th class="p-4">Teknisi</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-xs">
                    @foreach($tickets as $ticket)
                        <tr class="hover:bg-slate-900/10 transition">
                            <td class="p-4 font-mono font-semibold text-slate-200">{{ $ticket->ticket_number }}</td>
                            <td class="p-4">
                                <span class="font-bold text-slate-200 block">{{ $ticket->asset->name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $ticket->asset->asset_id }}</span>
                            </td>
                            <td class="p-4">
                                <span class="text-slate-200 font-medium block">{{ $ticket->reporter_name }}</span>
                                <span class="text-[10px] text-slate-500 block">{{ $ticket->reporter_contact ?? '-' }}</span>
                            </td>
                            <td class="p-4">
                                @if($ticket->priority === 'critical')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-500/10 border border-red-500/20 text-red-400">CRITICAL</span>
                                @elseif($ticket->priority === 'high')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-orange-500/10 border border-orange-500/20 text-orange-400">HIGH</span>
                                @elseif($ticket->priority === 'medium')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 border border-amber-500/20 text-amber-300">MEDIUM</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-500/10 border border-slate-500/20 text-slate-400">LOW</span>
                                @endif
                            </td>
                            <td class="p-4">
                                @if($ticket->assignedTechnician)
                                    <span class="font-medium text-slate-350 block">{{ $ticket->assignedTechnician->name }}</span>
                                @else
                                    <span class="text-slate-500 italic block">Belum Ditugaskan</span>
                                @endif
                            </td>
                            <td class="p-4">
                                @if($ticket->status === 'open')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-500/15 border border-sky-500/20 text-sky-400 uppercase">Open</span>
                                @elseif($ticket->status === 'assigned')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/15 border border-purple-500/20 text-purple-400 uppercase">Assigned</span>
                                @elseif($ticket->status === 'in_progress')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/15 border border-blue-500/20 text-blue-450 uppercase">In Progress</span>
                                @elseif($ticket->status === 'waiting_sparepart')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/15 border border-amber-500/20 text-amber-400 uppercase">Waiting Sparepart</span>
                                @elseif($ticket->status === 'completed')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/15 border border-emerald-500/20 text-emerald-400 uppercase">Completed</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/15 border border-rose-500/20 text-rose-450 uppercase">Cancelled</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="px-3 py-1.5 bg-indigo-600/10 hover:bg-indigo-600/20 border border-indigo-500/25 text-indigo-400 rounded-lg font-semibold transition cursor-pointer">
                                    Detail & Aksi
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
@endsection
