@extends('layouts.layout')

@section('title', 'Bulk Asset Creation - IT Asset Management')
@section('page_title', 'Bulk Asset Creation')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 animate-[fadeIn_0.4s_ease-out_forwards]">
    <div class="mb-4">
        <a href="{{ route('assets.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Daftar Aset
        </a>
    </div>

    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-6 md:p-8 shadow-xl relative overflow-hidden">
        <!-- Background glow decor -->
        <div class="absolute right-0 top-0 translate-x-12 -translate-y-12 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="mb-6 border-b border-slate-800/60 pb-5">
            <h3 class="text-xl font-bold text-white">Buat Banyak Aset IT Sekaligus</h3>
            <p class="text-xs text-slate-400 mt-1">Masukkan spesifikasi master aset. Sistem akan memprediksi kode inventaris berurutan dan menghasilkan kode aset unik untuk setiap unit.</p>
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

        <form action="{{ route('admin.assets.bulk.preview') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Asset Base Name -->
                <div class="md:col-span-2 lg:col-span-3">
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Nama Aset Master</label>
                    <input type="text" id="name" name="name" required value="{{ old('name') }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: Monitor LG 24MK600">
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Kategori Aset</label>
                    <div class="relative">
                        <select id="category" name="category" required
                            class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white outline-none transition appearance-none cursor-pointer">
                            <option value="" disabled {{ !old('category') ? 'selected' : '' }} class="bg-[#1f1736]">Pilih Kategori...</option>
                            @foreach(['Laptop', 'PC Desktop', 'Monitor', 'Printer / Scanner', 'Server', 'Networking', 'Aksesoris / Lainnya', 'Lainnya'] as $cat)
                                <option value="{{ $cat }}" class="bg-[#1f1736]" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Brand -->
                <div>
                    <label for="brand" id="brand-label" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Merek / Brand</label>
                    <input type="text" id="brand" name="brand" required value="{{ old('brand') }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: LG, Lenovo, Dell">
                </div>

                <!-- Model -->
                <div>
                    <label for="model" id="model-label" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Tipe / Model</label>
                    <input type="text" id="model" name="model" required value="{{ old('model') }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: 24MK600">
                </div>

                <!-- Count / Jumlah -->
                <div>
                    <label for="count" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Jumlah Aset (Unit)</label>
                    <input type="number" id="count" name="count" required value="{{ old('count', 5) }}" min="1" max="100"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Jumlah unit (maks 100)">
                </div>

                <!-- Building -->
                <div>
                    <label for="building" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Gedung</label>
                    <input type="text" id="building" name="building" required value="{{ old('building', 'Gedung TI') }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: Gedung TI">
                </div>

                <!-- Floor -->
                <div>
                    <label for="floor" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Lantai</label>
                    <input type="text" id="floor" name="floor" required value="{{ old('floor', '1') }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: 1, 2, 3">
                </div>

                <!-- Room -->
                <div>
                    <label for="room" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Ruangan</label>
                    <input type="text" id="room" name="room" required value="{{ old('room') }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: E310">
                </div>

                <!-- Year -->
                <div>
                    <label for="year" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Tahun Pengadaan</label>
                    <input type="number" id="year" name="year" required value="{{ old('year', date('Y')) }}" min="2000" max="{{ date('Y') + 10 }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: 2026">
                </div>

                <!-- Current User -->
                <div class="md:col-span-2">
                    <label for="current_user" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">User Saat Ini <span class="text-[10px] text-slate-500 font-normal">(Opsional - Pegawai/Staf)</span></label>
                    <input type="text" id="current_user" name="current_user" value="{{ old('current_user') }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Nama Pegawai / Staf yang bertanggung jawab">
                </div>

                <!-- Hardware Specs Title -->
                <div class="md:col-span-2 lg:col-span-3 pt-4 border-t border-slate-800/60">
                    <h4 class="text-sm font-bold text-white flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 5h10a3 3 0 013 3v10a3 3 0 01-3 3H7a3 3 0 01-3-3V8a3 3 0 013-3z" />
                        </svg>
                        Spesifikasi Aset <span class="text-[10px] text-slate-500 font-normal">(Opsional)</span>
                    </h4>
                </div>

                <!-- Specification -->
                <div class="md:col-span-2 lg:col-span-3">
                    <label for="specification" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Spesifikasi Detail</label>
                    <textarea id="specification" name="specification" rows="3"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: Intel Core i5, RAM 16GB, SSD 512GB">{{ old('specification') }}</textarea>
                </div>

                <!-- Status awal -->
                <div class="md:col-span-2 lg:col-span-3 pt-2">
                    <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Status Awal Aset</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach([
                            'standby' => 'Standby (Siap Pakai)',
                            'digunakan' => 'Sedang Digunakan',
                            'maintenance' => 'Dalam Perbaikan',
                            'rusak' => 'Rusak (Off)',
                            'fraud' => 'Hilang / Indikasi Fraud',
                            'write_off' => 'Write Off (Diarsipkan)'
                        ] as $val => $label)
                            <label class="flex items-center gap-3 p-3.5 bg-slate-950 hover:bg-slate-900 border border-slate-800 rounded-xl cursor-pointer transition relative">
                                <input type="radio" name="status" value="{{ $val }}" class="text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-950 bg-slate-950 border-slate-800 h-4.5 w-4.5 cursor-pointer"
                                    {{ old('status', 'standby') == $val ? 'checked' : '' }}>
                                <span class="text-xs font-medium text-slate-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pt-5 border-t border-slate-800 flex flex-col-reverse sm:flex-row items-center justify-end gap-3 w-full">
                <a href="{{ route('assets.index') }}" class="w-full sm:w-auto text-center px-6 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-sm font-semibold transition cursor-pointer">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto px-7 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition cursor-pointer flex items-center justify-center gap-2">
                    Pratinjau (Preview)
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category');
        const brandInput = document.getElementById('brand');
        const modelInput = document.getElementById('model');
        const brandLabel = document.getElementById('brand-label');
        const modelLabel = document.getElementById('model-label');

        function updateRequiredFields() {
            if (categorySelect.value === 'PC Desktop') {
                brandInput.removeAttribute('required');
                modelInput.removeAttribute('required');
                brandLabel.innerHTML = 'Merek / Brand <span class="text-[10px] text-slate-500 font-normal lowercase">(opsional untuk PC Desktop)</span>';
                modelLabel.innerHTML = 'Tipe / Model <span class="text-[10px] text-slate-500 font-normal lowercase">(opsional untuk PC Desktop)</span>';
            } else {
                brandInput.setAttribute('required', 'required');
                modelInput.setAttribute('required', 'required');
                brandLabel.innerHTML = 'Merek / Brand';
                modelLabel.innerHTML = 'Tipe / Model';
            }
        }

        if (categorySelect && brandInput && modelInput) {
            categorySelect.addEventListener('change', updateRequiredFields);
            updateRequiredFields();
        }
    });
</script>
@endsection
