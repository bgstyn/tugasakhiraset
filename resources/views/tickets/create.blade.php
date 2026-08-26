<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporkan Kerusakan Aset - IT Asset Management</title>
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
        <div class="absolute right-0 top-0 translate-x-12 -translate-y-12 w-36 h-36 bg-rose-500/5 rounded-full blur-3xl pointer-events-none"></div>

        {{-- Header --}}
        <div class="border-b border-slate-800/60 pb-5 flex items-center gap-3">
            <a href="{{ route('assets.public.short-show', $asset->asset_id) }}" class="p-2 bg-slate-950/60 border border-slate-850 hover:bg-slate-800 rounded-xl text-slate-400 hover:text-white transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h2 class="text-lg font-bold text-white leading-tight">Formulir Laporan Kerusakan</h2>
                <p class="text-xs text-slate-400 mt-0.5">Aset: <strong class="text-indigo-400">{{ $asset->name }}</strong> ({{ $asset->asset_id }})</p>
            </div>
        </div>

        @if($errors->any())
            <div class="p-4 bg-rose-500/10 border border-rose-500/25 rounded-xl text-rose-300 text-xs">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form action="{{ route('tickets.public.store', $asset->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            {{-- Reporter Name --}}
            <div>
                <label for="reporter_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-350 mb-1.5">Nama Pelapor <span class="text-rose-500">*</span></label>
                <input type="text" id="reporter_name" name="reporter_name" required value="{{ old('reporter_name') }}"
                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-650 outline-none text-sm transition"
                    placeholder="Masukkan nama lengkap Anda">
            </div>

            {{-- Reporter Contact --}}
            <div>
                <label for="reporter_contact" class="block text-xs font-semibold uppercase tracking-wider text-slate-355 mb-1.5">Email / No HP <span class="text-slate-500 font-normal">(Opsional)</span></label>
                <input type="text" id="reporter_contact" name="reporter_contact" value="{{ old('reporter_contact') }}"
                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-650 outline-none text-sm transition"
                    placeholder="Contoh: pelapor@email.com / 08123456789">
            </div>

            {{-- Priority --}}
            <div>
                <label for="priority" class="block text-xs font-semibold uppercase tracking-wider text-slate-350 mb-1.5">Tingkat Prioritas <span class="text-rose-500">*</span></label>
                <select id="priority" name="priority" required
                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 text-slate-300 text-sm outline-none transition cursor-pointer">
                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Rendah (Low)</option>
                    <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Sedang (Medium)</option>
                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Tinggi (High)</option>
                    <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Kritis (Critical)</option>
                </select>
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-350 mb-1.5">Deskripsi Kerusakan <span class="text-rose-500">*</span></label>
                <textarea id="description" name="description" required rows="4"
                    class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-650 outline-none text-sm transition"
                    placeholder="Jelaskan secara detail bagian yang rusak dan kendala yang dialami..."></textarea>
            </div>

            {{-- Photo Upload --}}
            <div>
                <label for="photo" class="block text-xs font-semibold uppercase tracking-wider text-slate-350 mb-1.5">Foto Bukti Kerusakan <span class="text-slate-500 font-normal">(Opsional, Maks 5MB)</span></label>
                <input type="file" id="photo" name="photo" accept="image/*"
                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 text-slate-400 text-xs outline-none transition cursor-pointer">
            </div>

            {{-- Submit --}}
            <div class="pt-2">
                <button type="submit" class="w-full py-3.5 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl font-bold shadow-lg shadow-rose-600/10 hover:shadow-rose-600/20 transition cursor-pointer text-sm">
                    Kirim Laporan Kerusakan
                </button>
            </div>
        </form>
    </div>
</body>
</html>
