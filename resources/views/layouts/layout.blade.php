<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Manajemen Aset IT')</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        /* Transition animations for collapsible sidebar */
        #app-sidebar {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #sidebar-overlay {
            transition: opacity 0.3s ease-in-out;
        }
        
        /* Tablet & Desktop Collapsed States */
        @media (min-width: 768px) {
            #app-sidebar.collapsed {
                width: 76px !important;
            }
            #app-sidebar.collapsed .sidebar-text {
                display: none !important;
            }
            #app-sidebar.collapsed .sidebar-header-logo {
                display: none !important;
            }
            #app-sidebar.collapsed .sidebar-header-compact {
                display: flex !important;
            }
            #app-sidebar.collapsed .sidebar-footer-user {
                display: none !important;
            }
            #app-sidebar.collapsed .sidebar-footer-user-compact {
                display: block !important;
            }
            #app-sidebar.collapsed nav {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
            #app-sidebar.collapsed nav a {
                justify-content: center !important;
                padding-left: 0rem !important;
                padding-right: 0rem !important;
                width: 2.75rem !important;
                height: 2.75rem !important;
                margin-left: auto !important;
                margin-right: auto !important;
                border-radius: 0.75rem !important;
            }
            #app-sidebar.collapsed nav a svg {
                margin: 0 !important;
            }
            #app-sidebar.collapsed .sidebar-divider {
                display: none !important;
            }
            #app-sidebar.collapsed #sidebar-collapse-btn svg {
                transform: rotate(180deg);
            }
        }
    </style>
    @yield('styles')
