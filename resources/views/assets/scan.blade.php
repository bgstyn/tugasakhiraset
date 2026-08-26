@extends('layouts.layout')

@section('title', 'Scan QR Aset - IT Asset Management')
@section('page_title', 'Scan QR Code Kamera')

@section('styles')
<style>
    /* Premium high-tech scanner design */
    #reader {
        border: none !important;
        border-radius: 1.5rem;
        overflow: hidden;
        background: #090d16 !important;
        width: 100% !important;
    }
    #reader video {
        width: 100% !important;
        height: auto !important;
        border-radius: 1.5rem !important;
        object-fit: cover !important;
    }
    #reader img {
        max-width: 100% !important;
        height: auto !important;
        margin: 0 auto !important;
    }
    #reader__video {
        border-radius: 1.5rem;
        object-fit: cover;
        width: 100% !important;
        height: auto !important;
    }
    #reader__dashboard {
        background: #0f172a !important;
        color: #94a3b8 !important;
        border-top: 1px solid #1e293b !important;
        padding: 1rem !important;
    }
    #reader button {
        background: #4f46e5 !important;
        color: #ffffff !important;
        border: none !important;
        padding: 0.6rem 1.2rem !important;
        border-radius: 0.75rem !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
        cursor: pointer !important;
        transition: all 0.2s !important;
    }
    #reader button:hover {
        background: #4338ca !important;
        transform: translateY(-1px);
    }
    /* Scanning laser animation overlay */
    .scanner-container {
        position: relative;
    }
    .scanner-laser {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(to right, transparent, #818cf8, #a78bfa, #818cf8, transparent);
        box-shadow: 0 0 12px 3px rgba(129, 140, 248, 0.8);
        animation: scan-line 3s linear infinite;
        z-index: 10;
        pointer-events: none;
        display: none;
    }
    @keyframes scan-line {
        0% { top: 0%; }
        50% { top: 100%; }
        100% { top: 0%; }
    }
</style>
@endsection

@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('assets.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Daftar Aset
        </a>
    </div>

    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 md:p-6 shadow-lg text-center">
        <div class="mb-4">
            <div class="inline-flex p-3 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 rounded-2xl mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-white">Scanner Kamera QR Code Aset</h3>
            <p class="text-xs text-slate-400 mt-1">Pilih sumber kamera untuk memindai QR code aset secara langsung.</p>
        </div>

        {{-- Camera Selector Tabs --}}
        <div class="flex p-1 bg-slate-950 rounded-xl border border-slate-800 mb-5">
            <button type="button" id="tab-webcam" onclick="switchCameraMode('webcam')" class="flex-1 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white transition">
                📷 Kamera Browser / WebCam
            </button>
            <button type="button" id="tab-raspi" onclick="switchCameraMode('raspi')" class="flex-1 py-2 text-xs font-semibold rounded-lg text-slate-400 hover:text-white transition">
                🍓 Stream Kamera RasPi (Port 5000)
            </button>
        </div>

        <!-- Scan status message boxes -->
        <div id="status-message" class="hidden mb-4 p-4 rounded-xl text-sm font-medium transition duration-200">
            <!-- Dynamic Message text -->
        </div>

        {{-- Dynamic Asset Scan Result Card --}}
        <div id="scan-result-card" class="hidden mb-6 transition duration-300">
            <!-- Rendered by JavaScript upon successful scan -->
        </div>

        <!-- Local WebCam Scanner Container -->
        <div id="webcam-container">

            <div class="scanner-container rounded-2xl overflow-hidden border border-slate-800/80 bg-slate-950 p-1">
                <div class="scanner-laser" id="laser"></div>
                <div id="reader" class="w-full"></div>
            </div>
            <div class="mt-4 flex items-center justify-between text-xs text-slate-500 px-2">
                <span>Library: html5-qrcode</span>
                <span class="flex items-center gap-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-ping"></span>
                    Standby scan
                </span>
            </div>
        </div>

        <!-- Raspberry Pi Camera Stream Container -->
        <div id="raspi-container" class="hidden space-y-3">
            {{-- Dynamic IP Address Bar --}}
            <div class="flex items-center gap-2 bg-slate-950 p-2 rounded-xl border border-slate-800 text-xs">
                <span class="text-slate-400 font-semibold pl-2">IP RasPi:</span>
                <input type="text" id="raspi-ip-input" value="192.168.100.107" class="flex-1 bg-slate-900 border border-slate-700 text-white rounded-lg px-3 py-1.5 font-mono outline-none focus:border-indigo-500 text-xs" placeholder="192.168.100.107">
                <button type="button" onclick="updateRaspiIp()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition">
                    Simpan & Connect
                </button>
            </div>

            <div class="rounded-2xl overflow-hidden border border-indigo-500/30 bg-slate-950 p-1 relative">
                <img src="" id="raspi-stream-img" alt="Raspberry Pi Camera Stream" class="w-full h-auto rounded-xl object-cover hidden">

                <div id="raspi-stream-error" class="hidden p-6 text-center space-y-3">
                    <div class="w-12 h-12 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-2xl flex items-center justify-center mx-auto mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-rose-400 text-xs font-bold">⚠️ Video Stream Raspberry Pi Tidak Terbuka</p>
                    <p class="text-[11px] text-slate-400 max-w-md mx-auto">
                        Penyebab: Website ini berbasis <strong class="text-emerald-400">HTTPS</strong>, sedangkan stream RasPi berbasis <strong class="text-amber-400">HTTP (192.168.100.107:5000)</strong> sehingga diblokir keamanan browser (Chrome Mixed Content), ATAU script <code class="text-indigo-300 font-mono">raspi_scanner.py</code> belum diaktifkan di RasPi.
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
                        <button type="button" onclick="openRaspiDirectTab()" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition">
                            🌐 Buka Stream di Tab Baru (http://<span class="error-ip-text">192.168.100.107</span>:5000)
                        </button>
                        <button type="button" onclick="switchCameraMode('webcam')" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-xs font-semibold transition">
                            📷 Gunakan Kamera Browser / WebCam
                        </button>
                    </div>
                    <div class="text-[10px] text-slate-500 pt-1">
                        💡 <strong>Tips Chrome:</strong> Klik ikon Gembok 🔒 di URL bar ➔ Setelan Situs ➔ Konten Tidak Aman ➔ Pilih <strong>Izinkan (Allow)</strong>.
                    </div>
                </div>

            </div>
            <div class="flex items-center justify-between text-xs text-slate-400 px-2 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="font-mono text-[11px]" id="stream-url-label">http://192.168.100.107:5000/video_feed</span>
                </div>
                <button type="button" onclick="refreshRaspiStream()" class="text-indigo-400 hover:underline text-[11px] font-semibold">
                    🔄 Refresh Stream
                </button>
            </div>
        </div>
    </div>
</div>




@endsection

@section('scripts')
<!-- Load html5-qrcode scanner client library from CDN -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    let html5QrcodeScanner;
    const statusBox = document.getElementById('status-message');
    const laser = document.getElementById('laser');

    function showStatus(message, type = 'info') {
        statusBox.classList.remove('hidden', 'bg-blue-500/10', 'border-blue-500/20', 'text-blue-300', 'bg-rose-500/10', 'border-rose-500/20', 'text-rose-300', 'bg-emerald-500/10', 'border-emerald-500/20', 'text-emerald-300');
        statusBox.classList.add('block');

        if (type === 'success') {
            statusBox.classList.add('bg-emerald-500/10', 'border', 'border-emerald-500/20', 'text-emerald-300');
        } else if (type === 'error') {
            statusBox.classList.add('bg-rose-500/10', 'border', 'border-rose-500/20', 'text-rose-300');
        } else {
            statusBox.classList.add('bg-blue-500/10', 'border', 'border-blue-500/20', 'text-blue-300');
        }

        statusBox.innerText = message;
    }

    function onScanSuccess(decodedText, decodedResult) {
        // Scanned successfully
        console.log(`Scan result: ${decodedText}`, decodedResult);
        
        // Stop scanning to process
        laser.style.display = 'none';

        // Check if scanned value is a public URL detail page
        if (decodedText.includes('/assets/')) {
            showStatus('Aset ditemukan! Mengalihkan halaman...', 'success');
            setTimeout(() => {
                window.location.href = decodedText;
            }, 1000);
            return;
        }
        
        showStatus('Mencocokkan data aset...', 'info');

        // Call our API endpoint to find/process the asset
        fetch('/api/rfid-scan', {

            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                code_asset: decodedText
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStatus('Aset berhasil ditemukan!', 'success');
                displayAssetResult(data.data, data.redirect_url);
            } else {

                showStatus(data.message || 'Aset tidak ditemukan.', 'error');
                // Resume scanning after 3 seconds
                setTimeout(() => {
                    showStatus('Siap memindai...', 'info');
                    laser.style.display = 'block';
                }, 3000);
            }
        })
        .catch(err => {
            console.error('Error fetching asset:', err);
            showStatus('Gagal menghubungkan ke server.', 'error');
            setTimeout(() => {
                showStatus('Siap memindai...', 'info');
                laser.style.display = 'block';
            }, 3000);
        });
    }

    function displayAssetResult(asset, redirectUrl) {
        const resultCard = document.getElementById('scan-result-card');
        if (!resultCard) return;

        let statusBg = 'bg-blue-500/15 border-blue-500/30 text-blue-400';
        if (asset.status === 'digunakan') statusBg = 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400';
        else if (asset.status === 'maintenance') statusBg = 'bg-amber-500/15 border-amber-500/30 text-amber-400';
        else if (asset.status === 'rusak' || asset.status === 'fraud') statusBg = 'bg-rose-500/15 border-rose-500/30 text-rose-400';

        const detailUrl = redirectUrl || asset.url || ('/assets/' + (asset.id || ''));

        resultCard.innerHTML = `
            <div class="bg-slate-950 border border-emerald-500/30 rounded-3xl p-5 md:p-6 shadow-2xl text-left animate-[fadeIn_0.3s_ease-out]">
                <div class="flex items-start justify-between mb-4 border-b border-slate-800 pb-4">
                    <div>
                        <span class="px-2.5 py-0.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold uppercase rounded-full tracking-wider">
                            ✅ Hasil Scan Aset
                        </span>
                        <h4 class="text-lg font-bold text-white mt-1.5">${asset.name || 'Detail Aset'}</h4>
                    </div>
                    <span class="px-3 py-1 border ${statusBg} text-xs font-bold rounded-xl uppercase tracking-wider">
                        ${asset.status || 'Standby'}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs mb-5">
                    <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800">
                        <span class="text-slate-500 block text-[10px] uppercase font-bold">Kode Asset TI</span>
                        <strong class="text-indigo-400 text-sm font-mono block mt-0.5">${asset.asset_id || '-'}</strong>
                    </div>
                    <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800">
                        <span class="text-slate-500 block text-[10px] uppercase font-bold">RFID Tag UID</span>
                        <strong class="text-slate-200 text-sm font-mono block mt-0.5">${asset.rfid_uid || 'Belum Terdaftar'}</strong>
                    </div>
                    <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800">
                        <span class="text-slate-500 block text-[10px] uppercase font-bold">Lokasi Penempatan</span>
                        <span class="text-slate-200 block mt-0.5 font-medium">${asset.building || '-'}, Lnt. ${asset.floor || '-'}, Ruang ${asset.room || asset.location || '-'}</span>
                    </div>
                    <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800">
                        <span class="text-slate-500 block text-[10px] uppercase font-bold">User Penanggung Jawab</span>
                        <span class="text-slate-200 block mt-0.5 font-medium">${asset.current_user || '-'}</span>
                    </div>
                </div>

                <div class="flex gap-2.5">
                    <a href="${detailUrl}" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold text-center transition shadow-lg shadow-indigo-600/20">
                        📂 Buka Detail Lengkap
                    </a>
                    <button type="button" onclick="resetScanResult()" class="py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-xs font-semibold transition">
                        🔄 Scan Aset Lain
                    </button>
                </div>
            </div>
        `;

        resultCard.classList.remove('hidden');
    }

    function resetScanResult() {
        const resultCard = document.getElementById('scan-result-card');
        if (resultCard) resultCard.classList.add('hidden');
        showStatus('Siap memindai QR code aset berikutnya...', 'info');
        isRedirecting = false;
        if (laser) laser.style.display = 'block';
    }

    function onScanFailure(error) {
        // quiet failure, standard for scanning frames
    }

    function switchCameraMode(mode) {
        const webcamContainer = document.getElementById('webcam-container');
        const raspiContainer = document.getElementById('raspi-container');
        const tabWebcam = document.getElementById('tab-webcam');
        const tabRaspi = document.getElementById('tab-raspi');

        if (mode === 'raspi') {
            webcamContainer.classList.add('hidden');
            raspiContainer.classList.remove('hidden');

            tabRaspi.classList.remove('text-slate-400');
            tabRaspi.classList.add('bg-indigo-600', 'text-white');
            tabWebcam.classList.remove('bg-indigo-600', 'text-white');
            tabWebcam.classList.add('text-slate-400');

            refreshRaspiStream();
        } else {
            raspiContainer.classList.add('hidden');
            webcamContainer.classList.remove('hidden');

            tabWebcam.classList.remove('text-slate-400');
            tabWebcam.classList.add('bg-indigo-600', 'text-white');
            tabRaspi.classList.remove('bg-indigo-600', 'text-white');
            tabRaspi.classList.add('text-slate-400');
        }
    }

    function getSavedRaspiIp() {
        return localStorage.getItem('raspi_ip') || '192.168.100.107';
    }

    function updateRaspiIp() {
        const ipInput = document.getElementById('raspi-ip-input');
        if (!ipInput) return;
        const newIp = ipInput.value.trim();
        if (!newIp) return;
        localStorage.setItem('raspi_ip', newIp);
        
        const errorIpLabel = document.getElementById('error-ip-label');
        if (errorIpLabel) errorIpLabel.innerText = newIp;

        refreshRaspiStream();
        showStatus('IP Raspberry Pi diperbarui: ' + newIp, 'info');
    }

    function handleStreamError() {
        const img = document.getElementById('raspi-stream-img');
        const errBox = document.getElementById('raspi-stream-error');
        if (img) img.classList.add('hidden');
        if (errBox) errBox.classList.remove('hidden');
    }

    function openRaspiDirectTab() {
        const ip = getSavedRaspiIp();
        window.open(`http://${ip}:5000`, '_blank');
    }

    let liveStreamInterval = null;
    let raspiOnline = false;
    let offlineCount = 0;

    function startLiveFrameStream() {
        if (liveStreamInterval) clearInterval(liveStreamInterval);
        
        const img = document.getElementById('raspi-stream-img');
        const errBox = document.getElementById('raspi-stream-error');

        liveStreamInterval = setInterval(() => {
            const raspiContainer = document.getElementById('raspi-container');
            if (!raspiContainer || raspiContainer.classList.contains('hidden')) return;

            // Use fetch to check header before setting image
            fetch("/assets/scan/live-frame?t=" + new Date().getTime())
                .then(response => {
                    const status = response.headers.get('X-Raspi-Status');
                    if (status === 'online' && response.headers.get('Content-Type')?.includes('image/jpeg')) {
                        return response.blob().then(blob => {
                            const objectUrl = URL.createObjectURL(blob);
                            const oldSrc = img.src;
                            img.src = objectUrl;
                            img.classList.remove('hidden');
                            if (errBox) errBox.classList.add('hidden');
                            raspiOnline = true;
                            offlineCount = 0;
                            // Revoke old blob URL to prevent memory leak
                            if (oldSrc && oldSrc.startsWith('blob:')) {
                                URL.revokeObjectURL(oldSrc);
                            }
                        });
                    } else {
                        // Camera offline
                        offlineCount++;
                        if (offlineCount > 5) {
                            raspiOnline = false;
                            if (img) img.classList.add('hidden');
                            if (errBox) errBox.classList.remove('hidden');
                        }
                    }
                })
                .catch(err => {
                    offlineCount++;
                    if (offlineCount > 5) {
                        raspiOnline = false;
                        if (img) img.classList.add('hidden');
                        if (errBox) errBox.classList.remove('hidden');
                    }
                });
        }, 200);
    }

    function refreshRaspiStream() {
        const ip = getSavedRaspiIp();
        const img = document.getElementById('raspi-stream-img');
        const errBox = document.getElementById('raspi-stream-error');
        const urlLabel = document.getElementById('stream-url-label');
        const ipInput = document.getElementById('raspi-ip-input');

        if (ipInput) ipInput.value = ip;
        if (urlLabel) urlLabel.innerText = `https://tipnpasset.duckdns.org/assets/scan/live-frame`;
        document.querySelectorAll('.error-ip-text').forEach(el => el.innerText = ip);

        // Reset state for fresh connection
        offlineCount = 0;
        raspiOnline = false;
        if (img) img.classList.remove('hidden');
        if (errBox) errBox.classList.add('hidden');

        startLiveFrameStream();
    }

    let lastSeenTimestamp = Math.floor(Date.now() / 1000);
    let isRedirecting = false;

    function startScanPolling() {
        setInterval(() => {
            if (isRedirecting) return;

            // Poll AWS Cache for latest scan event from Raspberry Pi
            fetch('/assets/scan/latest')
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data && res.data.timestamp > lastSeenTimestamp) {
                        lastSeenTimestamp = res.data.timestamp;
                        showStatus('✅ Aset ' + res.data.data.data.name + ' (' + res.data.data.data.asset_id + ') berhasil ditemukan!', 'success');
                        displayAssetResult(res.data.data.data, res.data.data.redirect_url);
                    }
                })
                .catch(err => console.log('Polling scan log:', err));
        }, 1200);
    }

    // Initialize scanner on window load
    window.addEventListener('load', () => {
        // Start automatic polling for Raspberry Pi scan events & live frame stream
        startScanPolling();
        startLiveFrameStream();


        html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", 
            { 
                fps: 10, 
                qrbox: { width: 250, height: 250 },
                rememberLastUsedCamera: true
            },
            /* verbose= */ false
        );
        
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);

        // Show laser once scanner is active
        setTimeout(() => {
            const startBtn = document.getElementById('html5-qrcode-button-camera-start');
            const stopBtn = document.getElementById('html5-qrcode-button-camera-stop');
            
            if (!startBtn || startBtn.style.display === 'none') {
                laser.style.display = 'block';
            }

            // Bind click to start/stop to toggle laser animation
            if (startBtn) {
                startBtn.addEventListener('click', () => {
                    laser.style.display = 'block';
                });
            }
            if (stopBtn) {
                stopBtn.addEventListener('click', () => {
                    laser.style.display = 'none';
                });
            }
        }, 1000);
    });

</script>
@endsection

