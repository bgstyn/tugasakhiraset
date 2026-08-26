@extends('layouts.layout')

@section('title', 'Manajemen Akun Teknisi - IT Asset Management')
@section('page_title', 'Manajemen Akun Teknisi')

@section('content')
<!-- Page Header -->
<div class="mb-6 md:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <p class="text-slate-400 text-sm">Kelola akun teknisi yang memiliki akses ke sistem manajemen aset.</p>
    </div>
    <a href="{{ route('teknisi.create') }}" id="btn-add-teknisi"
        class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium shadow-lg shadow-indigo-600/10 hover:shadow-indigo-600/20 transition-all duration-200 cursor-pointer shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
        </svg>
        Tambah Teknisi
    </a>
</div>

<!-- Teknisi Table Card -->
<div class="bg-slate-900 border border-slate-800/80 rounded-2xl shadow-lg overflow-hidden">
    @if($teknisiList->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-slate-500">
            <div class="p-4 bg-slate-800/50 rounded-2xl mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-400">Belum ada akun teknisi</p>
            <p class="text-xs text-slate-500 mt-1">Tambahkan teknisi baru untuk memulai.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm" id="teknisi-table">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/40">
                        <th class="text-left px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">#</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Nama</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Username</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Jabatan</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Lokasi</th>
                        <th class="text-left px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Terdaftar</th>
                        <th class="text-center px-6 py-4 text-xs font-semibold uppercase tracking-wider text-slate-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @foreach($teknisiList as $index => $teknisi)
                        <tr class="hover:bg-slate-800/30 transition-colors duration-150" id="teknisi-row-{{ $teknisi->id }}">
                            <td class="px-6 py-4 text-slate-500 font-medium">{{ $index + 1 }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white text-xs shadow-md shrink-0">
                                        {{ strtoupper(substr($teknisi->name, 0, 2)) }}
                                    </div>
                                    <span class="font-medium text-slate-200">{{ $teknisi->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <code class="px-2 py-1 bg-slate-800 border border-slate-700 rounded-lg text-xs text-indigo-300 font-mono">{{ $teknisi->username }}</code>
                            </td>
                            <td class="px-6 py-4 text-slate-300">{{ $teknisi->position ?? '-' }}</td>
                            <td class="px-6 py-4">
                                @if($teknisi->location)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-indigo-500/10 border border-indigo-500/20 text-indigo-300">
                                        {{ $teknisi->location }}
                                    </span>
                                @else
                                    <span class="text-slate-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-400 text-xs">{{ $teknisi->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Edit -->
                                    <a href="{{ route('teknisi.edit', $teknisi) }}" title="Edit"
                                        class="p-2 rounded-lg bg-slate-800 border border-slate-700 hover:border-blue-700 hover:bg-blue-950/30 text-slate-400 hover:text-blue-400 transition-all duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>

                                    <!-- Reset Password Modal Trigger -->
                                    <button type="button" title="Reset Password"
                                        onclick="openResetModal({{ $teknisi->id }}, '{{ addslashes($teknisi->name) }}')"
                                        class="p-2 rounded-lg bg-slate-800 border border-slate-700 hover:border-amber-700 hover:bg-amber-950/30 text-slate-400 hover:text-amber-400 transition-all duration-200 cursor-pointer">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                    </button>

                                    <!-- Delete -->
                                    <form action="{{ route('teknisi.destroy', $teknisi) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun teknisi {{ addslashes($teknisi->name) }}? Tindakan ini tidak dapat dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus"
                                            class="p-2 rounded-lg bg-slate-800 border border-slate-700 hover:border-rose-700 hover:bg-rose-950/30 text-slate-400 hover:text-rose-400 transition-all duration-200 cursor-pointer">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary Footer -->
        <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40 flex items-center justify-between">
            <span class="text-xs text-slate-400">Total: <strong class="text-slate-200">{{ $teknisiList->count() }}</strong> akun teknisi</span>
        </div>
    @endif
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-md p-6 relative" onclick="event.stopPropagation()">
        <button type="button" onclick="closeResetModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white transition cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="flex items-center gap-3 mb-5">
            <div class="p-2.5 bg-amber-500/10 border border-amber-500/20 text-amber-400 rounded-xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-white">Reset Password</h3>
                <p class="text-xs text-slate-400">Teknisi: <span id="resetModalName" class="text-amber-300 font-medium"></span></p>
            </div>
        </div>

        <form id="resetPasswordForm" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="reset_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Password Baru</label>
                <input type="password" id="reset_password" name="password" required minlength="6"
                    class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-white placeholder-slate-500 outline-none transition"
                    placeholder="Minimal 6 karakter...">
            </div>

            <div>
                <label for="reset_password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Konfirmasi Password</label>
                <input type="password" id="reset_password_confirmation" name="password_confirmation" required minlength="6"
                    class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-white placeholder-slate-500 outline-none transition"
                    placeholder="Ulangi password baru...">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeResetModal()"
                    class="flex-1 py-2.5 px-4 bg-slate-800 border border-slate-700 hover:bg-slate-700 text-slate-300 font-medium rounded-xl transition cursor-pointer text-sm">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 px-4 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-xl shadow-lg shadow-amber-600/10 transition cursor-pointer text-sm">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openResetModal(id, name) {
        document.getElementById('resetModalName').textContent = name;
        document.getElementById('resetPasswordForm').action = '/admin/teknisi/' + id + '/reset-password';
        document.getElementById('reset_password').value = '';
        document.getElementById('reset_password_confirmation').value = '';
        const modal = document.getElementById('resetPasswordModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('reset_password').focus();
    }

    function closeResetModal() {
        const modal = document.getElementById('resetPasswordModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal on outside click
    document.getElementById('resetPasswordModal').addEventListener('click', function(e) {
        if (e.target === this) closeResetModal();
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeResetModal();
    });
</script>
@endsection
