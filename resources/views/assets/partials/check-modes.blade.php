<nav class="check-mode-tabs" aria-label="Metode cek aset">
    <a href="{{ route('assets.scan', [], false) }}" @if(request()->query('mode') !== 'rfid') aria-current="page" @endif>QR / Kamera</a>
    <a href="{{ route('assets.scan', ['mode' => 'rfid'], false) }}" @if(request()->query('mode') === 'rfid') aria-current="page" @endif>Tag RFID</a>
</nav>
