@extends('layouts.public')

@section('title', 'Masuk - Inventaris TI')

@section('content')
<section class="ops-surface ops-public-panel ops-login">
    <header>
        <img src="{{ asset('images/logo-ti-white.png') }}" class="ops-login-logo" width="252" height="80" alt="Logo Jurusan Teknologi Informasi">
        <h1>Masuk ke Inventaris TI</h1>
        <p>Gunakan akun staf atau teknisi.</p>
    </header>

    @if($errors->any())
        <div class="ops-flash" data-tone="error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form action="{{ route('login') }}" method="POST" class="ops-form" id="loginForm">
        @csrf
        <div class="ops-field">
            <label for="username">Nama pengguna</label>
            <input type="text" id="username" name="username" required value="{{ old('username') }}" autocomplete="username"
                   @error('username') aria-invalid="true" @enderror>
        </div>
        <div class="ops-field">
            <label for="password">Kata sandi</label>
            <div class="ops-password-control">
                <input type="password" id="password" name="password" required autocomplete="current-password">
                <button type="button" id="togglePassword" class="ops-button ops-button-secondary" aria-controls="password" aria-pressed="false">Tampilkan</button>
            </div>
        </div>
        <button type="submit" class="ops-button ops-button-primary" id="loginButton">Masuk</button>
    </form>
</section>
@endsection

@section('scripts')
<script>
    const passwordToggle = document.getElementById('togglePassword');
    const loginPassword = document.getElementById('password');
    const loginButton = document.getElementById('loginButton');
    passwordToggle.addEventListener('click', () => {
        const visible = loginPassword.type === 'password';
        loginPassword.type = visible ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', String(visible));
        passwordToggle.textContent = visible ? 'Sembunyikan' : 'Tampilkan';
    });
    document.getElementById('loginForm').addEventListener('submit', () => {
        loginButton.disabled = true;
        loginButton.textContent = 'Memeriksa akun…';
    });
    window.addEventListener('pageshow', () => {
        loginButton.disabled = false;
        loginButton.textContent = 'Masuk';
    });
</script>
@endsection
