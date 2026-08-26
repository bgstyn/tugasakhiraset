<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Berhasil Terkirim - IT Asset Management</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen py-10 px-4 flex flex-col items-center justify-center">

    <div class="w-full max-w-lg bg-slate-900 border border-slate-800/80 rounded-3xl p-6 md:p-8 shadow-2xl text-center space-y-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 translate-x-12 -translate-y-12 w-36 h-36 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>

        {{-- Icon success --}}
        <div class="w-16 h-16 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-4 animate-[bounce_1s_infinite]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <div class="space-y-2">
            <h2 class="text-xl font-bold text-white leading-tight">Laporan Kerusakan Terkirim</h2>
            <p class="text-xs text-slate-400 max-w-xs mx-auto">Terima kasih atas laporan Anda. Tiket penanganan perbaikan aset telah berhasil dibuat oleh sistem.</p>
        </div>

        {{-- Ticket Number Box --}}
        <div class="bg-slate-950 border border-slate-800 p-5 rounded-2xl space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest block">Nomor Tiket Anda</span>
            <strong class="text-2xl font-mono text-indigo-400 block tracking-wider select-all">{{ $ticket->ticket_number }}</strong>
            <span class="text-[10px] text-slate-500 block">Salin nomor tiket di atas untuk melacak status perbaikan secara berkala.</span>
        </div>

        {{-- Asset Details --}}
        <div class="text-left text-xs bg-slate-950/40 p-4 rounded-xl border border-slate-850 space-y-2">
            <div class="flex justify-between border-b border-slate-800 pb-1.5">
                <span class="text-slate-550">Nama Aset</span>
                <span class="text-slate-300 font-semibold">{{ $ticket->asset->name }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-800 pb-1.5">
                <span class="text-slate-550">ID Asset TI</span>
                <span class="text-slate-300 font-mono">{{ $ticket->asset->asset_id }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-800 pb-1.5">
                <span class="text-slate-550">Pelapor</span>
                <span class="text-slate-300 font-semibold">{{ $ticket->reporter_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-550">Prioritas</span>
                <span class="text-rose-400 font-semibold uppercase">{{ $ticket->priority }}</span>
            </div>
        </div>

        {{-- Action --}}
        <div class="pt-2 flex gap-2">
            <a href="{{ route('assets.public.short-show', $ticket->asset->asset_id) }}" class="flex-1 py-3.5 bg-slate-800 hover:bg-slate-750 text-slate-300 border border-slate-700 rounded-2xl text-xs font-semibold transition cursor-pointer">
                Kembali ke Info Aset
            </a>
        </div>
    </div>
</body>
</html>
