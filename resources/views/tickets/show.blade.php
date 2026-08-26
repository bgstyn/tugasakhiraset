@extends('layouts.layout')

@section('title', 'Detail Tiket #' . $ticket->ticket_number . ' - IT Asset Management')
@section('page_title', 'Detail Tiket Perbaikan')

@section('content')
<div class="space-y-6">
    <div class="mb-4">
        <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Daftar Tiket
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Left column: Ticket Info & Action forms --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Ticket General info --}}
            <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-800 pb-4 gap-3">
                    <div>
                        <span class="text-slate-500 text-[10px] uppercase font-bold tracking-widest block">Nomor Tiket</span>
                        <h3 class="text-xl font-bold text-white font-mono mt-0.5">{{ $ticket->ticket_number }}</h3>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        {{-- Status Badge --}}
                        @if($ticket->status === 'open')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-sky-500/15 border border-sky-500/20 text-sky-400 uppercase">Open</span>
                        @elseif($ticket->status === 'assigned')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-500/15 border border-purple-500/20 text-purple-400 uppercase">Assigned</span>
                        @elseif($ticket->status === 'in_progress')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/15 border border-blue-500/20 text-blue-450 uppercase">In Progress</span>
                        @elseif($ticket->status === 'waiting_sparepart')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/15 border border-amber-500/20 text-amber-400 uppercase">Waiting Sparepart</span>
                        @elseif($ticket->status === 'completed')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 border border-emerald-500/20 text-emerald-400 uppercase">Completed</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-500/15 border border-rose-500/20 text-rose-450 uppercase">Cancelled</span>
                        @endif

                        {{-- Priority Badge --}}
                        @if($ticket->priority === 'critical')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-500/10 border border-red-500/25 text-red-400 uppercase">CRITICAL</span>
                        @elseif($ticket->priority === 'high')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-500/10 border border-orange-500/25 text-orange-400 uppercase">HIGH</span>
                        @elseif($ticket->priority === 'medium')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/10 border border-amber-500/25 text-amber-300 uppercase">MEDIUM</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-500/10 border border-slate-500/25 text-slate-400 uppercase">LOW</span>
                        @endif
                    </div>
                </div>

                {{-- Damage Description --}}
                <div class="space-y-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Deskripsi Kerusakan</h4>
                    <div class="text-sm bg-slate-950 p-4 rounded-xl border border-slate-850 text-slate-350 leading-relaxed font-sans whitespace-pre-line">
                        {{ $ticket->description }}
                    </div>
                </div>

                {{-- Reporter Info --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-slate-950/40 p-4 rounded-xl border border-slate-850/80">
                    <div>
                        <span class="text-slate-500 block">Nama Pelapor</span>
                        <strong class="text-slate-300 text-sm block mt-0.5">{{ $ticket->reporter_name }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Email / No HP</span>
                        <strong class="text-slate-300 text-sm block mt-0.5">{{ $ticket->reporter_contact ?? '-' }}</strong>
                    </div>
                </div>

                {{-- Ticket Evidence Photo --}}
                @if($ticket->photo)
                    <div class="space-y-2">
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Foto Bukti Kerusakan</h4>
                        <img src="{{ asset($ticket->photo) }}" class="max-w-md w-full h-auto object-cover rounded-xl border border-slate-800" alt="Foto Bukti Kerusakan">
                    </div>
                @endif
            </div>

            {{-- Action Box (Claim, Assign, Update Status) --}}
            <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg space-y-6">
                <h4 class="font-bold text-white text-base border-b border-slate-800 pb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Aksi Pengelolaan Tiket
                </h4>

                <div class="space-y-4">
                    {{-- 1. CLAIM BUTTON (For Technicians/Admins when Open) --}}
                    @if($ticket->status === 'open')
                        <div class="p-4 bg-indigo-500/5 border border-indigo-500/20 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <span class="font-bold text-white text-sm block">Klaim Tiket Ini</span>
                                <span class="text-xs text-slate-400 mt-0.5">Ambil tugas penanganan perbaikan aset ini. Tiket akan langsung ditugaskan ke Anda.</span>
                            </div>
                            <form action="{{ route('tickets.claim', $ticket->id) }}" method="POST" class="shrink-0 w-full sm:w-auto">
                                @csrf
                                <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-750 text-white rounded-xl text-xs font-semibold shadow-md transition cursor-pointer text-center">
                                    Claim Ticket
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- 2. ASSIGNMENT FORM (Administrator Only) --}}
                    @if(Auth::user()->isAdmin())
                        <form action="{{ route('tickets.assign', $ticket->id) }}" method="POST" class="p-4 bg-slate-950/40 border border-slate-800 rounded-xl space-y-3">
                            @csrf
                            <div>
                                <label for="assigned_to" class="block font-bold text-white text-sm">Tugaskan ke Teknisi</label>
                                <span class="text-xs text-slate-500 mt-0.5 block">Pilih teknisi untuk ditugaskan secara langsung guna menangani perbaikan ini.</span>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3 pt-1.5">
                                <select id="assigned_to" name="assigned_to" required
                                    class="flex-1 px-3 py-2 bg-slate-950 border border-slate-850 focus:border-indigo-500 rounded-xl text-slate-300 text-xs outline-none cursor-pointer transition">
                                    <option value="" disabled selected>-- Pilih Teknisi --</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}" {{ $ticket->assigned_to == $tech->id ? 'selected' : '' }}>
                                            {{ $tech->name }} ({{ $tech->position ?? 'Staff' }})
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-750 text-white rounded-xl text-xs font-semibold transition cursor-pointer">
                                    Tugaskan
                                </button>
                            </div>
                        </form>
                    @endif

                    {{-- 3. STATUS UPDATE FORM (For Assigned Technician or Admin) --}}
                    @php
                        $canUpdateStatus = Auth::user()->isAdmin() || (Auth::user()->id === $ticket->assigned_to);
                    @endphp
                    @if($canUpdateStatus && $ticket->assigned_to)
                        <form action="{{ route('tickets.updateStatus', $ticket->id) }}" method="POST" enctype="multipart/form-data" class="p-4 bg-slate-950/40 border border-slate-800 rounded-xl space-y-4">
                            @csrf
                            <div>
                                <label for="status" class="block font-bold text-white text-sm">Perbarui Status Progres</label>
                                <span class="text-xs text-slate-500 mt-0.5 block">Ubah status pengerjaan tiket ini dan berikan catatan perbaikan.</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="status_select" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Pilih Status Baru</label>
                                    <select id="status_select" name="status" required
                                        class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 focus:border-indigo-500 rounded-xl text-slate-350 text-xs outline-none cursor-pointer transition">
                                        @foreach([
                                            'open' => 'Open',
                                            'assigned' => 'Assigned',
                                            'in_progress' => 'In Progress',
                                            'waiting_sparepart' => 'Waiting Sparepart (Menunggu Part)',
                                            'completed' => 'Completed (Selesai)',
                                            'cancelled' => 'Cancelled (Batal)'
                                        ] as $val => $label)
                                            <option value="{{ $val }}" {{ $ticket->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="status_comment" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Catatan Perubahan (Opsional)</label>
                                    <input type="text" id="status_comment" name="comment" placeholder="Contoh: Mulai pembongkaran casing..."
                                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 focus:border-indigo-500 rounded-xl text-white placeholder-slate-650 outline-none text-xs transition">
                                </div>

                                {{-- Logbook Fields (Only shown if status is completed) --}}
                                <div id="completed-logbook-fields" class="sm:col-span-2 hidden bg-slate-950/80 p-4 border border-slate-850 rounded-xl space-y-4 mt-2">
                                    <div class="border-b border-slate-850 pb-2 mb-2">
                                        <h5 class="text-xs font-bold text-indigo-400">Maintenance Logbook (Wajib diisi untuk status Selesai)</h5>
                                        <p class="text-[10px] text-slate-500">Isi rekam perbaikan di bawah untuk diarsipkan secara permanen.</p>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="diagnosis" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Diagnosa Kerusakan</label>
                                            <textarea id="diagnosis" name="diagnosis" rows="2" placeholder="Masukkan diagnosa kerusakan aset..."
                                                class="w-full px-3 py-2 bg-slate-900 border border-slate-800 focus:border-indigo-500 rounded-lg text-slate-200 text-xs outline-none transition resize-none"></textarea>
                                        </div>
                                        <div>
                                            <label for="cause" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Penyebab Kerusakan</label>
                                            <textarea id="cause" name="cause" rows="2" placeholder="Masukkan penyebab kerusakan..."
                                                class="w-full px-3 py-2 bg-slate-900 border border-slate-800 focus:border-indigo-500 rounded-lg text-slate-200 text-xs outline-none transition resize-none"></textarea>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="action_taken" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Tindakan Perbaikan</label>
                                            <textarea id="action_taken" name="action_taken" rows="2" placeholder="Masukkan tindakan perbaikan yang dilakukan..."
                                                class="w-full px-3 py-2 bg-slate-900 border border-slate-800 focus:border-indigo-500 rounded-lg text-slate-200 text-xs outline-none transition resize-none"></textarea>
                                        </div>
                                        <div>
                                            <label for="spareparts" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Sparepart yang Digunakan</label>
                                            <input type="text" id="spareparts" name="spareparts" placeholder="Contoh: RAM DDR4 8GB, SSD 256GB"
                                                class="w-full px-3 py-2 bg-slate-900 border border-slate-800 focus:border-indigo-500 rounded-lg text-slate-200 text-xs outline-none transition">
                                        </div>
                                        <div>
                                            <label for="maintenance_date" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Tanggal Maintenance</label>
                                            <input type="date" id="maintenance_date" name="maintenance_date" value="{{ date('Y-m-d') }}"
                                                class="w-full px-3 py-2 bg-slate-900 border border-slate-800 focus:border-indigo-500 rounded-lg text-slate-200 text-xs outline-none transition">
                                        </div>
                                        <div>
                                            <label for="photo_before" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Foto Bukti Sebelum (Before)</label>
                                            <input type="file" id="photo_before" name="photo_before" accept="image/*"
                                                class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 focus:border-indigo-500 rounded-lg text-slate-400 text-xs outline-none transition cursor-pointer">
                                        </div>
                                        <div>
                                            <label for="photo_after" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Foto Bukti Sesudah (After)</label>
                                            <input type="file" id="photo_after" name="photo_after" accept="image/*"
                                                class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 focus:border-indigo-500 rounded-lg text-slate-400 text-xs outline-none transition cursor-pointer">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-1.5 flex justify-end">
                                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-750 text-white rounded-xl text-xs font-bold transition cursor-pointer">
                                    Simpan Perubahan Status
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Comments Module --}}
            <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg space-y-6">
                <h4 class="font-bold text-white text-base border-b border-slate-800 pb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                    </svg>
                    Diskusi & Komentar Perbaikan
                </h4>

                {{-- Comments list --}}
                @if($ticket->comments->isEmpty())
                    <p class="text-xs text-slate-500 italic py-2">Belum ada diskusi atau komentar pada tiket ini.</p>
                @else
                    <div class="space-y-4">
                        @foreach($ticket->comments as $comment)
                            <div class="p-3.5 bg-slate-950/50 border border-slate-850 rounded-xl space-y-2">
                                <div class="flex items-center justify-between border-b border-slate-850 pb-1.5 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-200">{{ $comment->user->name }}</span>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded uppercase font-semibold
                                            {{ $comment->user->isAdmin() ? 'bg-amber-500/10 text-amber-300 border border-amber-500/20' : 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/20' }}">
                                            {{ $comment->user->role }}
                                        </span>
                                    </div>
                                    <span class="text-slate-500 text-[10px]">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-xs text-slate-350 leading-relaxed">{{ $comment->content }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Add comment form --}}
                <form action="{{ route('tickets.storeComment', $ticket->id) }}" method="POST" class="space-y-3 pt-3 border-t border-slate-800">
                    @csrf
                    <div>
                        <label for="comment_content" class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Tambah Komentar Baru</label>
                        <textarea id="comment_content" name="content" required rows="3"
                            class="w-full px-4 py-3 bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-650 outline-none text-xs transition"
                            placeholder="Tulis pesan atau komentar Anda terkait progres perbaikan aset..."></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-750 text-white rounded-xl text-xs font-semibold transition cursor-pointer">
                            Kirim Komentar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Right column: Asset Details info --}}
        <div class="space-y-6">
            {{-- Asset Specifications Card --}}
            <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg space-y-4">
                <h4 class="font-bold text-white text-base border-b border-slate-800 pb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 5h10a3 3 0 013 3v10a3 3 0 01-3 3H7a3 3 0 01-3-3V8a3 3 0 013-3z" />
                    </svg>
                    Spesifikasi Aset Terkait
                </h4>

                {{-- Asset Photo --}}
                @if($ticket->asset->photo)
                    <img src="{{ asset($ticket->asset->photo) }}" class="w-full h-36 object-cover rounded-xl border border-slate-800" alt="Foto Aset">
                @endif

                <div class="space-y-3.5 text-xs">
                    <div>
                        <span class="text-slate-500 block">Nama Aset</span>
                        <strong class="text-slate-200 text-sm block mt-0.5">{{ $ticket->asset->name }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">ID Asset TI (Internal)</span>
                        <strong class="text-slate-200 text-sm font-mono block mt-0.5">{{ $ticket->asset->asset_id }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Nomor Inventaris Kementerian</span>
                        <strong class="text-slate-300 block mt-0.5">{{ $ticket->asset->government_inventory_number }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Serial Number</span>
                        <strong class="text-slate-300 font-mono block mt-0.5">{{ $ticket->asset->serial_number ?? '-' }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Kategori</span>
                        <strong class="text-slate-300 block mt-0.5">{{ $ticket->asset->category ?? '-' }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Lokasi Penempatan</span>
                        <strong class="text-slate-300 block mt-0.5">{{ $ticket->asset->building ?? '-' }}, Lnt. {{ $ticket->asset->floor ?? '-' }}, R. {{ $ticket->asset->room ?? '-' }}</strong>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800">
                    <a href="{{ route('assets.show', $ticket->asset->id) }}" class="w-full text-center block py-2.5 bg-slate-800 hover:bg-slate-750 border border-slate-700 rounded-xl text-xs font-semibold text-slate-300 transition cursor-pointer">
                        Buka Halaman Aset
                    </a>
                </div>
            </div>

            {{-- Audit log status transitions --}}
            <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg space-y-4">
                <h4 class="font-bold text-white text-base border-b border-slate-800 pb-3 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Lini Masa Perubahan Status
                </h4>

                <div class="space-y-5 relative before:absolute before:left-3.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-850">
                    @foreach($ticket->histories as $history)
                        <div class="flex gap-4 relative">
                            <span class="h-7.5 w-7.5 shrink-0 rounded-full bg-slate-850 border-2 border-slate-900 flex items-center justify-center text-slate-400 z-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                            <div class="text-xs space-y-1">
                                <span class="block text-slate-500 font-semibold">{{ $history->created_at->format('d M Y, H:i') }}</span>
                                <div class="text-slate-200">
                                    @if($history->old_status)
                                        <span class="text-slate-500 line-through">{{ $history->old_status }}</span>
                                        <span class="text-slate-400 font-medium">→</span>
                                    @endif
                                    <span class="text-indigo-400 font-bold uppercase">{{ $history->new_status }}</span>
                                </div>
                                <p class="text-slate-400 italic leading-relaxed">{{ $history->comment }}</p>
                                @if($history->user)
                                    <span class="block text-[10px] text-slate-550">Oleh: {{ $history->user->name }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status_select');
    const logbookFields = document.getElementById('completed-logbook-fields');
    
    if (statusSelect && logbookFields) {
        function checkStatus() {
            if (statusSelect.value === 'completed') {
                logbookFields.classList.remove('hidden');
                document.getElementById('diagnosis').required = true;
                document.getElementById('cause').required = true;
                document.getElementById('action_taken').required = true;
                document.getElementById('maintenance_date').required = true;
            } else {
                logbookFields.classList.add('hidden');
                document.getElementById('diagnosis').required = false;
                document.getElementById('cause').required = false;
                document.getElementById('action_taken').required = false;
                document.getElementById('maintenance_date').required = false;
            }
        }
        statusSelect.addEventListener('change', checkStatus);
        checkStatus();
    }
});
</script>
@endsection
