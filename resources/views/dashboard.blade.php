@extends('layouts.layout')

@section('title', 'Dashboard - IT Asset Management')
@section('page_title', 'Dashboard Statistik')

@section('content')
<!-- Welcome banner -->
<div class="mb-6 md:mb-8 p-5 md:p-6 lg:p-8 bg-gradient-to-r from-indigo-900/60 to-purple-900/40 border border-indigo-800/40 rounded-3xl relative overflow-hidden shadow-2xl">
    <div class="absolute -right-20 -top-20 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl"></div>
    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-bold text-white md:text-2xl">Selamat datang kembali, {{ session('staff_it.name') }}!</h3>
            <p class="text-slate-300 text-xs md:text-sm mt-1">Kelola, lacak, dan monitoring aset IT di lokasi {{ session('staff_it.location') }} secara cepat dan terintegrasi.</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap w-full lg:w-auto">
            <a href="{{ route('assets.create') }}" class="w-full sm:w-auto justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition flex items-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Aset
            </a>
            <a href="{{ route('assets.scan') }}" class="w-full sm:w-auto justify-center px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-sm font-medium transition flex items-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
                Scan QR
            </a>
            <a href="{{ route('assets.rfid.validate') }}" class="w-full sm:w-auto justify-center px-4 py-2.5 bg-slate-800/80 hover:bg-slate-700/80 text-emerald-400 border border-slate-700 rounded-xl text-sm font-medium transition flex items-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Validasi RFID
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">
    <!-- Total Asset -->
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg hover:border-slate-700 transition duration-300 relative overflow-hidden group">
        <div class="absolute right-0 bottom-0 translate-x-4 translate-y-4 text-slate-800/30 group-hover:scale-110 transition duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-28 w-28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
        </div>
        <div class="flex items-center gap-4 mb-4 relative z-10">
            <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 rounded-xl shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <div>
                <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider block">Total Aset IT</span>
                <h4 class="text-2xl md:text-3xl font-extrabold text-white mt-0.5">{{ $stats['total'] }}</h4>
            </div>
        </div>
        <div class="text-[11px] text-slate-400 relative z-10 flex items-center gap-1.5">
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
            Semua aset terdaftar
        </div>
    </div>

    <!-- Standby -->
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg hover:border-emerald-950/60 transition duration-300 relative overflow-hidden group">
        <div class="absolute right-0 bottom-0 translate-x-4 translate-y-4 text-emerald-950/30 group-hover:scale-110 transition duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-28 w-28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="flex items-center gap-4 mb-4 relative z-10">
            <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div>
                <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider block">Status Standby</span>
                <h4 class="text-2xl md:text-3xl font-extrabold text-white mt-0.5">{{ $stats['standby'] }}</h4>
            </div>
        </div>
        <div class="text-[11px] text-slate-400 relative z-10 flex items-center gap-1.5">
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            Aset siap digunakan
        </div>
    </div>

    <!-- Digunakan -->
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg hover:border-blue-950/60 transition duration-300 relative overflow-hidden group">
        <div class="absolute right-0 bottom-0 translate-x-4 translate-y-4 text-blue-950/30 group-hover:scale-110 transition duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-28 w-28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </div>
        <div class="flex items-center gap-4 mb-4 relative z-10">
            <div class="p-3 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-xl shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider block">Sedang Digunakan</span>
                <h4 class="text-2xl md:text-3xl font-extrabold text-white mt-0.5">{{ $stats['digunakan'] }}</h4>
            </div>
        </div>
        <div class="text-[11px] text-slate-400 relative z-10 flex items-center gap-1.5">
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-400"></span>
            Aset aktif di tangan user
        </div>
    </div>

    <!-- Maintenance -->
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg hover:border-amber-950/60 transition duration-300 relative overflow-hidden group">
        <div class="absolute right-0 bottom-0 translate-x-4 translate-y-4 text-amber-950/30 group-hover:scale-110 transition duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-28 w-28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>
        <div class="flex items-center gap-4 mb-4 relative z-10">
            <div class="p-3 bg-amber-500/10 border border-amber-500/20 text-amber-400 rounded-xl shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div>
                <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider block">Maintenance</span>
                <h4 class="text-2xl md:text-3xl font-extrabold text-white mt-0.5">{{ $stats['maintenance'] }}</h4>
            </div>
        </div>
        <div class="text-[11px] text-slate-400 relative z-10 flex items-center gap-1.5">
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-400"></span>
            Sedang diperbaiki/service
        </div>
    </div>
