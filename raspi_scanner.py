#!/usr/bin/env python3
"""
===================================================================
Raspberry Pi MJPEG Camera Streamer & Asset Scanner Agent (AWS Cloud)
===================================================================
Script ini berjalan di Raspberry Pi (192.168.100.107) dengan fitur:
1. PUSH Live Camera Frame ke AWS Cloud Server (https://tipnpasset.duckdns.org/api/raspi/frame)
2. Flask Web Server Streaming Kamera Lokal di Port 5000 (http://<ip-raspi>:5000)
3. Scanner QR Code / Barcode bawaan yang otomatis mengirimkan hasil scan ke AWS:
   https://tipnpasset.duckdns.org/api/rfid-scan
===================================================================
"""

import os
import sys
import time
import json
import socket
import threading
import requests

# Coba import OpenCV & Numpy
try:
    import cv2
    import numpy as np
    HAS_OPENCV = True
except ImportError:
    HAS_OPENCV = False
    cv2 = None
    np = None

# Coba import PyZbar
try:
    from pyzbar import pyzbar
    USE_PYZBAR = True
except ImportError:
    USE_PYZBAR = False

# Coba import Flask
try:
    from flask import Flask, Response, render_template_string, jsonify
    HAS_FLASK = True
except ImportError:
    HAS_FLASK = False

# Konfigurasi Server AWS & Token
AWS_SERVER_URL = "https://tipnpasset.duckdns.org/api/rfid-scan"
AWS_FRAME_PUSH_URL = "https://tipnpasset.duckdns.org/api/raspi/frame"
API_TOKEN = "secret-rfid-token"
STREAM_PORT = 5000

# Cooldown per scan (detik) agar tidak spamming server
SCAN_COOLDOWN = 3.0
last_scanned_code = ""
last_scanned_time = 0
latest_status = "Standby - Memulai Kamera..."
last_frame_push_time = 0

# Global Frame Object & Lock
output_frame = None
frame_lock = threading.Lock()

def get_local_ip():
    """Mendapatkan IP Address lokal Raspberry Pi secara otomatis"""
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return "127.0.0.1"

LOCAL_IP = get_local_ip()

def push_frame_to_aws(frame):
    """Mengirimkan (PUSH) frame live camera ke AWS Cloud Server agar dapat dilihat langsung di Web HTTPS"""
    global last_frame_push_time
    now = time.time()
    if (now - last_frame_push_time) < 0.15: # ~6 FPS untuk hemat bandwidth
        return
    last_frame_push_time = now

    try:
        flag, encoded_image = cv2.imencode(".jpg", frame, [int(cv2.IMWRITE_JPEG_QUALITY), 60])
        if not flag:
            return
        files = {'frame': ('frame.jpg', bytearray(encoded_image), 'image/jpeg')}
        headers = {'X-API-Token': API_TOKEN}
        requests.post(AWS_FRAME_PUSH_URL, files=files, headers=headers, timeout=2)
    except Exception:
        pass

def send_to_aws_api(payload):
    """Mengirimkan data hasil scan ke AWS Server"""
    global last_scanned_code, last_scanned_time, latest_status
    current_time = time.time()
    
    scanned_val = payload.get('asset_id') or payload.get('rfid_uid')
    if scanned_val == last_scanned_code and (current_time - last_scanned_time) < SCAN_COOLDOWN:
        return

    last_scanned_code = scanned_val
    last_scanned_time = current_time

    headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-API-Token': API_TOKEN
    }

    print(f"\n[➔] Mengirim data ke AWS Cloud: {payload}")
    latest_status = f"Mengirim data: {scanned_val}..."

    try:
        response = requests.post(AWS_SERVER_URL, json=payload, headers=headers, timeout=10, verify=True)
        if response.status_code == 200:
            res_data = response.json()
            asset_info = res_data.get('data', {})
            msg = f" ✅ DITEMUKAN: {asset_info.get('name')} ({asset_info.get('asset_id')})"
            print("==================================================")
            print(msg)
            print(f" 🔹 Status       : {asset_info.get('status')}")
            print(f" 🔹 Lokasi       : Ruang {asset_info.get('room', '-')}, Lantai {asset_info.get('floor', '-')}")
            print(f" 🔹 Link Detail  : {res_data.get('redirect_url')}")
            print("==================================================\n")
            latest_status = msg
        elif response.status_code == 404:
            msg = f" ⚠️ BELUM TERDAFTAR: {scanned_val}"
            print("==================================================")
            print(msg)
            print("==================================================\n")
            latest_status = msg
        else:
            msg = f" [!] Response Server ({response.status_code}): {response.text}"
            print(msg)
            latest_status = msg
    except Exception as e:
        msg = f" [❌] Gagal menghubungi Server AWS: {e}"
        print(msg)
        latest_status = msg

