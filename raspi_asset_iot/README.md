# Raspberry Pi 5 IoT — Asset Scanner Laravel

Program Python **headless** untuk **Raspberry Pi 5 / Raspberry Pi OS Trixie 64-bit**. Jalankan `app.py` untuk scanner GPIO. Prosedur migrasi backend dan penggantian label QR ada di [panduan Laravel dan Raspberry Pi](../docs/AUDIT-IOT.md).

## Fitur

- **Mode CEK** (switch posisi kiri): QR lewat USB webcam atau RFID UID lewat RC522, validasi ke `POST /api/rfid-scan`. LCD menyatakan **ASET TERDAFTAR** / **BELUM TERDAFTAR** hanya berdasarkan respons API yang benar.
- **Mode DAFTAR** (switch posisi kanan): scan QR aset dan RFID UID **dalam urutan bebas**; setelah keduanya lengkap kirim otomatis ke `POST /api/rfid/register`. Ini memasangkan UID pada **aset yang telah ada**, bukan membuat master data aset baru.
- LCD 16×2 PCF8574 I2C (alamat default `0x27`) menampilkan mode, proses, hasil, dan antrean. LED hijau menandakan sukses, merah menandakan kegagalan scan atau validasi, dan biru menandakan kegagalan komunikasi server.
- SQLite menyimpan scan dengan UUID sebelum dikirim. Saat jaringan kembali, pengirim mengirim ulang UUID yang sama dengan jeda 5 detik hingga 5 menit. Laravel versi baru menyimpan hasil berdasarkan UUID agar respons yang hilang tidak mengulang perubahan aset atau riwayat.
- Scanner tidak memerlukan GUI dan dapat berjalan saat boot via `systemd`.
- Saat jaringan putus, status aset tidak mungkin divalidasi oleh Laravel; hasil scan cek **ditunda**, bukan dianggap terdaftar.

## Penting: periksa instalasi hardware yang sudah ada

Verifikasi pin fisik pada perangkat. Cocokkan GPIO dengan skema atau hasil pengukuran, lalu sesuaikan nilai dalam `.env` jika berbeda. Semua **GPIO 3,3 V**, jangan hubungkan ke 5 V. Matikan daya saat memindah kabel.

### Mapping GPIO default (nomor BCM)

| Perangkat | Pin komponen | BCM | Pin fisik header |
|---|---|---:|---:|
| RC522 (3.3 V) | VCC | 3V3 | 1 |
| RC522 | GND | GND | 6 |
| RC522 | SDA/SS (SPI CE0) | GPIO8 | 24 |
| RC522 | SCK | GPIO11 | 23 |
| RC522 | MOSI | GPIO10 | 19 |
| RC522 | MISO | GPIO9 | 21 |
| RC522 | RST | GPIO25 | 22 |
| LCD I2C | SDA | GPIO2 | 3 |
| LCD I2C | SCL | GPIO3 | 5 |
| Switch SPDT 2 posisi | Kaki COM/tengah (cek dengan multimeter) | GND | 9 |
| Switch | Kaki luar CEK | GPIO17 | 11 |
| Switch | Kaki luar DAFTAR | GPIO27 | 13 |
| LED RGB (resistor tiap warna) | Red | GPIO5 | 29 |
| LED RGB | Green | GPIO6 | 31 |
| LED RGB | Blue | GPIO13 | 33 |
| Buzzer via driver transistor | Input driver | GPIO18 | 12 |
| Webcam | USB | — | Port USB |

**LCD 4 pin**: Umumnya I2C, VCC/GND/SDA/SCL. Jika modul LCD/bekapck memakai 5 V, gunakan **level shifter I2C bidirectional** untuk SDA/SCL (misalnya BSS138). LV=3,3 V dan HV=5 V. Jangan menarik GPIO2/GPIO3 ke 5 V melalui resistor pull-up backpack. GND disatukan. Alamat umum `0x27` atau `0x3F`; lihat hasil `i2cdetect -y 1`. Pin PCF8574 mengasumsikan backpack standar P0=RS, P1=RW, P2=E, P3=backlight, P4-P7=data; backpack nonstandar perlu adaptasi.

