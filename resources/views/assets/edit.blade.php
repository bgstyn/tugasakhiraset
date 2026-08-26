@extends('layouts.layout')

@section('title', 'Edit Aset - IT Asset Management')
@section('page_title', 'Edit Aset IT')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('assets.show', $asset->id) }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Detail Aset
        </a>
    </div>

    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-6 md:p-8 shadow-lg">
        <div class="mb-6 border-b border-slate-800 pb-4">
            <h3 class="text-lg font-bold text-white">Ubah Informasi Aset IT</h3>
            <p class="text-xs text-slate-400 mt-1">Sesuaikan informasi aset. Setiap perubahan akan dicatat ke dalam log aktivitas.</p>
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

        <form action="{{ route('assets.update', $asset->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
                <!-- Name (Full Width) -->
                <div class="md:col-span-2 lg:col-span-3">
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Nama Aset IT</label>
                    <input type="text" id="name" name="name" required value="{{ old('name', $asset->name) }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: Laptop ThinkPad L14 Gen 3">
                </div>

                <!-- ID Asset TI (col-span-2) -->
                <div class="md:col-span-2 lg:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-350 mb-2">ID Asset TI (Permanen)</label>
                    <input type="text" readonly value="{{ $asset->asset_id }}"
                        class="w-full px-4 py-3 bg-slate-950/60 border border-slate-850 rounded-xl text-slate-400 outline-none transition font-mono text-sm">
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Kategori Aset IT</label>
                    <div class="relative">
                        <select id="category" name="category" required
                            class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white outline-none transition appearance-none cursor-pointer">
                            <option value="" disabled class="bg-[#1f1736]">Pilih Kategori...</option>
                            @foreach(['Laptop', 'PC Desktop', 'Monitor', 'Printer / Scanner', 'Server', 'Networking', 'Aksesoris', 'Lainnya'] as $cat)
                                <option value="{{ $cat }}" class="bg-[#1f1736]" {{ old('category', $asset->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Government Inventory Number -->
                <div class="md:col-span-2 lg:col-span-2">
                    <label for="government_inventory_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Nomor Inventaris Kementerian</label>
                    <input type="text" id="government_inventory_number" name="government_inventory_number" required value="{{ old('government_inventory_number', $asset->government_inventory_number) }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: 3.10.01.05.00123">
                </div>

                <!-- Year -->
                <div>
                    <label for="year" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Tahun Pembelian / Pengadaan</label>
                    <input type="number" id="year" name="year" required value="{{ old('year', $asset->year) }}" min="2000" max="{{ date('Y') + 10 }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: 2026">
                </div>

                <!-- Current User -->
                <div class="md:col-span-2 lg:col-span-2">
                    <label for="current_user" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">User Saat Ini (Pegawai/Staf)</label>
                    <input type="text" id="current_user" name="current_user" value="{{ old('current_user', $asset->current_user) }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Nama Pegawai yang memegang (Kosongkan jika Standby)">
                </div>

                <!-- Serial Number Fisik -->
                <div>
                    <label for="serial_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Serial Number Fisik (SN)</label>
                    <input type="text" id="serial_number" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition font-mono"
                        placeholder="Contoh: PF2B9XXX">
                </div>

                <!-- Building -->
                <div>
                    <label for="building" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Gedung</label>
                    <input type="text" id="building" name="building" required value="{{ old('building', $asset->building) }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: Gedung TI">
                </div>

                <!-- Floor -->
                <div>
                    <label for="floor" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Lantai</label>
                    <input type="text" id="floor" name="floor" required value="{{ old('floor', $asset->floor) }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: 1, 2, 3">
                </div>

                <!-- Room -->
                <div>
                    <label for="room" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Ruangan</label>
                    <input type="text" id="room" name="room" required value="{{ old('room', $asset->room) }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: E310">
                </div>

                <!-- Brand -->
                <div>
                    <label for="brand" id="brand-label" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Merek / Brand</label>
                    <input type="text" id="brand" name="brand" required value="{{ old('brand', $asset->brand) }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: Lenovo, ASUS, HP, Dell">
                </div>

                <!-- Model -->
                <div class="md:col-span-2 lg:col-span-2">
                    <label for="model" id="model-label" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Tipe / Model</label>
                    <input type="text" id="model" name="model" required value="{{ old('model', $asset->model) }}"
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white placeholder-slate-500 outline-none transition"
                        placeholder="Contoh: ThinkPad L14 Gen 3">
                </div>

                <!-- Hardware Specs Section Header -->
                <div class="md:col-span-2 lg:col-span-3 pt-4 border-t border-slate-800/80">
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
                        placeholder="Contoh: Intel Core i5, RAM 16GB, SSD 512GB">{{ old('specification', $asset->specification) }}</textarea>
                </div>

                <!-- Foto Aset -->
                <div class="md:col-span-2 lg:col-span-3">
                    <label for="photo" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Foto Aset IT <span class="text-[10px] text-slate-500 font-normal">(Opsional, Maks 5MB)</span></label>
                    @if($asset->photo)
                        <div class="mb-3 flex items-center gap-3 bg-slate-950 p-3 rounded-xl border border-slate-800 w-fit">
                            <img src="{{ asset($asset->photo) }}" class="h-14 w-20 object-cover rounded-lg border border-slate-800" alt="Foto Aset">
                            <span class="text-xs text-slate-400">File saat ini terunggah</span>
                        </div>
                    @endif
                    <input type="file" id="photo" name="photo" accept="image/*"
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 text-slate-400 text-sm outline-none transition">
                </div>

                <!-- Status -->
                <div class="md:col-span-2 lg:col-span-3">
                    <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Status Aset</label>
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
                                    {{ old('status', $asset->status) == $val ? 'checked' : '' }}>
                                <span class="text-xs font-medium text-slate-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex flex-col-reverse sm:flex-row items-center justify-end gap-3 w-full">
                <a href="{{ route('assets.show', $asset->id) }}" class="w-full sm:w-auto text-center px-5 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-sm font-semibold transition cursor-pointer">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition cursor-pointer">
                    Perbarui Aset
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
