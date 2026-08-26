@extends('layouts.layout')

@section('title', 'Edit Teknisi - IT Asset Management')
@section('page_title', 'Edit Akun Teknisi')

@section('content')
<div class="max-w-2xl">
    <!-- Back link -->
    <a href="{{ route('teknisi.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-indigo-400 transition mb-6 group">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Kembali ke Daftar Teknisi
    </a>

    <!-- Form Card -->
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl shadow-lg p-5 md:p-6 lg:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white text-sm shadow-md shrink-0">
                {{ strtoupper(substr($teknisi->name, 0, 2)) }}
            </div>
            <div>
                <h3 class="text-lg font-semibold text-white">{{ $teknisi->name }}</h3>
                <p class="text-xs text-slate-400">
                    <code class="px-1.5 py-0.5 bg-slate-800 border border-slate-700 rounded text-indigo-300 font-mono">{{ $teknisi->username }}</code>
                    &mdash; Terdaftar {{ $teknisi->created_at->format('d M Y') }}
                </p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-rose-300 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('teknisi.update', $teknisi) }}" method="POST" class="space-y-5" id="editTeknisiForm">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Nama Lengkap <span class="text-rose-400">*</span></label>
                <input type="text" id="name" name="name" required value="{{ old('name', $teknisi->name) }}"
                    class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                    placeholder="Masukkan nama lengkap...">
            </div>

            <!-- Username -->
            <div>
                <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Username <span class="text-rose-400">*</span></label>
                <input type="text" id="username" name="username" required value="{{ old('username', $teknisi->username) }}"
                    class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition font-mono"
                    placeholder="contoh: john_doe">
                <p class="text-xs text-slate-500 mt-1.5">Hanya huruf, angka, dash, dan underscore.</p>
            </div>

            <!-- Position -->
            <div>
                <label for="position" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Jabatan</label>
                <input type="text" id="position" name="position" value="{{ old('position', $teknisi->position) }}"
                    class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                    placeholder="Contoh: Teknisi Lab Komputer...">
            </div>

            <!-- Location -->
            <div>
                <label for="location" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Lokasi Kerja</label>
                <div class="relative">
                    <select id="location" name="location"
                        class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white outline-none transition appearance-none cursor-pointer">
                        <option value="" class="bg-slate-800">Pilih Lokasi (Opsional)...</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->kode_ruangan }}" class="bg-slate-800" {{ old('location', $teknisi->location) == $loc->kode_ruangan ? 'selected' : '' }}>
                                {{ $loc->kode_ruangan }} - {{ $loc->nama_ruangan }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Info Note -->
            <div class="p-3.5 bg-slate-800/50 border border-slate-700/60 rounded-xl flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-slate-400">Untuk mereset password, gunakan tombol <strong class="text-amber-300">Reset Password</strong> pada halaman daftar teknisi.</p>
            </div>

            <!-- Actions -->
            <div class="flex flex-col-reverse sm:flex-row items-center gap-3 pt-4 border-t border-slate-800 w-full">
                <a href="{{ route('teknisi.index') }}"
                    class="w-full sm:w-auto text-center px-6 py-3 bg-slate-800 border border-slate-700 hover:bg-slate-700 text-slate-300 font-medium rounded-xl transition cursor-pointer text-sm">
                    Batal
                </a>
                <button type="submit" id="btn-update-teknisi"
                    class="w-full sm:w-auto justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition-all duration-200 cursor-pointer flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
