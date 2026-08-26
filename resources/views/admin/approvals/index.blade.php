@extends('layouts.layout')

@section('title', 'Persetujuan Aset - IT Asset Management')
@section('page_title', 'Persetujuan Aset')

@section('content')
<div class="space-y-8 animate-[fadeIn_0.4s_ease-out_forwards]">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white">Persetujuan & Permohonan Aset</h1>
            <p class="text-slate-400 text-sm mt-0.5">Kelola persetujuan perubahan status Fraud, Write Off, dan Permohonan Replacement Aset.</p>
        </div>
        @if($pendingCount > 0)
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/10 border border-amber-500/25 text-amber-300 text-sm font-semibold">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                {{ $pendingCount }} Pengajuan Menunggu
            </span>
        @endif
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-300 text-sm shadow-md">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 p-4 rounded-xl bg-rose-500/10 border border-rose-500/25 text-rose-300 text-sm shadow-md">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Filter Tabs --}}
    <div class="flex gap-2 bg-slate-900/60 border border-slate-800/80 rounded-xl p-1.5 w-fit">
        <a href="{{ route('admin.approvals.index', ['filter' => 'pending']) }}"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $filter === 'pending' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/25' : 'text-slate-400 hover:text-slate-200' }}">
            🟡 Menunggu Persetujuan
        </a>
        <a href="{{ route('admin.approvals.index', ['filter' => 'all']) }}"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $filter === 'all' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/25' : 'text-slate-400 hover:text-slate-200' }}">
            Semua Riwayat
        </a>
    </div>

    {{-- ───────────────────────────────────────────────────────────────── --}}
    {{-- SECTION 1: FRAUD & WRITE OFF APPROVALS --}}
    {{-- ───────────────────────────────────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg space-y-4">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Persetujuan Status (Fraud / Write Off)
        </h3>
        
        <div class="bg-slate-950/40 rounded-xl overflow-hidden border border-slate-850">
            @if($approvals->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-slate-800/80 bg-slate-900/80 text-xs font-bold uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-4">Aset</th>
                                <th class="px-5 py-4">Jenis</th>
                                <th class="px-5 py-4 hidden md:table-cell">Pengaju</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850 text-xs">
                            @foreach($approvals as $approval)
                            <tr class="hover:bg-slate-900/20 transition duration-150">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-200">{{ $approval->asset_name }}</div>
                                    @if($approval->asset)
                                        <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ $approval->asset->asset_id }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if($approval->type === 'fraud')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 border border-rose-500/20 text-rose-455">🔴 Fraud</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-700/60 border border-slate-600/30 text-slate-300">⚫ Write Off</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 hidden md:table-cell">
                                    <div class="text-slate-300 font-medium">{{ $approval->requested_by_name }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $approval->requested_by_position }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    @if($approval->status === 'pending')
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 border border-amber-500/20 text-amber-300 uppercase">Menunggu</span>
                                    @elseif($approval->status === 'approved')
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 uppercase">Disetujui</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 border border-rose-500/20 text-rose-400 uppercase">Ditolak</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right space-x-2">
                                    <button onclick="openDetailModal({{ $approval->id }})" class="text-indigo-400 hover:text-indigo-300 transition cursor-pointer font-semibold">Detail</button>
                                    @if($approval->status === 'pending')
                                        <span class="text-slate-800">|</span>
                                        <button onclick="openApproveModal({{ $approval->id }}, '{{ addslashes($approval->asset_name) }}', '{{ $approval->type_label }}')"
                                            class="text-emerald-450 hover:text-emerald-350 transition cursor-pointer font-semibold">Setujui</button>
                                        <span class="text-slate-800">|</span>
                                        <button onclick="openRejectModal({{ $approval->id }}, '{{ addslashes($approval->asset_name) }}', '{{ $approval->type_label }}')"
                                            class="text-rose-450 hover:text-rose-350 transition cursor-pointer font-semibold">Tolak</button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($approvals->hasPages())
                    <div class="px-5 py-3 border-t border-slate-850">
                        {{ $approvals->links() }}
                    </div>
                @endif
            @else
                <p class="text-xs text-slate-500 text-center py-10">Tidak ada pengajuan persetujuan status.</p>
            @endif
        </div>
    </div>

    {{-- ───────────────────────────────────────────────────────────────── --}}
    {{-- SECTION 2: REPLACEMENT REQUESTS --}}
    {{-- ───────────────────────────────────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg space-y-4">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            Permohonan Penggantian Aset (Replacement)
        </h3>
        
        <div class="bg-slate-950/40 rounded-xl overflow-hidden border border-slate-850">
            @if($replacements->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-slate-800/80 bg-slate-900/80 text-xs font-bold uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-4">Aset</th>
                                <th class="px-5 py-4">Alasan Kerusakan / Penggantian</th>
                                <th class="px-5 py-4 hidden md:table-cell">Pemohon</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-855 text-xs">
                            @foreach($replacements as $rep)
                            <tr class="hover:bg-slate-900/20 transition duration-150">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-200">{{ $rep->asset->name }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ $rep->asset->asset_id }}</div>
                                </td>
                                <td class="px-5 py-4 max-w-xs truncate text-slate-350 font-serif">
                                    {{ $rep->reason }}
                                </td>
                                <td class="px-5 py-4 hidden md:table-cell">
                                    <div class="text-slate-300 font-medium">{{ $rep->requester->name }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $rep->requester->role }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    @if($rep->status === 'pending')
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 border border-amber-500/20 text-amber-300 uppercase">Menunggu</span>
                                    @elseif($rep->status === 'approved')
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 uppercase">Disetujui</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 border border-rose-500/20 text-rose-400 uppercase">Ditolak</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right space-x-2">
                                    <button onclick="openReplacementDetailModal({{ $rep->id }})" class="text-indigo-400 hover:text-indigo-300 transition cursor-pointer font-semibold">Detail</button>
                                    @if($rep->status === 'pending')
                                        <span class="text-slate-800">|</span>
                                        <button onclick="openApproveReplacementModal({{ $rep->id }}, '{{ addslashes($rep->asset->name) }}')"
                                            class="text-emerald-450 hover:text-emerald-350 transition cursor-pointer font-semibold">Setujui</button>
                                        <span class="text-slate-800">|</span>
                                        <button onclick="openRejectReplacementModal({{ $rep->id }}, '{{ addslashes($rep->asset->name) }}')"
                                            class="text-rose-450 hover:text-rose-350 transition cursor-pointer font-semibold">Tolak</button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($replacements->hasPages())
                    <div class="px-5 py-3 border-t border-slate-850">
                        {{ $replacements->links() }}
                    </div>
                @endif
            @else
                <p class="text-xs text-slate-500 text-center py-10">Tidak ada pengajuan replacement.</p>
            @endif
        </div>
    </div>

</div>

{{-- ====================================================== --}}
{{-- 1. Approvals Detail Modal --}}
{{-- ====================================================== --}}
<div id="detailModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeDetailModal()"></div>
    <div class="relative bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-slate-800">
            <h3 class="text-lg font-bold text-white">Detail Pengajuan Approval</h3>
            <button onclick="closeDetailModal()" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition cursor-pointer">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="detailModalBody" class="p-6 space-y-4 text-sm text-slate-300">
            Loading...
        </div>
    </div>
</div>

{{-- ====================================================== --}}
{{-- 2. Replacement Detail Modal --}}
{{-- ====================================================== --}}
<div id="repDetailModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeRepDetailModal()"></div>
    <div class="relative bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl w-full max-w-xl">
        <div class="flex items-center justify-between p-6 border-b border-slate-800">
            <h3 class="text-lg font-bold text-white">Detail Pengajuan Replacement</h3>
            <button onclick="closeRepDetailModal()" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition cursor-pointer">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="repDetailModalBody" class="p-6 space-y-4 text-sm text-slate-300">
            Loading...
        </div>
    </div>
</div>

{{-- ====================================================== --}}
{{-- 3. Approvals Approve Modal --}}
{{-- ====================================================== --}}
<div id="approveModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeApproveModal()"></div>
    <div class="relative bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mx-auto mb-4">
                <svg class="h-7 w-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-center text-lg font-bold text-white mb-1">Setujui Pengajuan?</h3>
            <p class="text-center text-slate-400 text-sm mb-6" id="approveModalDesc">Status aset akan diubah sesuai pengajuan.</p>
            <form id="approveForm" method="POST">
                @csrf @method('PATCH')
                <div class="flex gap-3">
                    <button type="button" onclick="closeApproveModal()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-sm font-medium transition cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-bold transition cursor-pointer">Ya, Setujui</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====================================================== --}}
{{-- 4. Approvals Reject Modal --}}
{{-- ====================================================== --}}
<div id="rejectModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeRejectModal()"></div>
    <div class="relative bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="w-14 h-14 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center mx-auto mb-4">
                <svg class="h-7 w-7 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h3 class="text-center text-lg font-bold text-white mb-1">Tolak Pengajuan</h3>
            <p class="text-center text-slate-400 text-sm mb-5" id="rejectModalDesc">Status aset akan dikembalikan ke sebelumnya.</p>
            <form id="rejectForm" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Alasan Penolakan <span class="text-rose-400">*</span></label>
                    <textarea name="rejection_reason" rows="3" required minlength="5" maxlength="500"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-rose-500/60 focus:ring-1 focus:ring-rose-500/30 resize-none transition"
                        placeholder="Tuliskan alasan penolakan..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectModal()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-sm font-medium transition cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-sm font-bold transition cursor-pointer">Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====================================================== --}}
{{-- 5. Replacement Approve Modal --}}
{{-- ====================================================== --}}
<div id="approveRepModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeApproveReplacementModal()"></div>
    <div class="relative bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mx-auto mb-4">
                <svg class="h-7 w-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-center text-lg font-bold text-white mb-1">Setujui Replacement?</h3>
            <p class="text-center text-slate-400 text-sm mb-4" id="approveRepModalDesc">Aset akan otomatis dipindahkan ke status Rusak.</p>
            <form id="approveRepForm" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Catatan Administrator <span class="text-slate-500 font-normal">(Opsional)</span></label>
                    <input type="text" name="notes" placeholder="Contoh: Disetujui karena tidak ada sparepart tersedia."
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition">
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeApproveReplacementModal()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-sm font-medium transition cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-bold transition cursor-pointer">Ya, Setujui</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ====================================================== --}}
{{-- 6. Replacement Reject Modal --}}
{{-- ====================================================== --}}
<div id="rejectRepModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeRejectReplacementModal()"></div>
    <div class="relative bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6">
            <div class="w-14 h-14 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center mx-auto mb-4">
                <svg class="h-7 w-7 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h3 class="text-center text-lg font-bold text-white mb-1">Tolak Replacement</h3>
            <p class="text-center text-slate-400 text-sm mb-4" id="rejectRepModalDesc">Tolak permohonan penggantian aset.</p>
            <form id="rejectRepForm" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Alasan Penolakan <span class="text-rose-400">*</span></label>
                    <textarea name="notes" rows="3" required minlength="5" maxlength="500"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-rose-500/60 focus:ring-1 focus:ring-rose-500/30 resize-none transition"
                        placeholder="Tuliskan alasan penolakan..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectReplacementModal()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-sm font-medium transition cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-sm font-bold transition cursor-pointer">Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const approvalsData = @json($approvals->items());
const replacementsData = @json($replacements->items());
const approveBaseUrl = "{{ url('/admin/approvals') }}";
const replacementBaseUrl = "{{ url('/admin/replacements') }}";

function openDetailModal(id) {
    const approval = approvalsData.find(a => a.id === id);
    if (!approval) return;

    const typeLabel = approval.type === 'fraud' ? '🔴 Fraud' : '⚫ Write Off';
    const statusBadge = {
        pending: '<span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/25 text-amber-300 text-xs">🟡 Menunggu</span>',
        approved: '<span class="px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/25 text-emerald-300 text-xs">✅ Disetujui</span>',
        rejected: '<span class="px-2 py-0.5 rounded bg-rose-500/10 border border-rose-500/25 text-rose-300 text-xs">❌ Ditolak</span>',
    }[approval.status];

    let docHtml = '<span class="text-slate-550 italic">Tidak ada dokumen</span>';
    if (approval.document_path) {
        const url = '/storage/' + approval.document_path;
        const isImage = /\.(jpg|jpeg|png)$/i.test(approval.document_path);
        if (isImage) {
            docHtml = `<a href="${url}" target="_blank"><img src="${url}" class="max-h-48 rounded-lg border border-slate-700 object-contain" alt="Dokumen Pengajuan"></a>`;
        } else {
            docHtml = `<a href="${url}" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-indigo-400 text-xs font-medium transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Lihat / Download PDF
            </a>`;
        }
    }

    let resultHtml = '';
    if (approval.status === 'approved') {
        resultHtml = `<div class="p-3 rounded-xl bg-emerald-500/5 border border-emerald-500/15"><span class="text-xs text-slate-500">Disetujui oleh</span><div class="text-emerald-300 font-semibold mt-0.5">${approval.approved_by}</div><div class="text-xs text-slate-500 mt-0.5">${approval.approved_at ? new Date(approval.approved_at).toLocaleString('id-ID') : '-'}</div></div>`;
    } else if (approval.status === 'rejected') {
        resultHtml = `<div class="p-3 rounded-xl bg-rose-500/5 border border-rose-500/15"><span class="text-xs text-slate-500">Ditolak oleh</span><div class="text-rose-300 font-semibold mt-0.5">${approval.rejected_by}</div><div class="text-xs text-slate-500 mt-0.5">${approval.rejected_at ? new Date(approval.rejected_at).toLocaleString('id-ID') : '-'}</div><div class="mt-2 text-slate-300 text-xs bg-slate-800/60 p-2 rounded-lg">${approval.rejection_reason}</div></div>`;
    }

    document.getElementById('detailModalBody').innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div><span class="text-xs text-slate-500 uppercase tracking-wider">Aset</span><div class="mt-1 font-semibold text-white">${approval.asset_name}</div></div>
            <div><span class="text-xs text-slate-500 uppercase tracking-wider">Jenis</span><div class="mt-1">${typeLabel}</div></div>
            <div><span class="text-xs text-slate-500 uppercase tracking-wider">Pengaju</span><div class="mt-1 text-slate-200">${approval.requested_by_name}</div><div class="text-xs text-slate-500">${approval.requested_by_position}</div></div>
            <div><span class="text-xs text-slate-500 uppercase tracking-wider">Tanggal</span><div class="mt-1 text-slate-200 text-xs">${new Date(approval.created_at).toLocaleString('id-ID')}</div></div>
            <div><span class="text-xs text-slate-500 uppercase tracking-wider">Status</span><div class="mt-1">${statusBadge}</div></div>
            <div><span class="text-xs text-slate-500 uppercase tracking-wider">Status Sebelumnya</span><div class="mt-1 text-slate-400 text-xs font-mono">${approval.previous_status}</div></div>
        </div>
        <div><span class="text-xs text-slate-500 uppercase tracking-wider">Alasan Pengajuan</span><div class="mt-1.5 p-3 bg-slate-800/60 rounded-xl text-slate-300 text-sm leading-relaxed">${approval.reason}</div></div>
        ${approval.notes ? `<div><span class="text-xs text-slate-500 uppercase tracking-wider">Catatan Tambahan</span><div class="mt-1.5 p-3 bg-slate-800/60 rounded-xl text-slate-400 text-sm">${approval.notes}</div></div>` : ''}
        <div><span class="text-xs text-slate-500 uppercase tracking-wider">Dokumen Surat</span><div class="mt-1.5">${docHtml}</div></div>
        ${resultHtml ? `<div>${resultHtml}</div>` : ''}
    `;

    document.getElementById('detailModal').classList.remove('hidden');
    document.getElementById('detailModal').classList.add('flex');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
    document.getElementById('detailModal').classList.remove('flex');
}

function openApproveModal(id, name, typeLabel) {
    document.getElementById('approveModalDesc').textContent = `Aset "${name}" akan diubah statusnya menjadi ${typeLabel}.`;
    document.getElementById('approveForm').action = `${approveBaseUrl}/${id}/approve`;
    document.getElementById('approveModal').classList.remove('hidden');
    document.getElementById('approveModal').classList.add('flex');
}

function closeApproveModal() {
    document.getElementById('approveModal').classList.add('hidden');
    document.getElementById('approveModal').classList.remove('flex');
}

function openRejectModal(id, name, typeLabel) {
    document.getElementById('rejectModalDesc').textContent = `Pengajuan ${typeLabel} untuk aset "${name}" akan ditolak.`;
    document.getElementById('rejectForm').action = `${approveBaseUrl}/${id}/reject`;
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}

// Replacement Modal actions
function openReplacementDetailModal(id) {
    const rep = replacementsData.find(r => r.id === id);
    if (!rep) return;

    const statusBadge = {
        pending: '<span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/25 text-amber-300 text-xs">🟡 Menunggu</span>',
        approved: '<span class="px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/25 text-emerald-300 text-xs">✅ Disetujui</span>',
        rejected: '<span class="px-2 py-0.5 rounded bg-rose-500/10 border border-rose-500/25 text-rose-300 text-xs">❌ Ditolak</span>',
    }[rep.status];

    let resultHtml = '';
    if (rep.status === 'approved') {
        resultHtml = `<div class="p-3 rounded-xl bg-emerald-500/5 border border-emerald-500/15"><span class="text-xs text-slate-500">Disetujui oleh</span><div class="text-emerald-300 font-semibold mt-0.5">${rep.resolver ? rep.resolver.name : 'Admin'}</div><div class="text-xs text-slate-500 mt-0.5">${rep.resolved_at ? new Date(rep.resolved_at).toLocaleString('id-ID') : '-'}</div>${rep.notes ? `<div class="mt-2 text-slate-350 text-xs bg-slate-800/60 p-2 rounded-lg">${rep.notes}</div>` : ''}</div>`;
    } else if (rep.status === 'rejected') {
        resultHtml = `<div class="p-3 rounded-xl bg-rose-500/5 border border-rose-500/15"><span class="text-xs text-slate-500">Ditolak oleh</span><div class="text-rose-300 font-semibold mt-0.5">${rep.resolver ? rep.resolver.name : 'Admin'}</div><div class="text-xs text-slate-500 mt-0.5">${rep.resolved_at ? new Date(rep.resolved_at).toLocaleString('id-ID') : '-'}</div><div class="mt-2 text-slate-300 text-xs bg-slate-800/60 p-2 rounded-lg">${rep.notes}</div></div>`;
    }

    document.getElementById('repDetailModalBody').innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div><span class="text-xs text-slate-500 uppercase tracking-wider">Aset</span><div class="mt-1 font-semibold text-white">${rep.asset.name}</div><div class="text-xs text-slate-500 font-mono">${rep.asset.asset_id}</div></div>
            <div><span class="text-xs text-slate-500 uppercase tracking-wider">Pengaju</span><div class="mt-1 text-slate-200">${rep.requester.name}</div><div class="text-[10px] text-slate-500">${rep.requester.role}</div></div>
            <div><span class="text-xs text-slate-500 uppercase tracking-wider">Tanggal</span><div class="mt-1 text-slate-200 text-xs">${new Date(rep.created_at).toLocaleString('id-ID')}</div></div>
            <div><span class="text-xs text-slate-500 uppercase tracking-wider">Status</span><div class="mt-1">${statusBadge}</div></div>
        </div>
        <div><span class="text-xs text-slate-500 uppercase tracking-wider">Alasan Penggantian (Replacement)</span><div class="mt-1.5 p-3 bg-slate-800/60 rounded-xl text-slate-300 text-sm leading-relaxed">${rep.reason}</div></div>
        ${resultHtml ? `<div class="pt-2">${resultHtml}</div>` : ''}
    `;

    document.getElementById('repDetailModal').classList.remove('hidden');
    document.getElementById('repDetailModal').classList.add('flex');
}

function closeRepDetailModal() {
    document.getElementById('repDetailModal').classList.add('hidden');
    document.getElementById('repDetailModal').classList.remove('flex');
}

function openApproveReplacementModal(id, name) {
    document.getElementById('approveRepForm').action = `${replacementBaseUrl}/${id}/approve`;
    document.getElementById('approveRepModal').classList.remove('hidden');
    document.getElementById('approveRepModal').classList.add('flex');
}

function closeApproveReplacementModal() {
    document.getElementById('approveRepModal').classList.add('hidden');
    document.getElementById('approveRepModal').classList.remove('flex');
}

function openRejectReplacementModal(id, name) {
    document.getElementById('rejectRepForm').action = `${replacementBaseUrl}/${id}/reject`;
    document.getElementById('rejectRepModal').classList.remove('hidden');
    document.getElementById('rejectRepModal').classList.add('flex');
}

function closeRejectReplacementModal() {
    document.getElementById('rejectRepModal').classList.add('hidden');
    document.getElementById('rejectRepModal').classList.remove('flex');
}
</script>
@endsection
