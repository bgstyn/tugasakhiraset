@extends('layouts.layout')

@section('title', 'Validasi RFID - IT Asset Management')
@section('page_title', 'Validasi RFID')

@section('styles')
<style>
    .pulse-glow {
        box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7);
        animation: pulse 1.8s infinite;
    }
    @keyframes pulse {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 15px rgba(99, 102, 241, 0);
        }
        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
        }
    }
</style>
@endsection

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Dashboard
        </a>
    </div>

    {{-- Scan Panel --}}
    <div class="bg-slate-900 border border-slate-800/80 rounded-3xl p-6 md:p-8 shadow-xl text-center relative overflow-hidden">
        <div class="absolute right-0 top-0 translate-x-12 -translate-y-12 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="mb-6">
            <div id="scanner-circle" class="w-20 h-20 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 rounded-full flex items-center justify-center mx-auto mb-4 pulse-glow">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 14v1m8-9h1M3 12h1m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l-.707.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white">Validasi & Scan RFID Aset</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Silakan dekatkan kartu/tag RFID ke reader. Form akan mendeteksi input secara otomatis.</p>
        </div>

        {{-- Hidden Input Form --}}
        <form id="rfidForm" onsubmit="handleRfidSubmit(event)" class="max-w-xs mx-auto">
            <div class="relative">
                <input type="text" id="rfid_input" name="rfid_uid" autofocus required autocomplete="off"
                    class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-600 outline-none text-center font-mono text-sm tracking-widest transition"
                    placeholder="Menunggu scan RFID...">
            </div>
            <button type="submit" class="hidden">Submit</button>
        </form>

        <div class="mt-4">
            <button type="button" onclick="focusInput()" class="text-[10px] font-semibold text-slate-500 hover:text-indigo-400 transition cursor-pointer">
                ⚠️ Klik di sini jika scanner tidak merespon (fokus ulang input)
            </button>
        </div>
    </div>

    {{-- Validation Result Container --}}
    <div id="result-container" class="hidden transition duration-300">
        {{-- Filled by JS --}}
    </div>
</div>
@endsection

@section('scripts')
<script>
    const rfidInput = document.getElementById('rfid_input');
    const resultContainer = document.getElementById('result-container');
    const scannerCircle = document.getElementById('scanner-circle');

    // Keep input focused automatically
    document.addEventListener('click', () => {
        focusInput();
    });

    function focusInput() {
        rfidInput.focus();
    }

    function handleRfidSubmit(event) {
        event.preventDefault();
        const rfidUid = rfidInput.value.trim();
        if (!rfidUid) return;

        // Visual feedback
        scannerCircle.classList.remove('pulse-glow', 'bg-indigo-500/10', 'text-indigo-400', 'border-indigo-500/20');
        scannerCircle.classList.add('bg-indigo-600/20', 'text-indigo-200', 'border-indigo-500/40');
        
        // Hide previous result
        resultContainer.classList.add('opacity-0', 'scale-95');
        
        // Query database via AJAX
        fetch(`{{ route('assets.rfid.validate') }}?rfid_uid=${encodeURIComponent(rfidUid)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok && response.status !== 404) {
                throw new Error('Server Error');
            }
            return response.json();
        })
        .then(data => {
            scannerCircle.classList.add('pulse-glow');
            resultContainer.classList.remove('hidden');
            
            setTimeout(() => {
                resultContainer.classList.remove('opacity-0', 'scale-95');
                resultContainer.classList.add('opacity-100', 'scale-100');
            }, 50);

            if (data.success) {
                // Success: asset found!
                scannerCircle.classList.remove('bg-indigo-600/20', 'text-indigo-200', 'border-indigo-500/40');
                scannerCircle.classList.add('bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20');
                
                const asset = data.data;
                resultContainer.innerHTML = `
                    <div class="bg-slate-900 border border-emerald-500/20 rounded-3xl p-6 shadow-xl animate-[fadeIn_0.3s_ease-out]">
                        <div class="flex items-start justify-between mb-4 border-b border-slate-800/60 pb-4">
                            <div class="text-left">
                                <span class="text-slate-500 text-[10px] uppercase font-bold tracking-wider">Aset Ditemukan</span>
                                <h4 class="text-lg font-bold text-white mt-0.5">${asset.name}</h4>
                            </div>
                            <span class="px-3 py-1 bg-emerald-500/15 border border-emerald-500/20 text-emerald-400 text-xs font-semibold rounded-full uppercase tracking-wider">
                                Registered
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-left text-xs mb-6">
                            <div>
                                <span class="text-slate-550 block">ID Asset TI</span>
                                <strong class="text-slate-200 text-sm font-mono block mt-0.5">${asset.asset_id}</strong>
                            </div>
                            <div>
                                <span class="text-slate-550 block">RFID UID</span>
                                <strong class="text-indigo-400 text-sm font-mono block mt-0.5">${asset.rfid_uid}</strong>
                            </div>
                            <div>
                                <span class="text-slate-550 block">Lokasi Penempatan</span>
                                <span class="text-slate-300 block mt-0.5">${asset.building}, Lnt. ${asset.floor}, R. ${asset.location}</span>
                            </div>
                            <div>
                                <span class="text-slate-550 block">User Penanggung Jawab</span>
                                <span class="text-slate-300 block mt-0.5">${asset.current_user}</span>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <a href="${asset.url}" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold text-center transition shadow-lg shadow-indigo-600/10 cursor-pointer">
                                Buka Detail Lengkap
                            </a>
                            <button type="button" onclick="resetValidator()" class="py-3 px-5 bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                                Reset
                            </button>
                        </div>
                    </div>
                `;
            } else {
                // Error: asset not found!
                scannerCircle.classList.remove('bg-indigo-600/20', 'text-indigo-200', 'border-indigo-500/40');
                scannerCircle.classList.add('bg-rose-500/10', 'text-rose-400', 'border-rose-500/20');
                
                resultContainer.innerHTML = `
                    <div class="bg-slate-900 border border-rose-500/20 rounded-3xl p-6 shadow-xl text-center animate-[fadeIn_0.3s_ease-out]">
                        <div class="w-12 h-12 bg-rose-500/10 border border-rose-500/25 rounded-2xl flex items-center justify-center mx-auto text-rose-400 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-rose-400">RFID Belum Terdaftar</h4>
                        <p class="text-xs text-slate-450 mt-1 max-w-xs mx-auto">Tag dengan UID <code class="text-slate-300 font-mono font-bold bg-slate-950/80 px-2 py-0.5 rounded border border-slate-850 inline-block">${rfidUid}</code> tidak dikaitkan ke aset mana pun di database.</p>
                        <div class="mt-4 flex gap-2">
                            <button type="button" onclick="resetValidator()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                                Scan Ulang
                            </button>
                        </div>
                    </div>
                `;
            }
        })
        .catch(err => {
            console.error('Validation error:', err);
            scannerCircle.classList.add('pulse-glow');
            alert('Gagal menghubungkan ke server.');
        });
    }

    function resetValidator() {
        rfidInput.value = '';
        resultContainer.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            resultContainer.classList.add('hidden');
        }, 200);
        
        scannerCircle.classList.remove('bg-rose-500/10', 'text-rose-400', 'border-rose-500/20', 'bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20', 'bg-indigo-600/20', 'text-indigo-200', 'border-indigo-500/40');
        scannerCircle.classList.add('bg-indigo-500/10', 'text-indigo-400', 'border-indigo-500/20');
        
        focusInput();
    }

    // Auto-focus on load
    window.addEventListener('load', () => {
        focusInput();
    });
</script>
@endsection
