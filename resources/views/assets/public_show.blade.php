<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Aset IT - Public Information</title>
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

    <div class="w-full max-w-lg bg-slate-900 border border-slate-800/80 rounded-3xl p-6 md:p-8 shadow-2xl space-y-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 translate-x-12 -translate-y-12 w-36 h-36 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>

        {{-- Header --}}
        <div class="text-center border-b border-slate-800/60 pb-5">
            <div class="w-12 h-12 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 rounded-2xl flex items-center justify-center mx-auto mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-white leading-tight">Informasi Publik Aset</h2>
            <p class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Jurusan Teknologi Informasi PNP</p>
        </div>

        {{-- Asset Photo --}}
        <div>
            @if($asset->photo)
                <img src="{{ asset($asset->photo) }}" class="w-full h-56 object-cover rounded-2xl border border-slate-800 shadow-md" alt="Foto Aset">
            @else
                <div class="w-full h-40 bg-slate-950/60 border border-slate-800/50 rounded-2xl flex flex-col items-center justify-center text-slate-650">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-xs">Foto Aset Belum Diunggah</span>
                </div>
            @endif
        </div>

        {{-- Info Fields --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm border-b border-slate-800/60 pb-6">
            <div>
                <span class="text-slate-500 text-xs block">ID Asset TI</span>
                <strong class="text-slate-200 text-sm font-mono block mt-0.5">{{ $asset->asset_id }}</strong>
            </div>
            <div>
                <span class="text-slate-500 text-xs block">Nomor Inventaris Kementerian</span>
                <strong class="text-slate-200 text-sm block mt-0.5">{{ $asset->government_inventory_number }}</strong>
            </div>
            <div>
                <span class="text-slate-500 text-xs block">Serial Number</span>
                <strong class="text-slate-200 text-sm font-mono block mt-0.5">{{ $asset->serial_number ?? '-' }}</strong>
            </div>
            <div>
                <span class="text-slate-500 text-xs block">Nama Aset</span>
                <strong class="text-slate-200 text-sm block mt-0.5">{{ $asset->name }}</strong>
            </div>
            <div>
                <span class="text-slate-500 text-xs block">Kategori</span>
                <strong class="text-slate-200 text-sm block mt-0.5">{{ $asset->category ?? '-' }}</strong>
            </div>
            <div>
                <span class="text-slate-500 text-xs block">Merk & Model</span>
                <strong class="text-slate-200 text-sm block mt-0.5">{{ $asset->brand ?? '-' }} / {{ $asset->model ?? '-' }}</strong>
            </div>
            <div>
                <span class="text-slate-500 text-xs block">Lokasi Penempatan</span>
                <strong class="text-slate-200 text-sm block mt-0.5">{{ $asset->building ?? '-' }}, Lnt. {{ $asset->floor ?? '-' }}, R. {{ $asset->room ?? '-' }}</strong>
            </div>
            <div>
                <span class="text-slate-500 text-xs block">Status Aset</span>
                <div class="mt-1">
                    @if($asset->status === 'standby')
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 uppercase">Standby</span>
                    @elseif($asset->status === 'digunakan')
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-blue-500/10 border border-blue-500/20 text-blue-400 uppercase">Digunakan</span>
                    @elseif($asset->status === 'maintenance')
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 border border-amber-500/20 text-amber-400 uppercase">Perbaikan</span>
                    @else
                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 border border-rose-500/20 text-rose-400 uppercase">{{ $asset->status }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Action Button --}}
        <div>
            <a href="{{ route('tickets.public.create', $asset->id) }}" class="w-full flex items-center justify-center gap-2 py-3.5 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl font-bold shadow-lg shadow-rose-600/10 hover:shadow-rose-600/20 transition cursor-pointer text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Laporkan Kerusakan Aset
            </a>
        </div>
    </div>
</body>
</html>