</div>

<!-- RFID Statistics Grid -->
<div class="mb-6 md:mb-8">
    <div class="flex items-center justify-between mb-4">
        <h4 class="font-semibold text-lg text-slate-200 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Statistik RFID Aset
        </h4>
        <a href="{{ route('assets.rfid.history') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">Lihat Riwayat RFID</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Asset -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-slate-700 transition">
            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider block">Total Asset</span>
            <h4 class="text-2xl font-extrabold text-white mt-1">{{ $rfidStats['total_assets'] }}</h4>
            <div class="text-[10px] text-slate-500 mt-2">Seluruh aset terdaftar</div>
        </div>
        <!-- Asset dengan RFID -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-slate-700 transition">
            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider block">Dengan RFID</span>
            <h4 class="text-2xl font-extrabold text-indigo-400 mt-1">{{ $rfidStats['with_rfid'] }}</h4>
            <div class="text-[10px] text-indigo-450 mt-2">Memiliki tag terhubung</div>
        </div>
        <!-- Asset tanpa RFID -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-slate-700 transition">
            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider block">Tanpa RFID</span>
            <h4 class="text-2xl font-extrabold text-slate-400 mt-1">{{ $rfidStats['without_rfid'] }}</h4>
            <div class="text-[10px] text-slate-550 mt-2">Belum dipasang RFID tag</div>
        </div>
        <!-- RFID Aktif -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-emerald-900/40 transition">
            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider block">RFID Aktif</span>
            <h4 class="text-2xl font-extrabold text-emerald-400 mt-1">{{ $rfidStats['rfid_aktif'] }}</h4>
            <div class="text-[10px] text-emerald-500/80 mt-2">Tag status aktif</div>
        </div>
        <!-- RFID Belum Terdaftar -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-amber-900/40 transition">
            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider block">Belum Terdaftar</span>
            <h4 class="text-2xl font-extrabold text-amber-400 mt-1">{{ $rfidStats['rfid_belum_terdaftar'] }}</h4>
            <div class="text-[10px] text-amber-500/80 mt-2">Belum memiliki tag</div>
        </div>
    </div>
</div>