def extract_asset_code(raw_data):
    """Mengekstrak Kode Aset jika yang discan adalah URL penuh atau shortlink /a/"""
    raw_str = raw_data.strip()
    if '/a/' in raw_str:
        parts = raw_str.split('/a/')
        return parts[-1].split('?')[0].split('#')[0]
    if '/assets/code/' in raw_str:
        parts = raw_str.split('/assets/code/')
        return parts[-1].split('?')[0].split('#')[0]
    if '/assets/' in raw_str:
        parts = raw_str.split('/assets/')
        return parts[-1].split('?')[0].split('#')[0]
    return raw_str

def get_camera_capture():
    """Mencari index & backend kamera fisik yang valid"""
    if not HAS_OPENCV:
        return None

    for idx in [0, 1, 2, -1]:
        try:
            cap = cv2.VideoCapture(idx, cv2.CAP_V4L2) if hasattr(cv2, 'CAP_V4L2') else cv2.VideoCapture(idx)
            if not cap.isOpened():
                cap = cv2.VideoCapture(idx)
            
            if cap.isOpened():
                ret, test_frame = cap.read()
                if ret and test_frame is not None:
                    print(f"[INFO] Kamera fisik berhasil dibuka pada index device: {idx}")
                    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
                    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
                    return cap
                cap.release()
        except Exception:
            pass

    return None

