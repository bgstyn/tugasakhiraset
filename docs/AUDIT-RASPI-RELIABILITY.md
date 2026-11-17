# Ketahanan scanner Raspberry Pi dan validasi RFID

Perbaikan ini mempertahankan wiring dan endpoint yang digunakan Pi. GPIO tetap diambil dari `.env`; tidak ada perubahan nomor pin, polaritas fisik, format UID, atau isi database produksi.

## Perubahan dan perilaku

| Bagian | Perbaikan |
| --- | --- |
| Pergantian mode | Sensor mengambil nomor generasi mode sebelum membaca perangkat. Pengendali menolak scan dari generasi lama, termasuk setelah switch kembali ke posisi semula, serta scan yang sudah terlalu lama menunggu dalam RAM. Hasil API dari mode lama tidak menimpa indikator mode baru. |
| Pasangan pendaftaran | QR dan RFID lengkap dibekukan sampai transaksi SQLite berhasil. Saat antrean penuh atau SQLite gagal menulis, pasangan dipertahankan dan dicoba lagi tiap 2 detik. Pembacaan berikutnya tidak mengganti pasangan itu. Perubahan switch membuang pasangan yang belum lengkap, tetapi tidak membuang pasangan lengkap yang menunggu simpan. |
| LED | Satu thread timer memiliki deadline LED; deadline baru mengganti deadline lama di bawah lock yang sama. Operasi LCD atau SQLite tidak lagi menentukan kapan LED mati. Common anode tetap memakai active-low dan hanya satu warna aktif. |
| Buzzer | GPIO18 tetap satu beep untuk hasil sukses dan dua untuk kegagalan. Stop dan aktivasi output diserialkan; pemanggilan setelah indikator ditutup tidak dapat menghidupkannya kembali. |
| LCD | Satu thread memiliki bus I2C dari pembuatan sampai penutupan. Pengendali hanya menyerahkan teks terbaru, tanpa menunggu I2C. Kegagalan LCD dicoba ulang tiap 5 detik. |
| Pengawasan | Semua thread scanner, API, LED, buzzer, dan LCD dipantau, termasuk kemajuan loop. Thread mati atau tidak merespons melewati ambang membuat proses gagal agar `Restart=on-failure` systemd memulihkannya. Perangkat yang sedang tidak tersedia tetap dapat mencoba koneksi ulang tanpa mematikan scanner lain. |
| Shutdown | Output dimatikan lebih dahulu; worker ditunggu dengan satu batas waktu global. GPIO factory tidak ditutup saat reader/output worker masih hidup. Shutdown yang melewati batas dicatat sebagai kegagalan. Unit systemd memberi batas akhir 25 detik. |
| RC522 | `VersionReg` di alamat `0x37` diperiksa saat mulai dan setiap 2 detik. Respons 0/255, pembacaan tidak konsisten, atau identitas yang berubah dianggap gangguan SPI/perangkat. Versi nonstandar yang konsisten tetap diterima dengan log peringatan untuk menjaga kompatibilitas modul clone. Register versi merujuk pada [datasheet NXP MFRC522](https://www.nxp.com/docs/en/data-sheet/MFRC522.pdf). Ini belum menggantikan tes jarak baca, antena, dan kartu fisik. |
| RFID nonaktif | Scan UID dan endpoint validasi API/web mengembalikan HTTP 409 dengan `success=false` dan `code=RFID_INACTIVE` jika status bukan `aktif`. Pi menampilkan `RFID NONAKTIF`, merah, dua beep; transaksi ditandai ditolak, bukan dicoba selamanya. Penolakan terjadi sebelum perubahan aset. |
| Kompatibilitas | URL dan autentikasi API tetap. Pengambilan metadata RFID tetap menyertakan statusnya; lookup QR dan notifikasi UID untuk formulir tetap bekerja. Pemasangan tag melalui tindakan pendaftaran yang sah tetap dapat mengaktifkan RFID. |
| Retensi SQLite | Hanya transaksi `success` yang melewati retensi dibersihkan, maksimum 1.000 baris setiap 5 menit. `pending` dan `rejected` tidak dihapus. `SUCCESS_RETENTION_DAYS=0` menonaktifkan pembersihan. Tidak menjalankan VACUUM otomatis. |

## Batas jaminan penyimpanan

Transaksi yang sudah berhasil di-commit ke SQLite dipertahankan untuk retry memakai UUID yang sama. Pasangan lengkap yang **belum bisa ditulis** karena disk penuh/rusak atau database terkunci masih berada dalam RAM; kehilangan daya pada kondisi ini masih memerlukan scan ulang. LCD menunjukkan `SIMPAN GAGAL / PASANGAN DITAHAN` lalu `DAFTAR TERTUNDA`. Tidak mengklaim data tersebut sudah tersimpan permanen.

Pembacaan UID untuk formulir dan frame kamera tetap bersifat sementara, bukan transaksi antrean. Gunakan satu proses/service scanner untuk satu perangkat. Jaminan waktu LED adalah timer software terpisah; gangguan kernel, driver GPIO, penghentian paksa, dan putus daya tetap memerlukan pemeriksaan fisik.

## Konfigurasi

Nilai bawaan baru tersedia tanpa menimpa `.env` lama:

```dotenv
SCAN_MAX_AGE_SECONDS=5
SCAN_REPEAT_SECONDS=3
WORKER_STALL_SECONDS=30
SHUTDOWN_TIMEOUT_SECONDS=8
SUCCESS_RETENTION_DAYS=30
```

`SCAN_REPEAT_SECONDS` adalah jeda minimum menerima ulang nilai yang sama setelah dilepas dan ditempelkan/ditampilkan lagi. Menahan kartu atau QR di depan pembaca tidak menghasilkan scan terus-menerus. Pergantian mode mereset deteksi. `WORKER_STALL_SECONDS` harus minimal dua kali timeout HTTP ditambah 5 detik. Batas shutdown harus minimal timeout HTTP ditambah 1 detik. Nilai NaN, tak hingga, negatif, dan nilai di luar rentang ditolak sebelum GPIO dibuka. Saat memakai timeout HTTP lebih panjang daripada bawaan 6 detik, sesuaikan kedua ambang itu.

## Penerapan dan backup

1. Hentikan service sebelum mengganti file; jangan jalankan scanner lama bersamaan dengan service baru.
2. Backup program, `.env`, dan SQLite. Contoh berikut memakai lokasi database bawaan; sesuaikan bila `DB_PATH` berbeda:

   ```bash
   sudo systemctl stop "asset-iot@$(whoami).service"
   cd ~/raspi_asset_iot
   backup_dir="$HOME/asset-iot-backups/$(date +%Y%m%d-%H%M%S)"
   mkdir -p "$backup_dir"
   chmod 700 "$backup_dir"
   cp -p .env "$backup_dir/raspi.env"
   sqlite3 data/queue.sqlite3 ".backup '$backup_dir/queue.sqlite3'"
   tar --exclude=./.env --exclude=./data -czf "$backup_dir/program.tar.gz" .
   ```

3. Salin file program versi baru sambil mempertahankan `.env` dan folder `data`. Periksa kebijakan retensi sebelum menjalankan versi baru; gunakan `SUCCESS_RETENTION_DAYS=0` bila semua riwayat lokal ingin dipertahankan.
4. Terapkan controller Laravel dan jalankan `php artisan optimize:clear`. Tidak ada migrasi MySQL. SQLite hanya menambahkan indeks secara idempoten; UUID dan format tabel antrean tetap kompatibel.
5. Perbarui unit dan mulai kembali:

   ```bash
   python3 app.py --diagnose
   sudo install -m 0644 systemd/asset-iot@.service /etc/systemd/system/asset-iot@.service
   sudo systemctl daemon-reload
   sudo systemctl enable --now "asset-iot@$(whoami).service"
   journalctl -u "asset-iot@$(whoami).service" -f
   ```

## Rollback

Hentikan service, pulihkan file program dan unit dari backup, lalu jalankan `daemon-reload` dan mulai ulang service. Pertahankan SQLite terkini: versi sebelumnya masih dapat membacanya dan indeks tambahan tidak mengubah format data. Pemulihan backup database hanya dilakukan jika memang diperlukan, setelah mempertimbangkan transaksi baru sejak backup. Tidak perlu `migrate:rollback` pada Laravel. Pengembalian controller lama juga mengembalikan perilaku lama yang belum menolak tag nonaktif.

## Pengujian

```bash
cd raspi_asset_iot
python3 -m unittest discover -s tests -v
```

Pengujian otomatis mencakup mode berganti saat pembacaan berjalan, kembali ke mode semula, scan kedaluwarsa, SQLite penuh/terkunci, retry pasangan tanpa scan ulang, respons lama, retensi terbatas, RC522 tanpa respons, cooldown scan, seluruh alur register memakai perangkat simulasi, timer LED saat I2C tertahan, worker mati/macet, dan urutan shutdown. Laravel menguji tag `nonaktif`/`belum_terdaftar`, tidak adanya perubahan aset ketika ditolak, QR/UID tetap tersedia, serta aktivasi ulang dengan ID scan baru.

Hasil verifikasi: 39 tes Python lulus pada Windows dan Linux (WSL); 53 tes Laravel / 301 assertion lulus menggunakan SQLite dalam memori. Unit systemd diperiksa menggunakan `systemd-analyze verify`. Ini bukan hasil uji pada Raspberry Pi fisik.

Pengujian hardware yang harus dilakukan pada Pi:

1. Uji warna LED dan buzzer dengan wiring yang sama, lalu uji LCD di `0x27`.
2. Pindahkan switch sambil QR dibaca; scan dari mode sebelumnya tidak boleh menjadi pasangan di mode baru.
3. Putuskan koneksi server, buat pasangan, restart service setelah pasangan masuk SQLite, lalu sambungkan kembali. Pastikan satu pemasangan dan satu riwayat transaksi.
4. Nonaktifkan tag di website, gunakan mode Cek: harus merah, dua beep, `RFID NONAKTIF`. Aktifkan kembali dan lakukan scan baru setelah cooldown.
5. Hentikan service ketika LED/buzzer aktif; output harus mati. Uji reboot dan pastikan service kembali aktif.
6. Untuk uji gangguan USB/SPI/I2C, hentikan daya saat mengubah kabel GPIO, lalu jalankan ulang. Periksa retry, log, dan status dashboard. Ukur FPS, suhu, dan pemakaian CPU saat kamera serta RFID digunakan bersamaan.
