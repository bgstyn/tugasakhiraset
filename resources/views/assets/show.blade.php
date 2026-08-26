@extends(Auth::check() ? 'layouts.layout' : 'layouts.public')

@section('title', 'Detail Aset - IT Asset Management')
@section('page_title', 'Detail Aset IT')

@section('content')

@if(!Auth::check())
    <!-- ========================================== -->
    <!-- PUBLIC GUEST VIEW (Scan QR Code Landing) -->
    <!-- ========================================== -->
    <div class="max-w-3xl mx-auto space-y-6 animate-[fadeIn_0.5s_ease-out_forwards]">
        
        <!-- Header JTI Info -->
        <div class="text-center sm:text-left mb-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold rounded-full mb-2">
                <span class="h-2 w-2 rounded-full bg-indigo-500 animate-pulse"></span>
                Informasi Publik Aset
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-tight">Sistem Inventaris IT</h2>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">Status dan verifikasi kelayakan inventaris resmi Jurusan Teknologi Informasi.</p>
        </div>

        <!-- Main Details Card -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-6 md:p-8 shadow-xl relative overflow-hidden">
            <!-- Glassmorphism decorative blur -->
            <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl"></div>

            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 border-b border-slate-850 pb-6 mb-6">
                <div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/10 border border-indigo-500/25 text-indigo-300 mb-2 font-mono uppercase">
                        {{ $asset->room ?? '-' }} Penempatan
                    </span>
                    <h3 class="text-xl font-bold text-white md:text-2xl">{{ $asset->name }}</h3>
                    <p class="text-indigo-400 text-sm font-semibold mt-1">ID Asset TI: {{ $asset->asset_id }}</p>
                    <p class="text-slate-400 text-xs mt-1 font-mono">No. Inventaris: {{ $asset->government_inventory_number }}</p>
                </div>

                <div class="self-start sm:self-auto">
                    @if($asset->status == 'standby')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/25 text-emerald-400 shadow-sm">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full mr-2"></span>
                            Standby (Siap Pakai)
                        </span>
                    @elseif($asset->status == 'digunakan')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-500/10 border border-blue-500/25 text-blue-400 shadow-sm">
                            <span class="w-2 h-2 bg-blue-400 rounded-full mr-2"></span>
                            Sedang Digunakan
                        </span>
                    @elseif($asset->status == 'maintenance')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-500/10 border border-amber-500/25 text-amber-400 shadow-sm">
                            <span class="w-2 h-2 bg-amber-400 rounded-full mr-2"></span>
                            Dalam Perbaikan
                        </span>
                    @elseif($asset->status == 'rusak')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-500/10 border border-rose-500/25 text-rose-400 shadow-sm">
                            <span class="w-2 h-2 bg-rose-400 rounded-full mr-2"></span>
                            Rusak (Off)
                        </span>
                    @elseif($asset->status == 'fraud')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-red-500/10 border border-red-500/25 text-red-400 shadow-sm">
                            <span class="w-2 h-2 bg-red-400 rounded-full mr-2"></span>
                            Hilang / Fraud
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-500/10 border border-slate-500/25 text-slate-400 shadow-sm">
                            <span class="w-2 h-2 bg-slate-400 rounded-full mr-2"></span>
                            Write Off
                        </span>
                    @endif
                </div>
            </div>

            <!-- Fields Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                <div>
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Nama Aset</span>
                    <strong class="text-slate-200 text-base font-semibold">{{ $asset->name }}</strong>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">ID Asset TI</span>
                    <strong class="text-slate-200 text-base font-mono">{{ $asset->asset_id }}</strong>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Nomor Inventaris Kementerian</span>
                    <strong class="text-slate-200 text-base font-mono">{{ $asset->government_inventory_number }}</strong>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Kategori</span>
                    <strong class="text-slate-200 text-base font-semibold">{{ $asset->category }}</strong>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Merek / Brand</span>
                    <strong class="text-slate-200 text-base font-semibold">{{ $asset->brand }}</strong>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Model / Tipe</span>
                    <strong class="text-slate-200 text-base font-semibold">{{ $asset->model }}</strong>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Serial Number (SN Fisik)</span>
                    <strong class="text-slate-200 text-base font-mono">{{ $asset->serial_number ?? '-' }}</strong>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Lokasi Aset</span>
                    <strong class="text-slate-200 text-base font-semibold">
                        {{ $asset->building ?? '-' }}, Lantai {{ $asset->floor ?? '-' }}, Ruangan {{ $asset->room ?? '-' }}
                    </strong>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Tahun Pengadaan</span>
                    <strong class="text-slate-200 text-base font-semibold">{{ $asset->year }}</strong>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Status Aset</span>
                    <strong class="text-slate-200 text-base font-semibold capitalize">{{ $asset->status }}</strong>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">User Saat Ini</span>
                    <strong class="text-slate-200 text-base font-semibold">{{ $asset->current_user ?? 'Belum Ditugaskan (Standby)' }}</strong>
                </div>
                @if($asset->bundles->count() > 0)
                <div class="sm:col-span-2">
                    <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Paket Asset (Bundle)</span>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach($asset->bundles as $bundle)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-500/10 border border-indigo-500/25 text-indigo-300 rounded-xl text-xs font-semibold">
                            <span class="font-mono text-indigo-400">{{ $bundle->code }}</span>
                            <span>{{ $bundle->name }}</span>
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Optional Specifications -->
            @if($asset->specification)
                <div class="mt-8 pt-6 border-t border-slate-800">
                    <h4 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 5h10a3 3 0 013 3v10a3 3 0 01-3 3H7a3 3 0 01-3-3V8a3 3 0 013-3z" />
                        </svg>
                        Spesifikasi Detail
                    </h4>
                    <div class="text-sm bg-slate-950/45 p-4 rounded-xl border border-slate-800/80 text-slate-300">
                        {{ $asset->specification }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Verification / Alert Note -->
        <div class="bg-indigo-950/20 border border-indigo-900/30 rounded-2xl p-4 flex gap-3 items-start text-xs text-indigo-300 leading-relaxed">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="font-bold text-indigo-200">Verifikasi Resmi JTI</p>
                <p class="mt-0.5 text-slate-400">Data ini tersinkronisasi langsung dengan IT Asset Management System Jurusan Teknologi Informasi. Silakan hubungi staff laboratorium jika terdapat ketidakcocokan data fisik dengan sistem.</p>
            </div>
        </div>
    </div>

@else
    <!-- ========================================== -->
    <!-- AUTHENTICATED STAFF VIEW (Full Admin/Teknisi) -->
    <!-- ========================================== -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('assets.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition w-fit">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Daftar Aset
        </a>
        
        <!-- Delete Button (Only for admin) -->
        <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus aset {{ $asset->name }}?')" class="inline w-full sm:w-auto">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full sm:w-auto justify-center px-4 py-2 bg-slate-800 hover:bg-rose-950/40 text-rose-400 border border-slate-700 hover:border-rose-900 rounded-xl text-xs font-semibold transition flex items-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Hapus Aset
            </button>
        </form>
    </div>

    <!-- Staff Actions Board (Requested: Edit Asset, Pair RFID, Lihat Riwayat, Update Status) -->
    <div class="mb-6 bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden">
        <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-24 h-24 bg-indigo-500/5 rounded-full blur-xl pointer-events-none"></div>
        <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-3.5 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Panel Aksi Staff / Teknisi
        </h4>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <!-- Edit Asset Button -->
            <a href="{{ route('assets.edit', $asset->id) }}" class="justify-center px-4 py-3 bg-slate-950 hover:bg-slate-800 text-indigo-400 hover:text-indigo-300 border border-slate-800 hover:border-slate-700 rounded-xl text-xs font-semibold transition flex items-center gap-2 cursor-pointer text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit Asset
            </a>

            <!-- Pair RFID Button -->
            <button type="button" onclick="openPairRfidModal()" class="justify-center px-4 py-3 bg-slate-950 hover:bg-indigo-950/40 text-indigo-400 hover:text-indigo-300 border border-slate-800 hover:border-indigo-900/40 rounded-xl text-xs font-semibold transition flex items-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 14v1m8-9h1M3 12h1m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l-.707.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                </svg>
                Pair RFID
            </button>

            <!-- Lihat Riwayat Button -->
            <button type="button" onclick="scrollToElement('riwayat-timeline')" class="justify-center px-4 py-3 bg-slate-950 hover:bg-slate-800 text-emerald-400 hover:text-emerald-300 border border-slate-800 hover:border-slate-700 rounded-xl text-xs font-semibold transition flex items-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Lihat Riwayat
            </button>

            <!-- Update Status Button -->
            <button type="button" onclick="scrollToElement('quick-status-update')" class="justify-center px-4 py-3 bg-slate-950 hover:bg-slate-800 text-amber-400 hover:text-amber-300 border border-slate-800 hover:border-slate-700 rounded-xl text-xs font-semibold transition flex items-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.2" />
                </svg>
                Update Status
            </button>

            <!-- Catat Maintenance Button -->
            <button type="button" onclick="openManualMaintenanceModal()" class="justify-center px-4 py-3 bg-slate-950 hover:bg-slate-800 text-cyan-400 hover:text-cyan-300 border border-slate-800 hover:border-slate-700 rounded-xl text-xs font-semibold transition flex items-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Catat Perbaikan
            </button>

            <!-- Request Replacement Button -->
            <button type="button" onclick="openRequestReplacementModal()" class="justify-center px-4 py-3 bg-slate-950 hover:bg-slate-800 text-rose-400 hover:text-rose-300 border border-slate-800 hover:border-slate-700 rounded-xl text-xs font-semibold transition flex items-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                Ajukan Ganti
            </button>
        </div>
    </div>

    {{-- ============================================== --}}
    {{-- Bundle Membership Panel --}}
    {{-- ============================================== --}}
    @if($asset->bundles->count() > 0)
    <div class="mb-6 bg-indigo-950/20 border border-indigo-900/30 rounded-2xl px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex items-center gap-2.5 shrink-0">
            <div class="w-9 h-9 rounded-xl bg-indigo-500/15 border border-indigo-500/25 flex items-center justify-center">
                <svg class="h-4.5 w-4.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <span class="text-xs font-bold text-indigo-300 uppercase tracking-wider">Bagian dari Bundle</span>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($asset->bundles as $bundle)
            <a href="{{ route('bundles.show', $bundle) }}"
                class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/25 text-indigo-300 hover:text-white rounded-xl text-xs font-semibold transition group">
                <span class="font-mono text-indigo-400 group-hover:text-indigo-300">{{ $bundle->code }}</span>
                <span>{{ $bundle->name }}</span>
                <svg class="h-3 w-3 opacity-50 group-hover:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Main columns grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Asset Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Main details card -->
            <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-6 md:p-8 shadow-lg relative overflow-hidden">
                <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl"></div>

                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 border-b border-slate-800 pb-6 mb-6">
                    <div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-500/10 border border-indigo-500/25 text-indigo-300 mb-2 font-mono uppercase">
                            {{ $asset->room ?? '-' }} Penempatan
                        </span>
                        <h3 class="text-xl font-bold text-white md:text-2xl">{{ $asset->name }}</h3>
                        <p class="text-indigo-400 text-sm font-semibold mt-1">ID Asset TI: {{ $asset->asset_id }}</p>
                        <p class="text-slate-400 text-xs mt-1 font-mono">No. Inventaris: {{ $asset->government_inventory_number }}</p>
                    </div>

                    <div class="self-start sm:self-auto">
                        @if($asset->status == 'standby')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/25 text-emerald-400 shadow-sm shadow-emerald-500/5">
                                <span class="w-2 h-2 bg-emerald-400 rounded-full mr-2"></span>
                                Standby (Siap Pakai)
                            </span>
                        @elseif($asset->status == 'digunakan')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-500/10 border border-blue-500/25 text-blue-400 shadow-sm shadow-blue-500/5">
                                <span class="w-2 h-2 bg-blue-400 rounded-full mr-2"></span>
                                Sedang Digunakan
                            </span>
                        @elseif($asset->status == 'maintenance')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-500/10 border border-amber-500/25 text-amber-400 shadow-sm shadow-amber-500/5">
                                <span class="w-2 h-2 bg-amber-400 rounded-full mr-2"></span>
                                Dalam Perbaikan
                            </span>
                        @elseif($asset->status == 'rusak')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-500/10 border border-rose-500/25 text-rose-400 shadow-sm shadow-rose-500/5">
                                <span class="w-2 h-2 bg-rose-400 rounded-full mr-2"></span>
                                Rusak (Off)
                            </span>
                        @elseif($asset->status == 'fraud')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-red-500/10 border border-red-500/25 text-red-400 shadow-sm shadow-red-500/5">
                                <span class="w-2 h-2 bg-red-400 rounded-full mr-2"></span>
                                Hilang / Fraud
                            </span>
                        @elseif($asset->status == 'write_off')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-500/10 border border-slate-500/25 text-slate-400 shadow-sm shadow-slate-500/5">
                                <span class="w-2 h-2 bg-slate-400 rounded-full mr-2"></span>
                                Write Off
                            </span>
                        @elseif($asset->status == 'pending_fraud_approval')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-500/10 border border-amber-500/30 text-amber-300 shadow-sm animate-pulse">
                                <span class="w-2 h-2 bg-amber-400 rounded-full mr-2 animate-ping"></span>
                                🟡 Pending Fraud Approval
                            </span>
                        @elseif($asset->status == 'pending_write_off_approval')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-500/10 border border-amber-500/30 text-amber-300 shadow-sm animate-pulse">
                                <span class="w-2 h-2 bg-amber-400 rounded-full mr-2 animate-ping"></span>
                                🟡 Pending Write Off Approval
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Fields Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                    <div>
                        <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">User Saat Ini</span>
                        <strong class="text-slate-200 text-base">{{ $asset->current_user ?? 'Belum Ditugaskan (Standby)' }}</strong>
                    </div>
                    <div>
                        <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Tahun Pengadaan</span>
                        <strong class="text-slate-200 text-base">{{ $asset->year }}</strong>
                    </div>
                    <div>
                        <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Kategori Aset</span>
                        <strong class="text-slate-200 text-base">{{ $asset->category }}</strong>
                    </div>
                    <div>
                        <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Serial Number Fisik (SN)</span>
                        <strong class="text-slate-200 text-base font-mono">{{ $asset->serial_number ?? '-' }}</strong>
                    </div>
                    <div>
                        <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Merek / Brand</span>
                        <strong class="text-slate-200 text-base">{{ $asset->brand }}</strong>
                    </div>
                    <div>
                        <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Tipe / Model</span>
                        <strong class="text-slate-200 text-base">{{ $asset->model }}</strong>
                    </div>
                    <div>
                        <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Lokasi Aset</span>
                        <strong class="text-slate-200 text-base">
                            {{ $asset->building ?? '-' }}, Lantai {{ $asset->floor ?? '-' }}, Ruangan {{ $asset->room ?? '-' }}
                        </strong>
                    </div>
                    <div>
                        <span class="block text-slate-555 text-xs font-semibold uppercase tracking-wider mb-1">RFID UID & Status</span>
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            @if($asset->rfid_uid)
                                <strong class="text-slate-200 text-sm font-mono bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-0.5 rounded inline-block">{{ $asset->rfid_uid }}</strong>
                                
                                @if($asset->rfid_status === 'aktif')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-emerald-500/10 border border-emerald-500/25 text-emerald-400">
                                        <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full"></span>
                                        Aktif
                                    </span>
                                @elseif($asset->rfid_status === 'nonaktif')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-amber-500/10 border border-amber-500/25 text-amber-300">
                                        <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span>
                                        Nonaktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-slate-500/10 border border-slate-500/25 text-slate-400">
                                        Status Tidak Diketahui
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-rose-500/10 border border-rose-500/25 text-rose-400">
                                    <span class="w-1.5 h-1.5 bg-rose-400 rounded-full animate-pulse"></span>
                                    Belum Terdaftar
                                </span>
                            @endif
                        </div>

                        {{-- Control Buttons --}}
                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            @if(!$asset->rfid_uid)
                                <button type="button" onclick="openPairRfidModal()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1 cursor-pointer transition">
                                    Daftarkan RFID
                                </button>
                            @else
                                <button type="button" onclick="openPairRfidModal()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-705 text-slate-300 rounded-lg text-xs font-semibold flex items-center gap-1 cursor-pointer transition">
                                    Ganti RFID
                                </button>

                                <form action="{{ route('assets.rfid.toggle-web', $asset->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-705 text-slate-300 rounded-lg text-xs font-semibold cursor-pointer transition">
                                        {{ $asset->rfid_status === 'aktif' ? 'Nonaktifkan RFID' : 'Aktifkan RFID' }}
                                    </button>
                                </form>

                                @if(Auth::user()?->isAdmin())
                                    <form action="{{ route('assets.rfid.delete-web', $asset->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus RFID dari aset ini?')">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 text-rose-400 rounded-lg text-xs font-semibold cursor-pointer transition">
                                            Hapus RFID
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div>
                        <span class="block text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Tanggal Terdaftar</span>
                        <strong class="text-slate-400 text-base font-normal">{{ $asset->created_at->format('d M Y, H:i') }}</strong>
                    </div>
                </div>

                <!-- Hardware Specifications -->
                @if($asset->specification)
                    <div class="mt-8 pt-6 border-t border-slate-800">
                        <h4 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 5h10a3 3 0 013 3v10a3 3 0 01-3 3H7a3 3 0 01-3-3V8a3 3 0 013-3z" />
                            </svg>
                            Spesifikasi Detail
                        </h4>
                        <div class="text-sm bg-slate-950/45 p-4 rounded-xl border border-slate-800/80 text-slate-300">
                            {{ $asset->specification }}
                        </div>
                    </div>
                @endif
            </div>

            {{-- Quick Status Update Card --}}
            <div id="quick-status-update" class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg transition duration-300">
                <h4 class="font-bold text-white text-base mb-1 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.2" />
                    </svg>
                    Perbarui Status Aset
                </h4>

                @php $isPendingApproval = in_array($asset->status, ['pending_fraud_approval','pending_write_off_approval']); @endphp

                @if($isPendingApproval)
                    {{-- Locked: Pending Approval --}}
                    <div class="mt-4 p-4 rounded-xl bg-amber-500/5 border border-amber-500/20 text-center">
                        <div class="text-amber-300 font-semibold text-sm mb-1">⏳ Menunggu Persetujuan Administrator</div>
                        <p class="text-xs text-slate-400">Status tidak dapat diubah selama pengajuan masih pending.</p>
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('admin.approvals.index') }}" class="inline-flex items-center gap-1.5 mt-3 px-4 py-2 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/25 text-amber-300 rounded-xl text-xs font-semibold transition">
                                Lihat Pengajuan →
                            </a>
                        @endif
                    </div>
                @else
                    <p class="text-xs text-slate-500 mb-4">
                        @if(!Auth::user()->isAdmin())
                            Pilih status baru. Perubahan ke <span class="text-rose-400 font-semibold">Fraud</span> atau <span class="text-slate-300 font-semibold">Write Off</span> memerlukan persetujuan Administrator.
                        @else
                            Sebagai Administrator, Anda dapat mengubah semua status secara langsung.
                        @endif
                    </p>
                    <form action="{{ route('assets.updateStatus', $asset->id) }}" method="POST" id="statusUpdateForm">
                        @csrf
                        @method('PATCH')
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            @foreach([
                                'standby'   => ['label' => 'Standby',        'color' => 'hover:border-emerald-600/50', 'dot' => 'bg-emerald-400'],
                                'digunakan' => ['label' => 'Digunakan',       'color' => 'hover:border-blue-600/50',    'dot' => 'bg-blue-400'],
                                'maintenance'=> ['label' => 'Maintenance',    'color' => 'hover:border-amber-600/50',  'dot' => 'bg-amber-400'],
                                'rusak'     => ['label' => 'Rusak',           'color' => 'hover:border-rose-600/50',   'dot' => 'bg-rose-400'],
                                'fraud'     => ['label' => 'Fraud / Hilang',  'color' => 'hover:border-red-600/50',    'dot' => 'bg-red-400'],
                                'write_off' => ['label' => 'Write Off',       'color' => 'hover:border-slate-600/50',  'dot' => 'bg-slate-400'],
                            ] as $val => $cfg)
                                @php
                                    $requiresApproval = in_array($val, ['fraud','write_off']) && !Auth::user()->isAdmin();
                                @endphp
                                @if($requiresApproval)
                                    {{-- Button triggers approval modal instead of direct form submit --}}
                                    <button type="button"
                                        onclick="openApprovalModal('{{ $val }}')"
                                        class="py-2.5 px-3 border rounded-xl text-xs font-medium cursor-pointer transition text-center flex flex-col justify-center items-center gap-1.5 relative
                                        {{ $asset->status == $val ? 'bg-amber-600/20 border-amber-500/30 text-amber-300' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-slate-200 ' . $cfg['color'] }}">
                                        {{ $cfg['label'] }}
                                        <span class="text-[9px] text-slate-500 font-normal">Perlu Approval</span>
                                    </button>
                                @else
                                    <button type="submit" name="status" value="{{ $val }}"
                                        class="py-2.5 px-3 border rounded-xl text-xs font-medium cursor-pointer transition text-center flex flex-col justify-center items-center gap-1.5
                                        {{ $asset->status == $val ? 'bg-indigo-600 border-indigo-500 text-white shadow-md' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-slate-200 ' . $cfg['color'] }}">
                                        {{ $cfg['label'] }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </form>
                @endif
            </div>

            {{-- ============================================== --}}
            {{-- Approval History Section --}}
            {{-- ============================================== --}}
            @if($approvals->count() > 0)
            <div id="riwayat-pengajuan" class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg">
                <h4 class="font-bold text-white text-base mb-5 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Riwayat Pengajuan Approval
                </h4>
                <div class="space-y-3">
                    @foreach($approvals as $apv)
                    <div class="p-4 rounded-xl border {{ $apv->status === 'pending' ? 'bg-amber-500/5 border-amber-500/20' : ($apv->status === 'approved' ? 'bg-emerald-500/5 border-emerald-500/15' : 'bg-rose-500/5 border-rose-500/15') }}">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2">
                                @if($apv->type === 'fraud')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 border border-rose-500/20 text-rose-300">🔴 Fraud</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-700/60 border border-slate-600/30 text-slate-300">⚫ Write Off</span>
                                @endif
                                @if($apv->status === 'pending')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 border border-amber-500/20 text-amber-300">🟡 Pending</span>
                                @elseif($apv->status === 'approved')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 border border-emerald-500/20 text-emerald-300">✅ Disetujui</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-500/10 border border-rose-500/20 text-rose-300">❌ Ditolak</span>
                                @endif
                            </div>
                            <span class="text-[10px] text-slate-500">{{ $apv->created_at->format('d M Y, H:i') }}</span>
                        </div>
                        <div class="text-xs text-slate-400 space-y-1">
                            <div><span class="text-slate-500">Pengaju:</span> <span class="text-slate-300">{{ $apv->requested_by_name }}</span> · {{ $apv->requested_by_position }}</div>
                            @if($apv->status === 'approved' && $apv->approved_by)
                                <div><span class="text-slate-500">Disetujui oleh:</span> <span class="text-emerald-300 font-medium">{{ $apv->approved_by }}</span> · {{ $apv->approved_at?->format('d M Y, H:i') }}</div>
                            @elseif($apv->status === 'rejected' && $apv->rejected_by)
                                <div><span class="text-slate-500">Ditolak oleh:</span> <span class="text-rose-300 font-medium">{{ $apv->rejected_by }}</span></div>
                                @if($apv->rejection_reason)
                                    <div class="mt-1 p-2 bg-slate-800/60 rounded-lg text-slate-400 italic">"{{ $apv->rejection_reason }}"</div>
                                @endif
                            @endif
                            @if($apv->document_path)
                                <div>
                                    <a href="{{ Storage::url($apv->document_path) }}" target="_blank"
                                        class="inline-flex items-center gap-1 text-indigo-400 hover:text-indigo-300 transition">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        Lihat Surat Pengajuan
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column: QR Code & Lifecycle History -->
        <div class="space-y-6">
            <!-- QR Code Asset Card -->
            <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg text-center flex flex-col items-center justify-center">
                <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4 self-start flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    QR Code Asset
                </h4>
                <div class="p-4 bg-white rounded-2xl inline-block shadow-inner mb-3">
                    <img src="{{ $asset->qr_png_url }}" 
                        alt="QR Code Aset" class="w-36 h-36 mx-auto">
                </div>
                <p class="text-xs text-indigo-400 font-bold font-mono mb-4 bg-indigo-500/10 px-3 py-1 rounded border border-indigo-500/20 inline-block">{{ $asset->asset_id }}</p>
                
                <div class="grid grid-cols-2 gap-2.5 w-full">
                    <a href="{{ $asset->qr_png_url }}" download="{{ $asset->asset_id }}.png"
                        class="py-2 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-center text-xs font-semibold shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition cursor-pointer flex items-center justify-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        PNG
                    </a>
                    <a href="{{ $asset->qr_svg_url }}" download="{{ $asset->asset_id }}.svg"
                        class="py-2 px-3 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-350 hover:text-white rounded-xl text-center text-xs font-semibold transition cursor-pointer flex items-center justify-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M8 8a4 4 0 118 0v4h1v-4a5 5 0 00-10 0v4h1V8z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16h16M12 12v4m-3-3l3 3 3-3" />
                        </svg>
                        SVG
                    </a>
                </div>>
                </d            <!-- Local Lifecycle Timeline -->
            <div id="riwayat-timeline" class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg transition duration-300">
                <h4 class="font-bold text-white text-base mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Lini Masa Asset Lifecycle
                </h4>

                <div class="flex flex-wrap gap-1.5 mb-5 bg-slate-950/60 p-1 border border-slate-850 rounded-xl">
                    <button type="button" onclick="filterHistory('all')" id="tab-all" class="hist-tab px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition bg-indigo-600/20 text-indigo-300 border border-indigo-500/20 cursor-pointer">Semua</button>
                    <button type="button" onclick="filterHistory('assignment')" id="tab-assignment" class="hist-tab px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition text-slate-400 hover:text-slate-200 cursor-pointer">Assignment</button>
                    <button type="button" onclick="filterHistory('status')" id="tab-status" class="hist-tab px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition text-slate-400 hover:text-slate-200 cursor-pointer">Status</button>
                    <button type="button" onclick="filterHistory('location')" id="tab-location" class="hist-tab px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition text-slate-400 hover:text-slate-200 cursor-pointer">Lokasi</button>
                    <button type="button" onclick="filterHistory('maintenance')" id="tab-maintenance" class="hist-tab px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition text-slate-400 hover:text-slate-200 cursor-pointer">Maintenance</button>
                    <button type="button" onclick="filterHistory('ticket')" id="tab-ticket" class="hist-tab px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition text-slate-400 hover:text-slate-200 cursor-pointer">Tiket</button>
                    <button type="button" onclick="filterHistory('replacement')" id="tab-replacement" class="hist-tab px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition text-slate-400 hover:text-slate-200 cursor-pointer">Replacement</button>
                    <button type="button" onclick="filterHistory('rfid')" id="tab-rfid" class="hist-tab px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition text-slate-400 hover:text-slate-200 cursor-pointer">RFID</button>
                </div>

                @if($histories->isEmpty())
                    <p class="text-xs text-slate-500 text-center py-6">Belum ada riwayat perubahan.</p>
                @else
                    <div class="flow-root">
                        <ul class="-mb-8">
                            @foreach($histories as $log)
                                @php
                                    $cat = 'other';
                                    if (in_array($log->action, ['create', 'assignment_change'])) {
                                        $cat = 'assignment';
                                    } elseif (in_array($log->action, ['status_change', 'approval_requested', 'approval_approved', 'approval_rejected'])) {
                                        $cat = 'status';
                                    } elseif ($log->action === 'location_change') {
                                        $cat = 'location';
                                    } elseif ($log->action === 'maintenance') {
                                        $cat = 'maintenance';
                                    } elseif (in_array($log->action, ['ticket_created', 'ticket_status_change'])) {
                                        $cat = 'ticket';
                                    } elseif (str_contains($log->action, 'replacement')) {
                                        $cat = 'replacement';
                                    } elseif ($log->action === 'rfid_change') {
                                        $cat = 'rfid';
                                    }
                                    
                                    $actionLabel = 'Update Aset';
                                    $actionBg = 'bg-blue-500/10 text-blue-400 border border-blue-500/20';
                                    if ($log->action === 'create') {
                                        $actionLabel = 'Registrasi Aset';
                                        $actionBg = 'bg-emerald-500/10 text-emerald-450 border border-emerald-500/20';
                                    } elseif ($log->action === 'status_change') {
                                        $actionLabel = 'Perubahan Status';
                                        $actionBg = 'bg-amber-500/10 text-amber-400 border border-amber-500/20';
                                    } elseif ($log->action === 'location_change') {
                                        $actionLabel = 'Perpindahan Lokasi';
                                        $actionBg = 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20';
                                    } elseif ($log->action === 'assignment_change') {
                                        $actionLabel = 'Perubahan Assignment';
                                        $actionBg = 'bg-purple-500/10 text-purple-405 border border-purple-500/20';
                                    } elseif ($log->action === 'rfid_change') {
                                        $actionLabel = 'Registrasi RFID';
                                        $actionBg = 'bg-pink-500/10 text-pink-400 border border-pink-500/20';
                                    } elseif ($log->action === 'maintenance') {
                                        $actionLabel = 'Maintenance / Perbaikan';
                                        $actionBg = 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20';
                                    } elseif ($log->action === 'ticket_created') {
                                        $actionLabel = 'Tiket Kerusakan Dibuat';
                                        $actionBg = 'bg-rose-500/10 text-rose-455 border border-rose-500/20';
                                    } elseif ($log->action === 'ticket_status_change') {
                                        $actionLabel = 'Status Tiket Diperbarui';
                                        $actionBg = 'bg-teal-500/10 text-teal-400 border border-teal-500/20';
                                    } elseif (str_contains($log->action, 'replacement')) {
                                        $actionLabel = 'Replacement Request (' . str_replace('replacement_', '', $log->action) . ')';
                                        $actionBg = 'bg-red-500/10 text-red-405 border border-red-500/20';
                                    } elseif (str_contains($log->action, 'approval')) {
                                        $actionLabel = 'Persetujuan (' . str_replace('approval_', '', $log->action) . ')';
                                        $actionBg = 'bg-amber-600/10 text-amber-300 border border-amber-500/20';
                                    }
                                @endphp
                                <li class="history-item pb-8 relative" data-category="{{ $cat }}">
                                    @if(!$loop->last)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-800" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-slate-900 {{ $actionBg }}">
                                                @if($log->action === 'create')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                @elseif($log->action === 'maintenance')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></svg>
                                                @elseif($log->action === 'rfid_change')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 14v1m8-9h1M3 12h1m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l-.707.707M12 8a4 4 0 100 8 4 4 0 000-8z" /></svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.2" /></svg>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0 pt-1.5">
                                            <p class="text-xs text-slate-350">
                                                <span class="font-bold text-slate-200">
                                                    {{ $actionLabel }}
                                                </span>
                                            </p>
                                            
                                            <!-- List values that changed -->
                                            @if($log->new_values)
                                                <div class="mt-2 space-y-1 text-[11px] text-slate-400 bg-slate-950/40 p-2.5 border border-slate-850 rounded-lg">
                                                    @foreach($log->new_values as $key => $newVal)
                                                        @php
                                                            $oldVal = $log->old_values[$key] ?? null;
                                                        @endphp
                                                        @if($oldVal !== $newVal)
                                                            <div>
                                                                <span class="capitalize font-semibold text-slate-500">{{ str_replace('_', ' ', $key) }}:</span> 
                                                                @if($oldVal !== null)
                                                                    <span class="line-through text-rose-455">{{ is_array($oldVal) ? json_encode($oldVal) : $oldVal }}</span>
                                                                    <span class="text-slate-400">→</span>
                                                                @endif
                                                                <span class="text-emerald-450 font-medium">{{ is_array($newVal) ? json_encode($newVal) : $newVal }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif

                                            <div class="flex flex-col gap-0.5 mt-2">
                                                <span class="text-[10px] text-slate-500">
                                                    Oleh: <strong class="text-slate-400">{{ $log->changed_by_name }}</strong> ({{ $log->changed_by_position }})
                                                </span>
                                                <span class="text-[9px] text-slate-600">
                                                    {{ $log->created_at->format('d M Y, H:i') }} ({{ $log->created_at->diffForHumans() }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============================================== --}}
    {{-- APPROVAL SUBMISSION MODAL --}}
    {{-- ============================================== --}}
    <div id="approvalModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeApprovalModal()"></div>
        <div class="relative bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-slate-800">
                <div>
                    <h3 class="text-lg font-bold text-white" id="approvalModalTitle">Pengajuan Perubahan Status</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Pengajuan akan dikirim ke Administrator untuk disetujui.</p>
                </div>
                <button onclick="closeApprovalModal()" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition cursor-pointer">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('approvals.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
                @csrf
                <input type="hidden" name="asset_id" value="{{ $asset->id }}">
                <input type="hidden" name="type" id="approvalType" value="">

                {{-- Asset Info --}}
                <div class="p-3 rounded-xl bg-slate-800/60 border border-slate-700/50 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-semibold text-slate-200 block truncate">{{ $asset->name }}</span>
                        <div class="text-xs text-slate-400 font-mono">{{ $asset->asset_id }}</div>
                    </div>
                </div>

                {{-- Jenis Pengajuan (readonly, set by JS) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Jenis Pengajuan</label>
                    <div id="approvalTypeDisplay" class="px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-sm font-semibold text-slate-200"></div>
                </div>

                {{-- Alasan --}}
                <div>
                    <label for="approval_reason" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                        Alasan Pengajuan <span class="text-rose-400">*</span>
                    </label>
                    <textarea id="approval_reason" name="reason" rows="3" required minlength="10" maxlength="1000"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500/60 focus:ring-1 focus:ring-indigo-500/30 resize-none transition"
                        placeholder="Jelaskan alasan mengapa aset ini perlu diubah statusnya..."></textarea>
                </div>

                {{-- Upload Surat --}}
                <div>
                    <label for="approval_document" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                        Upload Surat Pengajuan
                        <span class="ml-1 text-slate-500 normal-case font-normal">(PDF, JPG, PNG, maks. 5MB)</span>
                    </label>
                    <label for="approval_document"
                        class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-slate-700 hover:border-indigo-500/50 rounded-xl cursor-pointer bg-slate-800/40 hover:bg-slate-800/70 transition group">
                        <div id="uploadPlaceholder" class="flex flex-col items-center gap-2">
                            <svg class="h-7 w-7 text-slate-600 group-hover:text-indigo-400 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <span class="text-xs text-slate-500 group-hover:text-slate-300 transition">Klik untuk upload atau seret file ke sini</span>
                        </div>
                        <div id="uploadPreview" class="hidden text-xs text-indigo-300 font-medium"></div>
                        <input id="approval_document" name="document" type="file" class="hidden"
                            accept=".pdf,.jpg,.jpeg,.png"
                            onchange="previewFile(this)">
                    </label>
                </div>

                {{-- Catatan Tambahan --}}
                <div>
                    <label for="approval_notes" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                        Catatan Tambahan <span class="text-slate-600 font-normal normal-case">(opsional)</span>
                    </label>
                    <textarea id="approval_notes" name="notes" rows="2" maxlength="500"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-200 text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500/60 focus:ring-1 focus:ring-indigo-500/30 resize-none transition"
                        placeholder="Catatan tambahan (opsional)..."></textarea>
                </div>

                {{-- Submit --}}
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeApprovalModal()" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-sm font-semibold transition cursor-pointer">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-sm font-bold transition cursor-pointer flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Kirim Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- PAIR RFID MODAL (API Registration) --}}
    <div id="pair-rfid-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <!-- Modal Overlay -->
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm cursor-pointer" onclick="closePairRfidModal()"></div>
        
        <!-- Modal Content Box -->
        <div class="relative bg-slate-900 border border-slate-800/80 w-full max-w-md p-6 rounded-2xl shadow-2xl mx-4 transform transition duration-300 scale-95 opacity-0" id="pair-rfid-modal-content">
            <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 14v1m8-9h1M3 12h1m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l-.707.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                </svg>
                Pair / Daftarkan RFID Aset
            </h3>
            <p class="text-xs text-slate-400 mb-4">Daftarkan tag RFID fisik untuk mempermudah deteksi otomatis sensor IoT reader.</p>
            
            <form action="{{ route('assets.rfid.register-web', $asset->id) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Nama Aset</label>
                        <input type="text" readonly value="{{ $asset->name }}" class="w-full bg-slate-950 border border-slate-850 px-3 py-2 rounded-xl text-slate-500 text-sm outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">ID Asset TI</label>
                        <input type="text" readonly value="{{ $asset->asset_id }}" class="w-full bg-slate-950 border border-slate-850 px-3 py-2 rounded-xl text-slate-500 text-sm font-mono outline-none">
                    </div>
                    <div>
                        <label for="rfid_uid_input" class="block text-[10px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">RFID UID (Hex/Dec)</label>
                        <input type="text" id="rfid_uid_input" name="rfid_uid" required placeholder="Masukkan UID RFID (contoh: A1B2C3D4)" 
                            value="{{ $asset->rfid_uid }}"
                            class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500/80 px-3 py-2.5 rounded-xl text-white text-sm font-mono outline-none transition duration-200">
                    </div>
                </div>
                
                <div class="mt-6 flex gap-2">
                    <button type="button" onclick="closePairRfidModal()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition cursor-pointer flex items-center justify-center gap-1.5">
                        Simpan RFID
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MANUAL MAINTENANCE MODAL --}}
    <div id="manual-maintenance-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm cursor-pointer" onclick="closeManualMaintenanceModal()"></div>
        <div class="relative bg-slate-900 border border-slate-800/80 w-full max-w-lg p-6 rounded-2xl shadow-2xl mx-4 max-h-[90vh] overflow-y-auto transform transition duration-300 scale-95 opacity-0" id="manual-maintenance-modal-content">
            <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Catat Logbook Maintenance
            </h3>
            <p class="text-xs text-slate-400 mb-4">Catat tindakan pemeliharaan yang telah dilakukan untuk aset ini secara mandiri.</p>
            
            <form action="{{ route('assets.maintenance.store', $asset->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4 text-left">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="manual_diagnosis" class="block text-[10px] font-bold uppercase tracking-wider text-slate-350 mb-1">Diagnosa Kerusakan</label>
                            <textarea id="manual_diagnosis" name="diagnosis" required rows="2" placeholder="Masukkan diagnosis kerusakan..."
                                class="w-full bg-slate-950 border border-slate-805 px-3 py-2.5 rounded-xl text-white text-xs outline-none focus:border-indigo-500 transition resize-none"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="manual_cause" class="block text-[10px] font-bold uppercase tracking-wider text-slate-350 mb-1">Penyebab Kerusakan</label>
                            <textarea id="manual_cause" name="cause" required rows="2" placeholder="Masukkan penyebab kerusakan..."
                                class="w-full bg-slate-950 border border-slate-805 px-3 py-2.5 rounded-xl text-white text-xs outline-none focus:border-indigo-500 transition resize-none"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="manual_action_taken" class="block text-[10px] font-bold uppercase tracking-wider text-slate-350 mb-1">Tindakan Perbaikan</label>
                            <textarea id="manual_action_taken" name="action_taken" required rows="2" placeholder="Masukkan tindakan perbaikan yang diambil..."
                                class="w-full bg-slate-950 border border-slate-805 px-3 py-2.5 rounded-xl text-white text-xs outline-none focus:border-indigo-500 transition resize-none"></textarea>
                        </div>
                        <div>
                            <label for="manual_spareparts" class="block text-[10px] font-bold uppercase tracking-wider text-slate-350 mb-1">Sparepart yang Digunakan</label>
                            <input type="text" id="manual_spareparts" name="spareparts" placeholder="RAM, SSD, Charger, dll." 
                                class="w-full bg-slate-950 border border-slate-805 px-3 py-2.5 rounded-xl text-white text-xs outline-none focus:border-indigo-500 transition">
                        </div>
                        <div>
                            <label for="manual_maintenance_date" class="block text-[10px] font-bold uppercase tracking-wider text-slate-350 mb-1">Tanggal Maintenance</label>
                            <input type="date" id="manual_maintenance_date" name="maintenance_date" required value="{{ date('Y-m-d') }}"
                                class="w-full bg-slate-950 border border-slate-805 px-3 py-2 rounded-xl text-white text-xs outline-none focus:border-indigo-500 transition">
                        </div>
                        <div>
                            <label for="manual_photo_before" class="block text-[10px] font-bold uppercase tracking-wider text-slate-350 mb-1">Foto Sebelum (Before)</label>
                            <input type="file" id="manual_photo_before" name="photo_before" accept="image/*"
                                class="w-full bg-slate-950 border border-slate-805 px-3 py-2 rounded-xl text-slate-400 text-xs outline-none transition cursor-pointer">
                        </div>
                        <div>
                            <label for="manual_photo_after" class="block text-[10px] font-bold uppercase tracking-wider text-slate-350 mb-1">Foto Sesudah (After)</label>
                            <input type="file" id="manual_photo_after" name="photo_after" accept="image/*"
                                class="w-full bg-slate-950 border border-slate-805 px-3 py-2 rounded-xl text-slate-400 text-xs outline-none transition cursor-pointer">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="manual_notes" class="block text-[10px] font-bold uppercase tracking-wider text-slate-350 mb-1">Catatan Tambahan</label>
                            <textarea id="manual_notes" name="notes" rows="2" placeholder="Catatan tambahan teknisi..."
                                class="w-full bg-slate-950 border border-slate-805 px-3 py-2.5 rounded-xl text-white text-xs outline-none focus:border-indigo-500 transition resize-none"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-3 p-3 bg-slate-950 border border-slate-850 rounded-xl cursor-pointer hover:bg-slate-900 transition">
                                <input type="checkbox" name="change_status_to_standby" value="1" class="text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-800 h-4.5 w-4.5 rounded cursor-pointer">
                                <span class="text-xs text-slate-300">Setel Status Aset kembali ke <strong>Standby (Siap Pakai)</strong></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex gap-2">
                    <button type="button" onclick="closeManualMaintenanceModal()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition cursor-pointer">
                        Simpan Logbook
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- REQUEST REPLACEMENT MODAL --}}
    <div id="request-replacement-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm cursor-pointer" onclick="closeRequestReplacementModal()"></div>
        <div class="relative bg-slate-900 border border-slate-800/80 w-full max-w-md p-6 rounded-2xl shadow-2xl mx-4 transform transition duration-300 scale-95 opacity-0" id="request-replacement-modal-content">
            <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                Ajukan Penggantian Aset (Replacement)
            </h3>
            <p class="text-xs text-slate-400 mb-4">Ajukan penggantian aset ini ke Administrator karena tidak dapat diperbaiki kembali.</p>
            
            <form action="{{ route('assets.replacement.store', $asset->id) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="replacement_reason" class="block text-[10px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">Alasan Replacement <span class="text-rose-455">*</span></label>
                        <textarea id="replacement_reason" name="reason" required rows="4" placeholder="Jelaskan alasan mengapa aset ini harus diganti (contoh: mainboard terbakar, biaya perbaikan melebihi harga beli)..."
                            class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 rounded-xl px-3 py-2 text-white text-xs outline-none transition resize-none"></textarea>
                    </div>
                </div>
                
                <div class="mt-6 flex gap-2">
                    <button type="button" onclick="closeRequestReplacementModal()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold transition cursor-pointer">
                        Kirim Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

@endsection

@section('scripts')
@if(Auth::check())
<script>
    // ─── Approval Modal ───
    function openApprovalModal(type) {
        const labels = { fraud: '🔴 Fraud / Hilang', write_off: '⚫ Write Off' };
        document.getElementById('approvalType').value = type;
        document.getElementById('approvalTypeDisplay').textContent = labels[type] || type;
        document.getElementById('approvalModalTitle').textContent =
            'Pengajuan ' + (type === 'fraud' ? 'Fraud' : 'Write Off');
        document.getElementById('approvalModal').classList.remove('hidden');
        document.getElementById('approvalModal').classList.add('flex');
        document.getElementById('approval_reason').focus();
    }

    function closeApprovalModal() {
        document.getElementById('approvalModal').classList.add('hidden');
        document.getElementById('approvalModal').classList.remove('flex');
    }

    function previewFile(input) {
        const preview = document.getElementById('uploadPreview');
        const placeholder = document.getElementById('uploadPlaceholder');
        if (input.files && input.files[0]) {
            preview.textContent = '📎 ' + input.files[0].name;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }
    }

    // Auto-open approval modal if need_approval session is set
    @if(session('need_approval'))
        document.addEventListener('DOMContentLoaded', function() {
            openApprovalModal('{{ session('need_approval') }}');
        });
    @endif

    // ─── Scroll helper ───
    function scrollToElement(id) {
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            el.classList.add('ring-2', 'ring-indigo-500/50');
            setTimeout(() => { el.classList.remove('ring-2', 'ring-indigo-500/50'); }, 2000);
        }
    }

    // ─── Pair RFID Modal ───
    function openPairRfidModal() {
        const modal = document.getElementById('pair-rfid-modal');
        const content = document.getElementById('pair-rfid-modal-content');
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
            document.getElementById('rfid_uid_input').focus();
        }, 50);
    }

    // ─── Manual Maintenance Modal ───
    function openManualMaintenanceModal() {
        const modal = document.getElementById('manual-maintenance-modal');
        const content = document.getElementById('manual-maintenance-modal-content');
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
            document.getElementById('manual_diagnosis').focus();
        }, 50);
    }

    function closeManualMaintenanceModal() {
        const modal = document.getElementById('manual-maintenance-modal');
        const content = document.getElementById('manual-maintenance-modal-content');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => { modal.classList.add('hidden'); }, 200);
    }

    // ─── Request Replacement Modal ───
    function openRequestReplacementModal() {
        const modal = document.getElementById('request-replacement-modal');
        const content = document.getElementById('request-replacement-modal-content');
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
            document.getElementById('replacement_reason').focus();
        }, 50);
    }

    function closeRequestReplacementModal() {
        const modal = document.getElementById('request-replacement-modal');
        const content = document.getElementById('request-replacement-modal-content');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => { modal.classList.add('hidden'); }, 200);
    }

    function closePairRfidModal() {
        const modal = document.getElementById('pair-rfid-modal');
        const content = document.getElementById('pair-rfid-modal-content');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => { modal.classList.add('hidden'); }, 200);
    }

    // ─── Category Filter for History ───
    function filterHistory(cat) {
        document.querySelectorAll('.hist-tab').forEach(btn => {
            btn.classList.remove('bg-indigo-600/20', 'text-indigo-300', 'border', 'border-indigo-500/20');
            btn.classList.add('text-slate-400');
        });
        const activeBtn = document.getElementById('tab-' + cat);
        if (activeBtn) {
            activeBtn.classList.add('bg-indigo-600/20', 'text-indigo-300', 'border', 'border-indigo-500/20');
            activeBtn.classList.remove('text-slate-400');
        }
        
        document.querySelectorAll('.history-item').forEach(item => {
            if (cat === 'all' || item.getAttribute('data-category') === cat) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    }
</script>
@endif
@endsection
