<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Detail Aset - Jurusan Teknologi Informasi')</title>
    <meta name="description" content="Halaman informasi publik detail aset IT - Jurusan Teknologi Informasi">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at 50% 0%, rgba(79, 70, 229, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 0% 100%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
                        #080a10;
            min-height: 100vh;
        }
        
        .public-container {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
    </style>
    @yield('styles')
</head>
<body class="text-slate-100 min-h-screen flex flex-col justify-between overflow-x-hidden">

    <!-- Glowing background accents -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[300px] bg-gradient-to-b from-indigo-500/10 to-transparent blur-3xl pointer-events-none z-0"></div>

    <div class="relative z-10 flex flex-col min-h-screen">
        <!-- Public Navigation / Header -->
        <header class="bg-slate-900/40 backdrop-blur-md border-b border-slate-800/80 px-4 md:px-8 py-4 sticky top-0 z-50">
            <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
                <!-- Left: JTI Logo & Title -->
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-ti-white.png') }}" alt="Logo Jurusan Teknologi Informasi" class="h-10 w-auto object-contain" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%236366f1\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10\' /></svg>'">
                    <div class="border-l border-slate-800 pl-3">
                        <h1 class="font-bold text-sm sm:text-base leading-tight bg-gradient-to-r from-white to-slate-300 bg-clip-text text-transparent">IT Asset Scan</h1>
                        <span class="text-[9px] sm:text-[10px] uppercase tracking-wider text-indigo-400 font-semibold block">Jurusan Teknologi Informasi</span>
                    </div>
                </div>

                <!-- Right: Action Button -->
                <div>
                    @if(Auth::check())
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition flex items-center gap-2 shadow-md shadow-indigo-600/10 cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="hidden sm:inline">Ke Dashboard</span> Admin
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white rounded-xl text-xs font-semibold transition flex items-center gap-2 cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Login Staff
                        </a>
                    @endif
                </div>
            </div>
        </header>

        <!-- Main Content Layout -->
        <main class="flex-1 max-w-6xl w-full mx-auto p-4 md:p-8">
            <!-- Flash Notifications -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-300 text-sm flex items-center gap-3 shadow-lg shadow-emerald-500/5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
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

    <!-- Public Footer -->
    <footer class="bg-slate-950 border-t border-slate-900 py-6 text-center text-xs text-slate-500 relative z-10 shrink-0">
        <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p>&copy; {{ date('Y') }} Jurusan Teknologi Informasi. Hak Cipta Dilindungi.</p>
            <div class="flex gap-4">
                <span class="text-indigo-500/60 font-semibold tracking-wider uppercase text-[10px]">IT Assets Management</span>
                <span class="text-slate-800">|</span>
                <span class="text-slate-500">Sistem Informasi Inventaris</span>
            </div>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>
