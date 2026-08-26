@extends('layouts.layout')

@section('title', 'Edit Lokasi - IT Asset Management')
@section('page_title', 'Ubah Data Lokasi')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('locations.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Daftar Lokasi
        </a>
    </div>

    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-6 md:p-8 shadow-lg">
        <div class="mb-6 border-b border-slate-800 pb-4">
            <h3 class="text-lg font-bold text-white">Ubah Informasi Lokasi Kampus</h3>
            <p class="text-xs text-slate-400 mt-1">Lakukan penyesuaian detail gedung atau ruangan. Pastikan data tetap akurat.</p>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/25 rounded-xl text-rose-300 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('locations.update', $location->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
                <!-- Room Code / Kode Ruangan -->
                <div>
                    <label for="kode_ruangan" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Kode Ruangan</label>
                    <input type="text" id="kode_ruangan" name="kode_ruangan" required value="{{ old('kode_ruangan', $location->kode_ruangan) }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition font-mono uppercase"
                        placeholder="Contoh: E301, C101A">
                </div>

                <!-- Room Name / Nama Ruangan -->
                <div>
                    <label for="nama_ruangan" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Nama Ruangan</label>
                    <input type="text" id="nama_ruangan" name="nama_ruangan" required value="{{ old('nama_ruangan', $location->nama_ruangan) }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: Labor Pemrograman 1, Studio (SBSN)">
                </div>

                <!-- Floor / Lantai -->
                <div>
                    <label for="lantai" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Lantai</label>
                    <input type="number" id="lantai" name="lantai" required value="{{ old('lantai', $location->lantai) }}" min="1" max="10"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: 1, 2, 3 (terisi otomatis dari kode ruangan)">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex flex-col-reverse sm:flex-row items-center justify-end gap-3 w-full">
                <a href="{{ route('locations.index') }}" class="w-full sm:w-auto text-center px-5 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-sm font-semibold transition cursor-pointer">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const kodeInput = document.getElementById('kode_ruangan');
        const lantaiInput = document.getElementById('lantai');

        kodeInput.addEventListener('input', function() {
            // Find the first digit in the room code
            const match = kodeInput.value.match(/[A-Za-z](\d)/);
            if (match) {
                lantaiInput.value = match[1];
            }
        });
    });
</script>
@endsection
