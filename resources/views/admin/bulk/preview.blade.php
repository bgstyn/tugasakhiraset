@extends('layouts.layout')

@section('title', 'Pratinjau Bulk Asset - IT Asset Management')
@section('page_title', 'Pratinjau Pembuatan Aset')

@section('content')
<div class="space-y-6 animate-[fadeIn_0.4s_ease-out_forwards]">
    
    {{-- Header / Back --}}
    <div class="flex items-center justify-between">
        <a href="javascript:history.back()" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Formulir
        </a>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-semibold rounded-full animate-pulse">
            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
            Pratinjau Draft Aset (Belum Disimpan)
        </span>
    </div>

    {{-- Info Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg">
        <div>
            <span class="block text-slate-550 text-[10px] uppercase font-semibold tracking-wider">Nama Aset Master</span>
            <span class="text-sm font-bold text-white mt-0.5 block">{{ $validated['name'] }}</span>
        </div>
        <div>
            <span class="block text-slate-550 text-[10px] uppercase font-semibold tracking-wider">Kategori / Merek / Model</span>
            <span class="text-sm font-semibold text-slate-200 mt-0.5 block">
                {{ $validated['category'] }} / {{ $validated['brand'] ?? '-' }} / {{ $validated['model'] ?? '-' }}
            </span>
        </div>
        <div>
            <span class="block text-slate-550 text-[10px] uppercase font-semibold tracking-wider">Lokasi Penempatan</span>
            <span class="text-sm font-semibold text-slate-200 mt-0.5 block">
                {{ $validated['building'] }}, Lantai {{ $validated['floor'] }}, Ruangan {{ $validated['room'] }}
            </span>
        </div>
        <div>
            <span class="block text-slate-550 text-[10px] uppercase font-semibold tracking-wider">Jumlah & Tahun Pengadaan</span>
            <span class="text-sm font-bold text-indigo-400 mt-0.5 block">
                {{ $validated['count'] }} Unit (Tahun {{ $validated['year'] }})
            </span>
        </div>
    </div>

    {{-- Main List Grid --}}
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-800/60 flex items-center justify-between">
            <div>
                <h4 class="font-bold text-white text-base">Daftar Unit Aset IT Baru</h4>
                <p class="text-xs text-slate-400 mt-0.5">Lengkapi Nomor Inventaris Kementerian dan Serial Number untuk masing-masing unit.</p>
            </div>
            <span class="px-3 py-1 bg-slate-950 border border-slate-800 text-slate-400 text-xs font-semibold rounded-xl">
                {{ count($previewItems) }} Unit Terdaftar
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-950 text-slate-400 font-semibold text-xs border-b border-slate-850 uppercase tracking-wider">
                        <th class="py-4.5 px-6 text-center w-16">#</th>
                        <th class="py-4.5 px-6">ID Asset TI (Otomatis)</th>
                        <th class="py-4.5 px-6">No. Inventaris Kementerian (Wajib)</th>
                        <th class="py-4.5 px-6">Serial Number Pabrik (SN - Opsional)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-850 text-slate-300">
                    @foreach($previewItems as $index => $item)
                        <tr class="hover:bg-slate-950/40 transition">
                            <td class="py-4 px-6 text-center text-slate-500 font-medium">{{ $index + 1 }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 rounded font-mono text-xs font-semibold">
                                    {{ $item['asset_id'] }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <input type="text" name="gov_codes[]" required 
                                    value="{{ old('gov_codes.'.$index) }}"
                                    class="w-full max-w-xs px-3 py-2 bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-lg text-white text-xs outline-none transition"
                                    placeholder="Nomor Inventaris Kementerian">
                            </td>
                            <td class="py-4 px-6">
                                <input type="text" name="serials[]" 
                                    value="{{ old('serials.'.$index) }}"
                                    class="w-full max-w-xs px-3 py-2 bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-lg text-white text-xs outline-none transition"
                                    placeholder="Serial Number Pabrik">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Hidden Form for Save Confirmation --}}
        <form action="{{ route('admin.assets.bulk.store') }}" method="POST" id="bulkConfirmForm">
            @csrf
            
            {{-- Pass all validated fields back to controller --}}
            <input type="hidden" name="name" value="{{ $validated['name'] }}">
            <input type="hidden" name="category" value="{{ $validated['category'] }}">
            <input type="hidden" name="brand" value="{{ $validated['brand'] ?? '' }}">
            <input type="hidden" name="model" value="{{ $validated['model'] ?? '' }}">
            <input type="hidden" name="count" value="{{ $validated['count'] }}">
            <input type="hidden" name="building" value="{{ $validated['building'] }}">
            <input type="hidden" name="floor" value="{{ $validated['floor'] }}">
            <input type="hidden" name="room" value="{{ $validated['room'] }}">
            <input type="hidden" name="year" value="{{ $validated['year'] }}">
            <input type="hidden" name="status" value="{{ $validated['status'] }}">
            <input type="hidden" name="specification" value="{{ $validated['specification'] ?? '' }}">
            <input type="hidden" name="current_user" value="{{ $validated['current_user'] ?? '' }}">

            <!-- JavaScript will append input elements into this form container before submitting -->
            <div id="dynamic-inputs-container"></div>

            <div class="p-6 bg-slate-950/45 border-t border-slate-800 flex items-center justify-between gap-4">
                <p class="text-xs text-slate-500 hidden sm:block">Dengan menekan tombol Simpan, aset-aset di atas akan langsung ditambahkan ke database.</p>

                <div class="flex items-center gap-3 ml-auto w-full sm:w-auto">
                    <a href="javascript:history.back()" class="w-1/2 sm:w-auto text-center px-5 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                        Koreksi Data
                    </a>
                    <button type="submit" onclick="submitBulkForm(event)" class="w-1/2 sm:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition cursor-pointer flex items-center justify-center gap-2">
                        Simpan Aset
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function submitBulkForm(event) {
        event.preventDefault();
        const container = document.getElementById('dynamic-inputs-container');
        container.innerHTML = ''; // clear
        
        // Find all inputs for gov_codes and serials
        const govInputs = document.querySelectorAll('input[name="gov_codes[]"]');
        const serialInputs = document.querySelectorAll('input[name="serials[]"]');
        
        let isValid = true;
        
        govInputs.forEach((input, index) => {
            if (!input.value.trim()) {
                input.classList.add('border-rose-500');
                isValid = false;
            } else {
                input.classList.remove('border-rose-500');
                // create hidden input
                const hiddenGov = document.createElement('input');
                hiddenGov.type = 'hidden';
                hiddenGov.name = `gov_codes[${index}]`;
                hiddenGov.value = input.value.trim();
                container.appendChild(hiddenGov);
            }
        });

        serialInputs.forEach((input, index) => {
            // create hidden input (can be empty/null)
            const hiddenSerial = document.createElement('input');
            hiddenSerial.type = 'hidden';
            hiddenSerial.name = `serials[${index}]`;
            hiddenSerial.value = input.value.trim();
            container.appendChild(hiddenSerial);
        });
        
        if (isValid) {
            document.getElementById('bulkConfirmForm').submit();
        } else {
            alert('Mohon lengkapi seluruh Nomor Inventaris Kementerian.');
        }
    }
</script>
@endsection
