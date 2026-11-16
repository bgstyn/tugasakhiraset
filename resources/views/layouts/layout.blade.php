<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inventaris TI')</title>
    @include('layouts.assets')
    @yield('styles')
</head>
<body class="ops-app">
    <a href="#main-content" class="ops-skip">Lewati ke konten</a>

    @php
        $navigation = [
            ['dashboard', 'Ringkasan', ['dashboard'], 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z'],
            ['assets.index', 'Daftar aset', ['assets.index', 'assets.create', 'assets.show', 'assets.edit'], 'M8 4h8l4 4v12H4V4h4zm0 7h8m-8 4h8'],
            ['bundles.index', 'Paket aset', ['bundles.*'], 'M4 7h16v13H4zm2 0V4h12v3m-8 5h4'],
            ['assets.scan', 'Scan aset', ['assets.scan'], 'M4 9V4h5m6 0h5v5m0 6v5h-5m-6 0H4v-5m3-3h10'],
            ['assets.rfid.validate', 'Validasi RFID', ['assets.rfid.validate', 'assets.rfid.history'], 'M5 8a10 10 0 0 0 0 8m3-6a5 5 0 0 0 0 4m8-4a5 5 0 0 1 0 4m3-6a10 10 0 0 1 0 8m-7-4h.01'],
            ['tickets.index', 'Tiket perbaikan', ['tickets.index', 'tickets.show'], 'M4 6h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4zm9 2v8'],
            ['locations.index', 'Lokasi', ['locations.*'], 'M19 10c0 5-7 10-7 10S5 15 5 10a7 7 0 1 1 14 0zm-5 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0z'],
            ['assets.history', 'Riwayat aktivitas', ['assets.history'], 'M21 12a9 9 0 1 1-3-6.7M21 4v5h-5m-4-1v5l3 2'],
        ];
        $adminNavigation = [
            ['admin.approvals.index', 'Persetujuan aset', ['admin.approvals.*'], 'M4 12l4 4L20 4m0 8v8H4V4h10'],
            ['admin.assets.bulk.create', 'Tambah aset massal', ['admin.assets.bulk.*'], 'M4 4h6v6H4zM4 14h6v6H4zM14 4h6v6h-6zm3 10v6m-3-3h6'],
            ['teknisi.index', 'Teknisi', ['teknisi.*'], 'M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m16 0v-2a4 4 0 0 0-2-3.4M14 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z'],
        ];
    @endphp

    <div id="sidebar-overlay" class="ops-sidebar-overlay" hidden></div>
    <aside id="app-sidebar" class="ops-sidebar" aria-label="Sidebar">
        <div class="ops-sidebar-header">
            <a class="ops-brand" href="{{ route('dashboard') }}" aria-label="Inventaris TI, ringkasan">
                <span class="ops-brand-mark" aria-hidden="true">TI</span>
                <span class="ops-nav-label">Inventaris TI <small>Politeknik Negeri Padang</small></span>
            </a>
            <button type="button" id="sidebar-close-btn" class="ops-icon-button ops-mobile-only" aria-label="Tutup menu">
                <span aria-hidden="true">×</span>
            </button>
        </div>

        <nav class="ops-navigation" aria-label="Menu utama">
            @include('layouts.nav-links', ['links' => $navigation])
            @if(auth()->user()?->isAdmin())
                <div class="ops-nav-heading ops-nav-label">Administrasi</div>
                @include('layouts.nav-links', ['links' => $adminNavigation, 'pendingApprovals' => \App\Models\AssetApproval::where('status', 'pending')->count()])
            @endif
        </nav>

        <div class="ops-sidebar-footer">
            <div class="ops-profile">
                <span class="ops-profile-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'U', 0, 2)) }}</span>
                <div class="ops-nav-label">
                    <strong>{{ auth()->user()?->name ?? '—' }}</strong>
                    <small>{{ auth()->user()?->position ?? auth()->user()?->role ?? '—' }}</small>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="ops-sidebar-logout" aria-label="Keluar dari akun" title="Keluar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 4H4v16h6m-1-8h11m-4-4 4 4-4 4"/>
                    </svg>
                    <span class="ops-nav-label">Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="ops-main" id="app-main">
        <header class="ops-topbar">
            <button type="button" class="ops-icon-button ops-mobile-only" id="mobile-hamburger" aria-controls="app-sidebar" aria-expanded="false" aria-label="Buka menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <button type="button" class="ops-icon-button ops-desktop-only" id="sidebar-collapse-btn" aria-controls="app-sidebar" aria-expanded="true" aria-label="Ciutkan menu" title="Ciutkan menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18v16H3zm6 0v16m6-12-3 4 3 4"/>
                </svg>
            </button>
            <h1>@yield('page_title', 'Ringkasan')</h1>
            @unless(request()->routeIs('assets.scan'))
                <a href="{{ route('assets.scan') }}" class="ops-button ops-button-secondary ops-scan-link">Scan aset</a>
            @endunless
        </header>

        <main id="main-content" class="ops-content" tabindex="-1">
            @foreach(['success', 'error', 'info'] as $type)
                @if(session($type))
                    <div class="ops-flash" data-tone="{{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}">
                        <span>{{ session($type) }}</span>
                        @if($type === 'success' && session('newly_created_id'))
                            <div class="ops-inline-actions">
                                <a href="{{ route('assets.index', ['quick_filter' => 'terbaru', 'highlight_id' => session('newly_created_id')]) }}" class="ops-button ops-button-primary">Lihat aset baru</a>
                                <a href="{{ route('assets.create') }}" class="ops-button ops-button-secondary">Tambah aset lagi</a>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach

            @yield('content')
        </main>
    </div>
    @yield('scripts')
</body>
</html>
