<div id="notifications" class="ops-notifications" aria-label="Notifikasi">
    @foreach(['success', 'error', 'info', 'warning'] as $tone)
        @if(session($tone))
            <section class="ops-toast" data-toast data-tone="{{ $tone }}" role="{{ $tone === 'error' ? 'alert' : 'status' }}" aria-atomic="true">
                <svg class="ops-toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    @if($tone === 'success')
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8 12 3 3 5-6"/><circle cx="12" cy="12" r="9"/>
                    @else
                        <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v5m0 3h.01"/>
                    @endif
                </svg>
                <div class="ops-toast-body">
                    <p>{{ session($tone) }}</p>
                    @if($tone === 'success' && session('newly_created_id'))
                        <a href="{{ route('assets.index', ['quick_filter' => 'terbaru', 'highlight_id' => session('newly_created_id')]) }}">Lihat aset baru</a>
                        <a href="{{ route('assets.create') }}">Tambah aset lagi</a>
                    @endif
                </div>
                <button type="button" data-toast-close class="ops-toast-close" aria-label="Tutup notifikasi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M6 18 18 6"/></svg>
                </button>
            </section>
        @endif
    @endforeach
    @php($validationMessage = $errors->first() ?: $errors->replacement->first())
    @if($validationMessage)
        <section class="ops-toast" data-toast data-tone="error" role="alert" aria-atomic="true">
            <div class="ops-toast-body"><p>{{ $validationMessage }}</p></div>
            <button type="button" data-toast-close class="ops-toast-close" aria-label="Tutup notifikasi">×</button>
        </section>
    @endif
</div>