def camera_capture_loop():
    """Thread terpisah untuk menangkap frame kamera & memproses QR code secara konstan"""
    global output_frame, frame_lock, latest_status

    if not HAS_OPENCV:
        print("[❌] Module OpenCV ('cv2') belum terinstall di Raspberry Pi.")
        print("     Jalankan: sudo apt update && sudo apt install -y python3-opencv python3-pyzbar")
        latest_status = "Error: OpenCV belum terinstall"
        return

    cap = get_camera_capture()
    if cap is None:
        latest_status = "Kamera fisik (/dev/video0) tidak terhubung"
        print("[⚠️] Kamera fisik tidak terdeteksi di perangkat ini.")
        print("     Periksa kabel kamera USB / CSI Raspberry Pi Anda.")

    if not USE_PYZBAR and HAS_OPENCV:
        qr_detector = cv2.QRCodeDetector()

    print("[INFO] Thread Scanner QR Code & Frame Pushing aktif.")

    while True:
        if cap is None or not cap.isOpened():
            time.sleep(5)
            cap = get_camera_capture()
            continue

        ret, frame = cap.read()
        if not ret or frame is None:
            time.sleep(0.1)
            continue

        scanned_text = None

        if USE_PYZBAR:
            barcodes = pyzbar.decode(frame)
            for barcode in barcodes:
                (x, y, w, h) = barcode.rect
                cv2.rectangle(frame, (x, y), (x + w, y + h), (0, 255, 0), 2)
                scanned_text = barcode.data.decode("utf-8")
                cv2.putText(frame, scanned_text, (x, y - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)
        else:
            data, bbox, _ = qr_detector.detectAndDecode(frame)
            if data:
                scanned_text = data
                if bbox is not None:
                    for i in range(len(bbox)):
                        pt1 = tuple(map(int, bbox[i][0]))
                        pt2 = tuple(map(int, bbox[(i+1)%len(bbox)][0]))
                        cv2.line(frame, pt1, pt2, (0, 255, 0), 2)

        if scanned_text:
            clean_code = extract_asset_code(scanned_text)
            if clean_code.startswith("TI") or "00" in clean_code:
                send_thread = threading.Thread(target=send_to_aws_api, args=({'asset_id': clean_code},))
                send_thread.start()
            else:
                send_thread = threading.Thread(target=send_to_aws_api, args=({'rfid_uid': clean_code},))
                send_thread.start()

        # Push frame ke AWS Cloud secara asynchronous
        push_thread = threading.Thread(target=push_frame_to_aws, args=(frame.copy(),), daemon=True)
        push_thread.start()

        with frame_lock:
            output_frame = frame.copy()

        time.sleep(0.03)

# Inisialisasi Flask Application
if HAS_FLASK:
    app = Flask(__name__)

    HTML_TEMPLATE = """
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Raspberry Pi Live Camera Stream - Asset Management</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0f172a; color: #f8fafc; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; }
            .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 20px; max-width: 680px; width: 100%; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
            h2 { color: #818cf8; margin-bottom: 5px; }
            p { color: #94a3b8; font-size: 14px; margin-top: 0; }
            .stream-box { border-radius: 12px; overflow: hidden; background: #000; margin: 15px 0; border: 2px solid #4f46e5; min-height: 300px; display: flex; align-items: center; justify-content: center; }
            img { width: 100%; height: auto; display: block; }
            .status-badge { background: #312e81; color: #c7d2fe; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: bold; display: inline-block; }
            .footer { margin-top: 15px; font-size: 12px; color: #64748b; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>📷 Raspberry Pi Live Camera Stream</h2>
            <p>IP RasPi: """ + LOCAL_IP + """ | Target AWS: tipnpasset.duckdns.org</p>
            <div class="stream-box">
                <img src="/video_feed" alt="Live Camera Feed">
            </div>
            <div class="status-badge" id="status">Status: Standby scan</div>
            <div class="footer">IT Asset Management System &copy; 2026</div>
        </div>
        <script>
            setInterval(() => {
                fetch('/status')
                    .then(r => r.json())
                    .then(d => { document.getElementById('status').innerText = 'Status: ' + d.status; })
                    .catch(e => console.error(e));
            }, 2000);
        </script>
    </body>
    </html>
    """

    @app.after_request
    def add_cors_headers(response):
        response.headers['Access-Control-Allow-Origin'] = '*'
        response.headers['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
        response.headers['Access-Control-Allow-Methods'] = 'GET,PUT,POST,DELETE,OPTIONS'
        return response

    @app.route('/')
    def index():
        return render_template_string(HTML_TEMPLATE)

    @app.route('/status')
    def status():
        return jsonify({
            'status': latest_status, 
            'last_code': last_scanned_code,
            'ip': LOCAL_IP,
            'port': STREAM_PORT
        })

    def generate_mjpeg_frames():
        global output_frame, frame_lock
        while True:
            encoded_bytes = None
            with frame_lock:
                if output_frame is not None and HAS_OPENCV:
                    flag, encoded_image = cv2.imencode(".jpg", output_frame, [int(cv2.IMWRITE_JPEG_QUALITY), 75])
                    if flag:
                        encoded_bytes = bytearray(encoded_image)
            
            if encoded_bytes is None:
                if HAS_OPENCV and np is not None:
                    img = np.zeros((480, 640, 3), dtype=np.uint8)
                    cv2.putText(img, "KAMERA RASPI STANDBY / OFFLINE", (80, 220), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 215, 255), 2)
                    cv2.putText(img, f"IP: http://{LOCAL_IP}:{STREAM_PORT}", (160, 270), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (200, 200, 200), 1)
                    _, encoded_img = cv2.imencode(".jpg", img)
                    encoded_bytes = bytearray(encoded_img)
                else:
                    time.sleep(0.1)
                    continue

            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + encoded_bytes + b'\r\n')
            time.sleep(0.04)

    @app.route('/video_feed')
    def video_feed():
        return Response(generate_mjpeg_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

if __name__ == "__main__":
    print("==================================================")
    print(" 🚀 Memulai Raspberry Pi Scanner & Camera Stream")
    print(f" 📍 IP Raspberry Pi  : {LOCAL_IP}")
    print(f" 🌐 Target Cloud AWS : {AWS_SERVER_URL}")
    print(f" 📹 AWS Stream Pushing: {AWS_FRAME_PUSH_URL}")
    print(f" 📹 Stream Live Port : http://{LOCAL_IP}:{STREAM_PORT}/video_feed")
    print("==================================================")

    # Start thread camera
    cam_thread = threading.Thread(target=camera_capture_loop, daemon=True)
    cam_thread.start()

    # Start server Flask
    if HAS_FLASK:
        print(f"\n[SERVER] Server Flask aktif di http://0.0.0.0:{STREAM_PORT}\n")
        app.run(host='0.0.0.0', port=STREAM_PORT, debug=False, threaded=True)
    else:
        print("[!] Flask tidak terinstall. Silakan install dengan: sudo apt install python3-flask")
        while True:
            time.sleep(1)