**LED RGB 4 kaki**. Cocokkan pin warna dengan datasheet atau multimeter mode dioda. Setiap kanal R/G/B memakai resistor seri sendiri, misalnya 330 Ω jika cocok dengan modul. Untuk common anode langsung, sambungkan common ke 3,3 V dan set `LED_TYPE=common_anode`. GPIO LOW menyalakan kanal dan HIGH mematikannya. Untuk common cathode langsung, common ke GND dan pilih `LED_TYPE=common_cathode`. Jika rangkaian memakai driver pembalik, tentukan polaritas di sisi GPIO sesuai rangkaian. Gunakan pull-up eksternal ke 3,3 V pada setiap GPIO kanal aktif-LOW (misalnya 10 kΩ untuk rangkaian langsung) jika perlu menjamin LED tetap mati ketika pin dilepas, selama boot, atau saat proses dihentikan paksa. Biarkan `LED_ENABLED=false` hingga wiring terverifikasi. [Raspberry Pi GPIO](https://www.raspberrypi.com/documentation/computers/raspberry-pi.html) · [Polaritas GPIO Zero](https://gpiozero.readthedocs.io/en/stable/api_output.html#gpiozero.DigitalOutputDevice)

**Buzzer 2 kabel**: foto saja tidak memastikan tegangan ataupun aktif/pasif. Gunakan **driver NPN/transistor atau MOSFET logika** (misalnya 2N2222 dengan resistor basis ~1 kΩ dan pull-down basis ~10 kΩ), sumber tegangan sesuai rating buzzer, serta GND gabung dengan Pi. Jika buzzer induktif tambahkan dioda flyback. Kode memberi ON/OFF sehingga ditujukan untuk **buzzer aktif**; buzzer pasif membutuhkan penghasil nada (PWM), jangan aktifkan sebelum mengetahui tipenya. `BUZZER_ENABLED=false` sampai aman.

### Switch 3 kaki posisi tetap (ON-ON)

Kaki tengah sering COM, **tetapi uji continuity dahulu**. Hubungkan COM ke GND, dua kaki luar ke GPIO17/GPIO27 memakai internal pull-up. Saat switch ke kiri, salah satu input dibumikan (mode cek); saat ke kanan yang lain (mode daftar). Jika posisinya terbalik, tukar nilai `SWITCH_CHECK_GPIO` dan `SWITCH_REGISTER_GPIO` di `.env` — tak perlu melepas kabel.

## Instalasi (Raspberry Pi OS Trixie 64-bit)

1. Siapkan migrasi Laravel terlebih dahulu. Salin folder `raspi_asset_iot` ke `/home/<username>/raspi_asset_iot`. Untuk pembaruan, hentikan service dan simpan salinan konfigurasi serta SQLite sesuai panduan audit. Pertahankan nilai `DB_PATH` yang menunjuk ke antrean lama.
2. Buka konfigurasi:

   ```bash
   sudo raspi-config
   # Interface Options -> SPI -> Yes; I2C -> Yes, reboot
   ```

3. Masuk ke folder aplikasi:

   ```bash
   cd ~/raspi_asset_iot
   bash install.sh
   sudo usermod -aG gpio,spi,i2c,video "$USER"
   # Keluar/login kembali supaya grup baru aktif
   nano .env
   ```

4. **Isi `.env`:**

   ```dotenv
   API_BASE_URL=https://tipnpasset.duckdns.org/api
   API_TOKEN=TOKEN_YANG_SAMA_DENGAN_RFID_API_TOKEN_DI_LARAVEL
   LCD_ENABLED=false
   LED_ENABLED=false
   BUZZER_ENABLED=false
   LED_TYPE=common_anode
   RFID_UID_FORMAT=hex
   ```

   `API_BASE_URL` diambil dari versi `raspi_scanner.py` di repo Laravel; **pastikan masih benar** untuk deployment sekarang. Di Laravel atur `RFID_API_TOKEN` yang kuat (jangan pakai nilai default yang diketahui umum), samakan dengan Pi, lalu `php artisan config:clear` jika dibutuhkan setelah perubahan `.env`. Jangan unggah `.env` atau token ke internet.

5. Periksa device tanpa mengaktifkan GPIO:

   ```bash
   python3 app.py --diagnose
   ls -l /dev/spidev0.0 /dev/i2c-1 /dev/video0
   i2cdetect -y 1
   ```

6. Tes satu per satu, hanya jika rangkaian terverifikasi:

   ```bash
   python3 hardware_test.py switch
   python3 hardware_test.py rfid
   python3 hardware_test.py camera
   # Sebelum tes LCD: pasang level shifter jika LCD 5 V dan LCD_ENABLED=true
   python3 hardware_test.py lcd
   # Pastikan LED_TYPE benar & LED_ENABLED=true, pasang resistor dulu
   python3 hardware_test.py led
   # Pastikan driver buzzer + tegangan cocok & BUZZER_ENABLED=true
   python3 hardware_test.py buzzer
   ```

7. Jalankan tanpa service dulu:

   ```bash
   python3 app.py
   ```

   Sistem tetap membaca scanner dan menyimpan antrean meski Laravel offline. Pastikan semua scan diproses dengan benar sebelum mengaktifkan boot otomatis.

8. Jika sudah stabil, jalankan sebagai service (asumsi folder berada tepat di `~/raspi_asset_iot`):

   ```bash
   sudo install -m 0644 systemd/asset-iot@.service /etc/systemd/system/asset-iot@.service
   sudo systemctl daemon-reload
   sudo systemctl enable --now "asset-iot@$(whoami).service"
   sudo systemctl status "asset-iot@$(whoami).service"
   journalctl -u "asset-iot@$(whoami).service" -f
   ```

   Gunakan `systemctl is-enabled "asset-iot@$(whoami).service"` untuk memeriksa boot otomatis. Reboot, lalu gunakan `systemctl is-active "asset-iot@$(whoami).service"`. Ulangi dengan koneksi internet terputus dan pastikan scan masih bisa masuk SQLite. Unit mengikuti `network.target`, sehingga dapat mulai tanpa koneksi internet. [Dokumentasi systemd](https://www.freedesktop.org/software/systemd/man/latest/systemd.special.html)

   Hentikan service sebelum mengetes hardware tersendiri untuk mencegah konflik GPIO. Jalankan `sudo systemctl stop "asset-iot@$(whoami).service"`, lalu pastikan LED dan buzzer mati.

## Perilaku LED/buzzer dan LCD

| Status | LED | Buzzer | LCD contoh |
|---|---|---|---|
| QR/RFID diterima dalam mode daftar, menunggu pasangan | Mati | 0 | `QR DITERIMA` / `SCAN RFID` |
| Sudah cocok dan Laravel menerima | Hijau | 1 beep | `DAFTAR BERHASIL` / ID aset |
| Laravel menemukan aset dalam mode cek | Hijau | 1 beep | `ASET TERDAFTAR` / ID aset |
| QR tidak terbaca/invalid; asset tidak ditemukan | Merah | 2 beep | `SCAN QR GAGAL` / `KODE TIDAK VALID` atau `BELUM TERDAFTAR` |
| Komunikasi server gagal pada scan yang baru dilakukan | Biru | 2 beep | `GAGAL POST DATA` / `SIMPAN ANTREAN` |
| Antrean lama telah berhasil disinkronkan | Hijau singkat | 0 | `ANTREAN TERKIRIM` |

QR dari Laravel dapat berbentuk `https://alamat/a/ASSET-001`; nilai yang dikirim sebagai `asset_id` adalah `ASSET-001`, bukan URL utuh. UID RFID disimpan hex uppercase (`0A1B2C3D`) secara default. Jika database Laravel lama menyimpan format lain, cocokkan lewat `RFID_UID_FORMAT=decimal` (tidak boleh mengubah format sembarang di tengah penggunaan).

**Pendaftaran otomatis tidak punya tombol batal terpisah**: pasangan QR/RFID yang belum selesai dibuang setelah 90 detik atau saat switch berganti mode. Saat internet mati, satu pasangan lengkap disimpan di SQLite untuk dikirim setelah online.

## Diagnostic dan antrean

```bash
python3 app.py --diagnose
python3 app.py --queue-stats
# contoh: {'pending': 2, 'success': 14, 'rejected': 1}
python3 -m unittest discover -s tests -v
```

Antrean disimpan di `data/queue.sqlite3` dengan WAL dan `synchronous=FULL`. Jalankan `python3 app.py --queue-stats` untuk melihat jumlah per status. `pending` akan dikirim otomatis, `success` sudah dikonfirmasi dan `rejected` mencatat penolakan akhir. Untuk kasus terakhir, cek log dan data aset, perbaiki penyebabnya, lalu scan ulang untuk membuat UUID baru. Jika disk penuh atau antrean mencapai `PENDING_LIMIT`, LCD melaporkan kegagalan penyimpanan dan scan tersebut harus diulang setelah ruang tersedia. Gangguan sementara, token yang ditolak, dan respons proxy yang tidak valid tetap berada di antrean. Pengulangan di latar belakang tidak menghasilkan beep tambahan.

## Keterbatasan dan langkah lanjutan

- **Belum diuji secara fisik** dengan modul pengguna; penugasan GPIO dan jenis LED/buzzer/LCD harus diverifikasi terlebih dahulu.
- `raspi_scanner.py` merupakan program live MJPEG/AWS tersendiri. Jalankan program itu di perangkat terpisah jika scanner GPIO sudah memakai webcam pada Pi yang sama.
- Aset yang discan saat offline bisa saja berubah statusnya sebelum akhirnya diperiksa ketika online; LCD tidak menyatakan registered/unregistered saat offline.
- Sebelum memasang LCD maupun buzzer, cocokkan tegangan pada label modul dengan sumber listriknya. Hindari memberi 5 V ke GPIO Raspberry Pi 5.
