@extends('layouts.layout')

@section('title', 'Daftar Lokasi - IT Asset Management')
@section('page_title', 'Manajemen Lokasi Kampus')

@section('content')
<div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg">
    <!-- Toolbar / Search and Action -->
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 md:gap-6 mb-6">
        <!-- Search Form -->
        <form action="{{ route('locations.index') }}" method="GET" class="w-full xl:flex-1 max-w-md">
            <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}"
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm text-slate-200 placeholder-slate-500 outline-none transition"
                    placeholder="Cari gedung, lantai, ruangan...">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </form>

        <!-- Actions -->
        <div class="flex items-center gap-3 flex-wrap w-full xl:w-auto">
            @if(request()->filled('search'))
                <a href="{{ route('locations.index') }}" class="w-full sm:w-auto text-center px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                    Clear Filter
                </a>
            @endif
            <a href="{{ route('locations.create') }}" class="w-full sm:w-auto justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/10 transition flex items-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Lokasi
            </a>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950/50">
        <table class="w-full min-w-[700px] text-left border-collapse">
            <thead>
                <tr class="bg-slate-900/60 border-b border-slate-800 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    <th class="py-4 px-6">Kode Ruangan</th>
                    <th class="py-4 px-6">Nama Ruangan</th>
                    <th class="py-4 px-6">Lantai</th>
                    <th class="py-4 px-6">Format Display</th>
                    <th class="py-4 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-sm text-slate-300">
                @forelse($locations as $loc)
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="py-4 px-6 font-mono text-indigo-300 font-semibold">
                            {{ $loc->kode_ruangan }}
                        </td>
                        <td class="py-4 px-6 font-semibold">
                            {{ $loc->nama_ruangan }}
                        </td>
                        <td class="py-4 px-6">
                            Lantai {{ $loc->lantai }}
                        </td>
                        <td class="py-4 px-6 text-slate-400">
                            {{ $loc->floor_room_name }}
                        </td>
                        <td class="py-4 px-6 text-right">
                            <div class="flex items-center justify-end gap-2.5">
                                <!-- Edit -->
                                <a href="{{ route('locations.edit', $loc->id) }}" class="p-1.5 text-slate-400 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition" title="Edit Lokasi">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <!-- Delete -->
                                <form action="{{ route('locations.destroy', $loc->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lokasi {{ $loc->room_name }}?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition cursor-pointer" title="Hapus Lokasi">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 px-6 text-center text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-800 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <p class="text-sm">Tidak ada lokasi kampus yang ditemukan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $locations->links() }}
    </div>
</div>
@endsection