<!-- Secondary Statistics Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 mb-6 md:mb-8">
    <!-- Left column: Remaining 3 status cards -->
    <div class="lg:col-span-1 flex flex-col gap-6">
        <h4 class="font-semibold text-lg text-slate-200 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Status Kritis & Arsip
        </h4>

        <!-- Rusak -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 flex items-center justify-between hover:border-rose-900/60 transition group">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h5 class="text-sm font-medium text-slate-300">Rusak</h5>
                    <p class="text-xs text-slate-500">Tidak berfungsi normal</p>
                </div>
            </div>
            <span class="text-2xl font-bold text-slate-100 group-hover:text-rose-400 transition">{{ $stats['rusak'] }}</span>
        </div>

        <!-- Fraud -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 flex items-center justify-between hover:border-red-900/60 transition group">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div>
                    <h5 class="text-sm font-medium text-slate-300">Fraud / Hilang</h5>
                    <p class="text-xs text-slate-500">Selisih stok / indikasi fraud</p>
                </div>
            </div>
            <span class="text-2xl font-bold text-slate-100 group-hover:text-red-400 transition">{{ $stats['fraud'] }}</span>
        </div>

        <!-- Write Off -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 flex items-center justify-between hover:border-slate-700 transition group">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-slate-500/10 border border-slate-500/20 text-slate-400 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h5 class="text-sm font-medium text-slate-300">Write Off</h5>
                    <p class="text-xs text-slate-500">Dihapus dari daftar aset aktif</p>
                </div>
            </div>
            <span class="text-2xl font-bold text-slate-100 group-hover:text-slate-400 transition">{{ $stats['write_off'] }}</span>
        </div>
    </div>

    <!-- Right column: Recent activities logs (Timeline) -->
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800/80 rounded-2xl p-6 shadow-lg flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-6">
                <h4 class="font-semibold text-lg text-slate-200 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Aktivitas Terbaru
                </h4>
                <a href="{{ route('assets.history') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">Lihat Semua Log</a>
            </div>

            @if($recentHistories->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-700 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm">Belum ada riwayat aktivitas yang tercatat.</p>
                </div>
            @else
                <div class="flow-root">
                    <ul class="-mb-8">
                        @foreach($recentHistories as $history)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-800" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <!-- Action Icon Circle -->
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-slate-900 
                                                @if($history->action == 'create') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                                @elseif($history->action == 'update') bg-blue-500/10 text-blue-400 border border-blue-500/20
                                                @else bg-rose-500/10 text-rose-400 border border-rose-500/20 @endif">
                                                
                                                @if($history->action == 'create')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                @elseif($history->action == 'update')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-xs text-slate-300">
                                                    <span class="font-semibold text-slate-200">
                                                        @if($history->action == 'create') Menambahkan
                                                        @elseif($history->action == 'update') Memperbarui
                                                        @else Menghapus @endif
                                                    </span> 
                                                    aset <a href="{{ $history->asset_id ? route('assets.show', $history->asset_id) : '#' }}" class="text-indigo-400 hover:text-indigo-300 font-medium">{{ $history->asset_name }}</a>
                                                </p>
                                                <span class="text-[10px] text-slate-500 flex items-center gap-1.5 mt-1">
                                                    Oleh: <strong class="text-slate-400">{{ $history->changed_by_name }}</strong> ({{ $history->changed_by_position }} - {{ $history->changed_by_location }})
                                                </span>
                                            </div>
                                            <div class="text-right text-[10px] whitespace-nowrap text-slate-500 pt-0.5">
                                                {{ $history->created_at->diffForHumans() }}
                                            </div>
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

<!-- Asset Distribution by Room and Floor Statistics -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 mb-6 md:mb-8">
    <!-- Distribution by Room -->
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg">
        <h4 class="font-semibold text-base md:text-lg text-slate-200 flex items-center gap-2 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Distribusi Aset per Ruangan
        </h4>
        <div class="space-y-3 max-h-80 overflow-y-auto pr-2 custom-scrollbar">
            @forelse($roomStats as $room)
                <div class="flex items-center justify-between p-3.5 bg-slate-950/40 border border-slate-800/60 rounded-xl hover:border-slate-700/60 transition group">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-slate-200 group-hover:text-indigo-400 transition">{{ $room->kode_ruangan }}</span>
                        <span class="text-xs text-slate-500 mt-0.5">{{ $room->nama_ruangan }}</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
                        {{ $room->assets_count }} Aset
                    </span>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-8 text-slate-500">
                    <p class="text-sm">Tidak ada ruangan terdaftar.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Distribution by Floor -->
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg">
        <h4 class="font-semibold text-base md:text-lg text-slate-200 flex items-center gap-2 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            Distribusi Aset per Lantai
        </h4>
        <div class="space-y-3">
            @forelse($floorStats as $floor)
                <div class="flex items-center justify-between p-3.5 bg-slate-950/40 border border-slate-800/60 rounded-xl hover:border-slate-700/60 transition group">
                    <span class="text-sm font-semibold text-slate-200 group-hover:text-indigo-400 transition">Lantai {{ $floor->lantai }}</span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
                        {{ $floor->assets_count }} Aset
                    </span>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-8 text-slate-500">
                    <p class="text-sm">Belum ada aset terdaftar di lantai mana pun.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
