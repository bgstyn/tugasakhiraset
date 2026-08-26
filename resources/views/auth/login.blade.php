<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IT Asset Management System</title>
    <meta name="description" content="Login ke IT Asset Management System - Jurusan Teknologi Informasi">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at 10% 20%, rgba(79, 70, 229, 0.12) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(124, 58, 237, 0.12) 0%, transparent 40%),
                        #080a10;
            min-height: 100vh;
        }

        .login-card {
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            background: rgba(17, 24, 39, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5),
                        inset 0 1px 0 rgba(255, 255, 255, 0.03);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            border-color: rgba(99, 102, 241, 0.12);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6);
        }

        .input-field {
            background: rgba(5, 7, 12, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-field:hover {
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(5, 7, 12, 0.6);
        }

        .input-field:focus {
            background: rgba(5, 7, 12, 0.8);
            border-color: rgba(99, 102, 241, 0.65);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .btn-login {
            background: linear-gradient(135deg, #4f46e5 0%, #6d28d9 100%);
            box-shadow: 0 4px 15px -3px rgba(99, 102, 241, 0.2);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-login:hover:not(:disabled) {
            box-shadow: 0 8px 20px -3px rgba(99, 102, 241, 0.35);
            transform: translateY(-1px);
            filter: brightness(1.06);
        }

        .btn-login:active:not(:disabled) {
            transform: translateY(1px);
            filter: brightness(0.96);
        }

        .show-password-btn {
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .show-password-btn:hover {
            color: #a5b4fc;
        }

        .show-password-btn:active {
            transform: scale(0.9);
        }

        @keyframes float-orb {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-15px) scale(1.01); }
        }

        .animate-orb-1 {
            animation: float-orb 12s ease-in-out infinite;
        }
        .animate-orb-2 {
            animation: float-orb 14s ease-in-out infinite 1.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in-section {
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4 sm:p-6 text-slate-100 overflow-x-hidden relative min-h-screen">
    <!-- Decorative Floating Orbs -->
    <div class="absolute top-[10%] left-[15%] w-72 h-72 sm:w-96 sm:h-96 bg-indigo-600/8 rounded-full blur-[90px] pointer-events-none animate-orb-1"></div>
    <div class="absolute bottom-[10%] right-[15%] w-72 h-72 sm:w-96 sm:h-96 bg-purple-600/8 rounded-full blur-[90px] pointer-events-none animate-orb-2"></div>

    <div class="w-full max-w-[440px] relative z-10 fade-in-section flex flex-col justify-center">
        <!-- Unified Login Card -->
        <div class="login-card rounded-[20px] p-8 sm:p-10">
            <!-- Header (Logo, Judul, Subtitle) inside the Card -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-5">
                    <!-- Fallback to a neat SVG if the TI logo is missing -->
                    <img src="{{ asset('images/logo-ti-white.png') }}" alt="Logo Jurusan" class="h-14 w-auto object-contain" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%236366f1\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10\' /></svg>'">
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white leading-snug">IT Asset Management System</h1>
                <p class="text-indigo-400 text-xs font-semibold mt-1.5 tracking-wider uppercase">Jurusan Teknologi Informasi</p>
                <p class="text-slate-500 text-[10px] font-bold tracking-widest mt-2 uppercase opacity-80">Sistem Internal Manajemen Aset</p>
            </div>

            <!-- Flash Messages (Styled Elegantly inside the card) -->
            @if(session('info'))
                <div class="mb-6 px-4 py-3 bg-indigo-500/5 border border-indigo-500/20 rounded-xl text-indigo-300 text-xs flex items-center gap-3 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium text-left leading-relaxed">{{ session('info') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 px-4 py-3 bg-rose-500/5 border border-rose-500/25 rounded-xl text-rose-300 text-xs flex items-center gap-3 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="font-medium text-left leading-relaxed">{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-6" id="loginForm">
                @csrf

                <!-- Username -->
                <div class="space-y-2">
                    <label for="username" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 text-left">Username</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-4 text-slate-500 flex items-center justify-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input type="text" id="username" name="username" required value="{{ old('username') }}"
                            class="input-field w-full pl-12 pr-4 h-[48px] rounded-xl text-white placeholder-slate-600 outline-none text-sm font-medium"
                            placeholder="Masukkan username Anda"
                            autocomplete="username">
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 text-left">Password</label>
                    <div class="relative flex items-center">
                        <div class="absolute left-4 text-slate-500 flex items-center justify-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" required
                            class="input-field w-full pl-12 pr-12 h-[48px] rounded-xl text-white placeholder-slate-600 outline-none text-sm font-medium"
                            placeholder="Masukkan password Anda"
                            autocomplete="current-password">
                        <button type="button" id="togglePassword" class="show-password-btn absolute right-4 flex items-center justify-center text-slate-500 hover:text-indigo-400 cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" id="eyeIcon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="loginButton"
                    class="btn-login w-full h-[48px] px-4 text-white font-semibold rounded-xl cursor-pointer text-sm flex items-center justify-center gap-2 mt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>Masuk ke Sistem</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.05 6.05m3.828 3.828l4.242 4.242m0 0L17.95 17.95M14.12 14.12L17.95 17.95m0 0l2.121 2.121M3 3l18 18" />';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
            }
        });

        // Focus username on page load
        document.getElementById('username').focus();

        // Submit form loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginButton');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-not-allowed');
                btn.innerHTML = `
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memverifikasi...</span>
                `;
            }
        });
    </script>
</body>
</html>
