# Audit dan penerapan IoT Asset Management

## Temuan dan perubahan

| Bagian | Temuan pada source | Perubahan |
| --- | --- | --- |
| Scanner Pi | `raspi_asset_iot/app.py` menangani mode cek dan daftar. Indikator memakai merah + hijau saat server gagal dan mode daftar membunyikan buzzer sebelum hasil API. Shutdown buzzer bisa berbenturan dengan thread yang sedang aktif. | `app.py`, `hardware.py`, `config.py`, dan `hardware_test.py` memakai kanal aktif-LOW untuk common anode, menyalakan satu warna per hasil, dan mematikan output saat idle dan shutdown. Buzzer memberi satu bunyi untuk sukses atau dua untuk gagal. Nomor BCM tetap dapat diatur dari konfigurasi. |
| Antrean | `core.py` sudah menyimpan UUID dalam SQLite, tetapi `api.py` belum mengirim UUID tersebut. Pengecekan UID sebelum POST tidak melindungi dari kegagalan respons setelah server memproses permintaan. | `core.py` dan `api.py` memakai UUID yang sama untuk semua percobaan, mempertahankan skema SQLite lama, dan menserialkan penambahan scan identik yang masih pending. |
| API Laravel | `/api/rfid-scan` dan `/api/rfid/register` memproses setiap POST secara terpisah. Middleware token membaca `env()` langsung meskipun pipeline deploy menjalankan `config:cache`. | `RecordRfidEvent.php`, migrasi `rfid_event_receipts`, `routes/api.php`, dan `RfidController.php` menyimpan hasil HTTP dan perubahan database dalam satu transaksi untuk request ber-UUID. `VerifyRfidToken.php` membaca token dari `config/services.php`. [Konfigurasi cache Laravel](https://laravel.com/docs/12.x/deployment#caching-configuration) |
| QR Laravel | `Asset.php` memakai URL absolut dari host request, mengambil gambar dari layanan eksternal, dan memakai kembali QR lama di `public/qrcodes`. Beberapa kode aset lama berisi spasi dan tanda kurung, sementara rute publik hanya menerima huruf, angka, dan tanda hubung. | `Asset.php`, `AssetQrCode.php`, `AssetController.php`, `routes/web.php`, dan view aset/tiket memakai `APP_URL` untuk isi QR, merender PNG/SVG secara lokal, dan membuka kode aset lama yang di-URL-encode. `ExportAssetQrCodes.php` membuat batch label pengganti di penyimpanan privat. |
| Boot dan pengujian | Template unit systemd sudah ada. | `asset-iot@.service`, `install.sh`, dan `.env.example` membawa konfigurasi boot dan alat backup. Tes Laravel dan Python berjalan dalam job tes workflow yang sudah ada. |

`composer.json` dan `composer.lock` menambahkan Endroid QR Code dan ekstensi GD. `.gitignore` mengecualikan database antrean Pi dan cache bytecode Python. Regresi diuji di `tests/Feature/AssetQrCodeTest.php`, `tests/Feature/RfidEventReceiptTest.php`, dan `raspi_asset_iot/tests/`.

Header `Idempotency-Key` opsional untuk kedua POST tersebut. Request lama tanpa header memakai kontrak API sebelumnya. Untuk request dengan header, satu UUID mewakili satu payload pada satu endpoint. Server mengirim ulang hasil yang sudah tersimpan dan mengembalikan HTTP 409 jika UUID dipakai dengan payload atau endpoint berbeda. Mode daftar Pi hanya memasangkan UID ke aset yang belum dipasangkan atau mengonfirmasi pasangan yang sudah sama. Gunakan mekanisme admin yang sudah ada untuk penggantian tag.

## Urutan pemasangan dan migrasi

1. Uji di staging dengan MySQL dan PHP 8.2 atau versi yang kompatibel dengan `composer.lock`. Aktifkan ekstensi GD di PHP CLI dan PHP web server. Pastikan domain publik mengarah ke Laravel dan sertifikat HTTPS valid. Set `APP_URL=https://domain-publik-anda` serta `RFID_API_TOKEN` lewat cara pengelolaan konfigurasi yang sudah dipakai. Gunakan token yang sama pada `API_TOKEN` di Pi. Pipeline lama punya langkah penulisan ulang `.env` dari secret CI. Jika memakai pipeline itu, verifikasi konfigurasi yang memang dikelola pipeline sebelum push ke `main`.
2. Sebelum migrasi, buat backup database dan QR lama di direktori privat di luar web root. Jalankan dari root proyek Laravel. Ganti nama database dan pengguna dengan nilai lingkungan Anda. Periksa kedua arsip berhasil dibuat.

   ```bash
   umask 077
   backup_dir="$(mktemp -d "$HOME/asset-iot-backup.XXXXXX")"
   mysqldump --single-transaction --no-tablespaces -u DB_USER -p DB_NAME > "$backup_dir/database.sql"
   tar -czf "$backup_dir/qrcodes-before.tar.gz" -C public qrcodes
   test -s "$backup_dir/database.sql" && tar -tzf "$backup_dir/qrcodes-before.tar.gz"
   ```

3. Pasang versi Laravel yang baru, baru perbarui scanner Pi. Migrasi hanya menambahkan tabel `rfid_event_receipts`. Jalankan dari root proyek Laravel dalam prosedur deploy yang biasa dipakai.

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan migrate:status
   ```

4. Hentikan service Pi. Backup antrean dengan SQLite `.backup`, yang menyertakan perubahan yang telah dikomit melalui WAL. Gunakan path dari `DB_PATH` bila instalasi lama memakai lokasi lain. Simpan konfigurasi secara privat dan salin file program baru tanpa menimpa `.env` atau folder data.

   ```bash
   cd ~/raspi_asset_iot
   sudo systemctl stop "asset-iot@$(whoami).service"
   umask 077
   pi_backup="$(mktemp -d "$HOME/asset-scanner-backup.XXXXXX")"
   cp .env "$pi_backup/scanner.env"
   sqlite3 -readonly data/queue.sqlite3 ".backup '$pi_backup/queue.sqlite3'" &&
     sqlite3 -readonly "$pi_backup/queue.sqlite3" 'PRAGMA integrity_check;'
   ```

5. Ikuti [instalasi dan pemetaan GPIO](../raspi_asset_iot/README.md). Pada instalasi lama yang sudah punya `LED_TYPE=common_cathode`, ubah nilai di `.env` ke `common_anode` setelah memeriksa rangkaian. Jalankan `python3 hardware_test.py led` untuk memastikan kanal fisik MERAH, HIJAU, dan BIRU menyala sesuai urutan. Perbarui nilai `LED_*_GPIO` jika warna yang terlihat berbeda. Jalankan `python3 hardware_test.py buzzer` untuk memeriksa satu dan dua bunyi pada BCM18. Setelah tes manual, aktifkan kembali service.

## Regenerasi label QR lama

1. Selesaikan backup QR sebelumnya. Jalankan pratinjau dan ekspor satu aset yang memang terdaftar. Ganti kode contoh sesuai data Anda.

   ```bash
   php artisan assets:export-qr --asset='TIPNP-2026-0001'
   php artisan assets:export-qr --asset='TIPNP-2026-0001' --write
   ```

2. Buka folder batch yang dicetak perintah di `storage/app/private/qr-exports/`. Gunakan `assets.csv` untuk mencocokkan kode aset, URL, dan nama PNG/SVG. Jika proses berhenti dengan `assets.partial.csv`, jalankan lagi untuk menghasilkan batch lengkap. Generator dari tampilan daftar/detail juga langsung menghasilkan gambar baru dari `APP_URL`.
3. Scan PNG atau label percobaan dari ponsel dengan Wi-Fi dimatikan. Pastikan alamat dimulai dengan domain HTTPS yang benar, detail aset tampil sebagai tamu, dan tombol **Laporkan Kerusakan** membuka formulir. Gunakan staging untuk uji submit tiket beserta konfirmasinya.
4. Setelah tes lulus, ekspor semua aset dengan `php artisan assets:export-qr --write`. Cetak dan ganti label yang masih menyimpan IP lokal. Alamat pada label cetak lama adalah bagian dari data QR dan baru berubah ketika label diganti.

## Pengujian

- Laravel lokal atau CI memakai SQLite in-memory dari `phpunit.xml`. Set `APP_KEY` uji dan jalankan `php artisan test`. Pengujian mencakup host request yang berbeda dari `APP_URL`, rute tiket untuk ID lama, ekspor PNG/SVG, replay setelah perubahan pasangan RFID, konflik UUID, dan rollback transaksi yang gagal.
- Pi atau komputer pengembangan memakai `requests` dan `python-dotenv`. Jalankan `python3 -m unittest discover -s tests -v` dari direktori `raspi_asset_iot`. Tes memeriksa polaritas keluaran, jumlah bunyi, cleanup, pencatatan bersamaan, hasil validasi dan gangguan server, serta pemakaian ulang UUID setelah antrean dibuka ulang.
- Uji GPIO dengan service berhenti. Jalankan `python3 app.py --diagnose`, lalu `hardware_test.py` satu per satu untuk `switch`, `rfid`, `camera`, `lcd`, `led`, dan `buzzer`. Pastikan LCD menampilkan kedua baris dan alamat `0x27` terdeteksi (lihat `sudo i2cdetect -y 1`). Uji RC522 dengan UID yang sudah dicatat di Laravel pada format yang sama.
- Uji alur penuh di staging. Scan aset valid dan invalid, lalu scan dengan internet terputus. Cocokkan warna/bunyi serta jumlah `pending`. Reboot Pi saat masih offline untuk memeriksa service dan persistensi antrean. Sambungkan kembali internet dan tunggu hingga percobaan berikutnya, maksimal lima menit setelah backoff mencapai batas. Untuk mode daftar, periksa Laravel hanya melakukan satu pemasangan dan satu set riwayat untuk UUID tersebut. Hentikan service ketika LED atau buzzer masih aktif untuk memeriksa cleanup, lalu uji boot dingin untuk memverifikasi kondisi pin sebelum service mulai.

## Rollback

1. Hentikan scanner sebelum menurunkan versi backend. Buat cadangan SQLite terkini dengan `.backup` dan simpan database antrean yang sedang dipakai. Me-restore backup yang lebih tua dapat menghilangkan scan baru dan mengulangi scan yang sudah diterima. Jika backend lama perlu dipakai sementara, tahan pengiriman antrean sampai versi yang mendukung UUID kembali, atau kosongkan antrean melalui backend baru sebelum downgrade.
2. Deploy rilis Laravel sebelumnya dan bangun ulang cache dengan konfigurasi yang berlaku. Tabel receipt yang ditambahkan kompatibel dengan rilis sebelumnya dan dapat disimpan untuk upgrade berikutnya. Jika harus menghapus tabel, simpan backup baru, pastikan tidak ada UUID yang masih mungkin dikirim ulang, dan gunakan rollback khusus migrasi ini. Jika migrasinya belum berada di batch terakhir, pilih batch yang benar dengan `--batch=N` setelah melihat tabel `migrations`.

   ```bash
   php artisan migrate:rollback --path=database/migrations/2026_11_16_000001_create_rfid_event_receipts_table.php
   ```

3. File di `public/qrcodes` tetap tersedia. Batch ekspor dapat diabaikan atau diarsipkan jika perlu membatalkan distribusi label. Jika folder lama pernah diganti lewat prosedur lain, ekstrak arsip `qrcodes-before.tar.gz` ke direktori sementara dan pulihkan file yang diperlukan.
4. Jika versi Pi juga perlu dikembalikan, pulihkan file program dan unit lama sambil mempertahankan `.env` serta SQLite terkini. Jalankan `sudo systemctl daemon-reload` sebelum mengaktifkan service kembali.

Tabel receipt menyimpan hasil selama event mungkin dikirim ulang, termasuk penolakan final. Jangan membersihkan tabel receipt selama masih ada antrean atau perangkat offline yang mungkin membawa UUID tersebut. Pantau ukuran database dan kapasitas SD card.