</head>
<body class="h-full text-slate-100 flex flex-col md:flex-row overflow-hidden bg-slate-950">

    <!-- Mobile Overlay for Drawer -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-40 hidden transition-opacity duration-300 opacity-0 md:hidden"></div>

    <!-- Sidebar -->
    <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-50 w-[280px] -translate-x-full transition-all duration-300 ease-in-out bg-slate-900 border-r border-slate-800 flex flex-col justify-between shrink-0
        md:static md:translate-x-0 md:flex">
        <div>
            <!-- Sidebar Header / Logo -->
            <div class="p-4 md:p-6 border-b border-slate-800 flex items-center justify-between gap-3 shrink-0">
                <!-- Full Logo -->
                <div class="sidebar-header-logo flex items-center gap-3">
                    <div class="p-2.5 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-indigo-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg leading-tight bg-gradient-to-r from-white to-slate-300 bg-clip-text text-transparent">IT Assets</h1>
                        <span class="text-[10px] uppercase tracking-wider text-indigo-400 font-semibold">Management</span>
                    </div>
                </div>

                <!-- Compact Logo (visible on tablet only when collapsed) -->
                <div class="sidebar-header-compact hidden p-2.5 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-indigo-400 mx-auto" title="IT Asset Management">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>

                <!-- Collapse Sidebar Button (Desktop / Tablet) -->
                <button type="button" id="sidebar-collapse-btn" class="hidden md:flex p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg border border-slate-800 transition cursor-pointer" title="Collapse Sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Mobile Drawer Close Button (X) -->
                <button type="button" id="sidebar-close-btn" class="md:hidden p-2 text-slate-400 hover:text-white hover:bg-slate-850 rounded-xl transition cursor-pointer" title="Tutup Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="p-4 space-y-1.5">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition group-hover:scale-110 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z" />
                    </svg>
                    <span class="sidebar-text">Dashboard</span>
                </a>

                <a href="{{ route('assets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('assets.index', 'assets.create', 'assets.show', 'assets.edit') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition group-hover:scale-110 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span class="sidebar-text">Daftar Aset</span>
                </a>

                <a href="{{ route('bundles.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('bundles.*') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition group-hover:scale-110 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span class="sidebar-text">Asset Bundle</span>
                </a>

                <a href="{{ route('assets.scan') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('assets.scan') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition group-hover:scale-110 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    <span class="sidebar-text">Scan QR Kamera</span>
                </a>


                <a href="{{ route('assets.rfid.validate') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('assets.rfid.validate') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition group-hover:scale-110 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 14v1m8-9h1M3 12h1m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l-.707.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                    </svg>
                    <span class="sidebar-text">Scan RFID</span>
                </a>

                <a href="{{ route('tickets.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('tickets.*') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition group-hover:scale-110 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h6m-6-4h6" />
                    </svg>
                    <span class="sidebar-text">Tiket Perbaikan</span>
                </a>

                <a href="{{ route('locations.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('locations.index', 'locations.create', 'locations.edit') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition group-hover:scale-110 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="sidebar-text">Manajemen Lokasi</span>
                </a>

                <a href="{{ route('assets.history') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('assets.history') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition group-hover:scale-110 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="sidebar-text">Riwayat Aktivitas</span>
                </a>

                {{-- Admin-only menu --}}
                @if(Auth::user() && Auth::user()->isAdmin())
                    <div class="pt-3 mt-3 border-t border-slate-800 sidebar-divider">
                        <span class="px-4 text-[10px] uppercase tracking-widest text-slate-500 font-semibold">Administrator</span>
                    </div>
                    <a href="{{ route('admin.approvals.index') }}" class="flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('admin.approvals.*') ? 'bg-amber-600/20 text-amber-300 font-medium border border-amber-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition group-hover:scale-110 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="sidebar-text">Persetujuan Aset</span>
                        </div>
                        @php $pendingApprovalCount = \App\Models\AssetApproval::where('status','pending')->count(); @endphp
                        @if($pendingApprovalCount > 0)
                            <span class="sidebar-text shrink-0 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-bold rounded-full bg-amber-500 text-slate-900">{{ $pendingApprovalCount }}</span>
                        @endif
                    </a>
                    
                    <a href="{{ route('admin.assets.bulk.create') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('admin.assets.bulk.*') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition group-hover:scale-110 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z" />
                        </svg>
                        <span class="sidebar-text">Bulk Create Aset</span>
                    </a>

                    <a href="{{ route('teknisi.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('teknisi.*') ? 'bg-indigo-600 text-white font-medium shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition group-hover:scale-110 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="sidebar-text">Manajemen Teknisi</span>
                    </a>
                @endif
            </nav>
        </div>

        <!-- Sidebar Footer - User Info -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/40">
            <!-- Full User Info -->
            <div class="sidebar-footer-user flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white shadow-md shrink-0">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                </div>
                <div class="overflow-hidden">
                    <h4 class="font-semibold text-sm text-slate-200 truncate">{{ Auth::user()->name ?? '-' }}</h4>
                    <p class="text-[11px] text-slate-400 truncate">{{ Auth::user()->position ?? Auth::user()->role ?? '-' }}</p>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium mt-1 uppercase
                        {{ Auth::user()->isAdmin() ? 'bg-amber-500/10 border border-amber-500/25 text-amber-300' : 'bg-indigo-500/10 border border-indigo-500/25 text-indigo-300' }}">
                        {{ Auth::user()->role ?? '-' }}
                    </span>
                </div>
            </div>

            <!-- Compact User Info -->
            <div class="sidebar-footer-user-compact hidden mb-4 text-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white text-sm shadow-md mx-auto" title="{{ Auth::user()->name ?? '-' }}">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                </div>
            </div>

            <!-- Full Logout -->
            <form action="{{ route('logout') }}" method="POST" id="logoutForm" class="sidebar-footer-user">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 px-3 bg-slate-800 hover:bg-rose-950/30 hover:border-rose-900/50 hover:text-rose-300 border border-slate-700 rounded-xl text-xs font-medium text-slate-300 transition-all duration-200 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </button>
            </form>

            <!-- Compact Logout -->
            <form action="{{ route('logout') }}" method="POST" id="logoutFormCompact" class="sidebar-footer-user-compact hidden">
                @csrf
                <button type="submit" class="w-10 h-10 flex items-center justify-center bg-slate-800 hover:bg-rose-950/30 hover:border-rose-900/50 hover:text-rose-300 border border-slate-700 rounded-xl text-slate-300 transition-all duration-200 cursor-pointer mx-auto" title="Logout">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden bg-slate-950">
        <!-- Top Nav / Responsive Mobile Header -->
        <header class="bg-slate-900/40 backdrop-blur-md border-b border-slate-800/80 px-4 md:px-6 py-3.5 md:py-4 flex items-center justify-between shrink-0 relative z-20">
            <div class="flex items-center gap-3">
                <!-- Mobile Hamburger (shown only on mobile/tablet) -->
                <button id="mobile-hamburger" class="md:hidden p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition cursor-pointer" title="Buka Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                
                <!-- Desktop Page Title Info -->
                <div>
                    <span class="hidden md:inline-block text-xs font-semibold uppercase tracking-wider text-slate-500">Sistem Manajemen Aset IT</span>
                    <h2 class="text-base md:text-xl font-bold text-white leading-tight">@yield('page_title', 'Dashboard')</h2>
                </div>
            </div>

            <!-- Mobile Specific Brand Title -->
            <div class="md:hidden flex items-center gap-2">
                <span class="font-bold text-sm bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">IT Asset Management</span>
            </div>

            <!-- Right Area (Status Info / Profile Avatar Shortcut) -->
            <div class="flex items-center gap-3">
                <!-- Status Check (Desktop/Tablet) -->
                <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1.5 rounded-full font-medium">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                    Sistem Aktif
                </span>

                <!-- Mobile Profile Avatar (toggles sidebar drawer) -->
                <button onclick="document.getElementById('mobile-hamburger').click()" class="md:hidden w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white text-xs shadow-md" title="Profil & Keluar">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                </button>
            </div>
        </header>

        <!-- Dynamic Page Content -->
        <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
            <!-- Flash Notifications -->
            @if(session('success'))
                @if(session('newly_created_id'))
                    <div class="mb-6 p-4.5 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl text-indigo-300 text-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-lg shadow-indigo-500/5">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-emerald-400 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div>
                                <span class="font-bold text-white block">Aset Berhasil Ditambahkan</span>
                                <span class="text-xs text-slate-400 mt-0.5">Aset <strong class="text-indigo-300">"{{ session('newly_created_name') }}"</strong> berhasil disimpan ke database.</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('assets.index', ['quick_filter' => 'terbaru', 'highlight_id' => session('newly_created_id')]) }}" 
                                class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-md transition">
                                Lihat Asset Baru
                            </a>
                            <a href="{{ route('assets.create') }}" 
                                class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                                Tambah Asset Lagi
                            </a>
                            <a href="{{ route('dashboard') }}" 
                                class="px-3.5 py-2 bg-slate-900 hover:bg-slate-850 border border-slate-800 text-slate-400 hover:text-slate-200 rounded-xl text-xs font-semibold transition">
                                Kembali ke Dashboard
                            </a>
                        </div>
                    </div>
                @else
                    <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-300 text-sm flex items-center gap-3 shadow-lg shadow-emerald-500/5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-rose-300 text-sm flex items-center gap-3 shadow-lg shadow-rose-500/5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="mb-6 p-4 bg-blue-500/10 border border-blue-500/20 rounded-2xl text-blue-300 text-sm flex items-center gap-3 shadow-lg shadow-blue-500/5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('app-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const hamburger = document.getElementById('mobile-hamburger');
            const closeBtn = document.getElementById('sidebar-close-btn');
            const collapseBtn = document.getElementById('sidebar-collapse-btn');

            // Load collapsed preference from localStorage
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }

            // Slide in drawer on mobile
            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => {
                    overlay.classList.add('opacity-100');
                    overlay.classList.remove('opacity-0');
                }, 50);
            }

            // Slide out drawer on mobile
            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.remove('opacity-100');
                overlay.classList.add('opacity-0');
                setTimeout(() => {
                    overlay.classList.add('hidden');
                }, 300);
            }

            if (hamburger) hamburger.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);

            // Toggle layout collapsed state on laptop/tablet
            if (collapseBtn) {
                collapseBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
                });
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
