<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Inventaris TI')</title>
    @include('layouts.assets')
    @yield('styles')
</head>
<body class="ops-public">
    <a class="ops-skip" href="#main-content">Lewati ke konten</a>
    <header class="ops-public-bar">
        <div>
            <div class="ops-brand">
                @unless(request()->routeIs('login'))
                    <img src="{{ asset('images/logo-ti-white.png') }}" alt="" width="40" height="36">
                @endunless
                <span>Inventaris TI <small>Politeknik Negeri Padang</small></span>
            </div>
            <div class="ops-inline-actions">
                @include('partials.theme-toggle')
                @if(auth()->check())
                    <a href="{{ route('dashboard') }}" class="ops-button ops-button-secondary">Ringkasan</a>
                @elseif(! request()->routeIs('login'))
                    <a href="{{ route('login') }}" class="ops-button ops-button-secondary">Masuk staf</a>
                @endif
            </div>
        </div>
    </header>
    <main id="main-content" class="ops-public-content" tabindex="-1">
        @yield('content')
    </main>
    <footer class="ops-public-footer">Jurusan Teknologi Informasi · Politeknik Negeri Padang</footer>
    @include('partials.notifications')
    @yield('scripts')
</body>
</html>
