# Pemeriksaan awal dan umpan balik mode Daftar

Perubahan ini berlaku saat service scanner mulai, sesudah OS boot. Tidak ada perubahan wiring, token, database, format UID, maupun proses pemasangan QR + RFID. Aturan pasangan belum lengkap 90 detik ditunda per permintaan pengguna dan masih mengikuti `PAIR_TIMEOUT_SECONDS` lama, termasuk pesan `WAKTU HABIS`.

## Perilaku

1. LCD menampilkan `CEK KOMPONEN / MOHON TUNGGU`. Scanner menunggu kesiapan webcam, RC522, dan LCD maksimal `STARTUP_CHECK_SECONDS` (bawaan 8, rentang 1–15 detik). Switch dan pembacaan SQLite turut diperiksa. Komponen yang dinonaktifkan tidak dianggap rusak. Internet tidak menjadi syarat siap menerima scan.
2. LED diuji merah → hijau → biru, masing-masing sekitar 180 ms. Jika komponen siap: LCD `CEK KOMPONEN OK`, hijau sesaat, satu beep. Jika belum siap: `KOMPONEN GAGAL` dan nama komponen, merah sesaat, dua beep; sensor yang sehat tetap dapat dipakai, sensor bermasalah tetap mencoba ulang. SQLite yang tidak bisa dibaca menggagalkan startup agar tidak mengaku siap menyimpan scan.
3. Setelah indikator sementara selesai, standby **biru**. Biru juga menandai gagal komunikasi, dibedakan oleh pesan LCD dan dua beep. Shutdown tetap mematikan seluruh warna serta buzzer. Polaritas common anode tetap active-low.
4. Mode Daftar: pembacaan pertama QR atau RFID berbunyi satu kali sebagai tanda **scan diterima**, bukan bukti pemasangan selesai. RFID yang tiba lebih dahulu diperiksa melalui GET `/api/rfid/asset`, tanpa mengubah aset. LCD menunjukkan `RFID TERDAFTAR` + ID aset, `BELUM TERDAFTAR / SCAN QR CODE`, atau `RFID NONAKTIF`. Setelah pesan sementara, status tag tetap tampil pada baris pertama selama menunggu QR.
5. Lookup gagal jaringan/token/server menghasilkan `STATUS TAK DIKET.`, biru, dua beep; tidak menyimpulkan tag belum terdaftar. UID tetap disimpan sebagai pasangan sementara. QR yang kemudian diterima tetap membuat transaksi SQLite seperti sebelumnya.
6. Pasangan lengkap disimpan dahulu ke SQLite. Setelah server mengonfirmasi: `DAFTAR BERHASIL`, hijau, satu beep. Pasangan sama yang sudah aktif: `RFID TERDAFTAR`, hijau, satu beep, tanpa riwayat perubahan tambahan. Tag milik aset lain ditolak: `RFID TERDAFTAR / KE ASET LAIN`, merah, dua beep. Aset dengan tag lain: `ASET ADA RFID / CEK RFID DI WEB`, merah, dua beep. Replay UUID tetap mengembalikan respons semula.

Lookup memakai worker dan session HTTP sendiri, timeout maksimal dua detik, hanya satu permintaan terbaru menunggu. Respons lama tidak boleh menimpa LCD setelah UID baru, pasangan lengkap, pergantian mode, atau batas waktu pasangan. Semua worker tetap diawasi dan ditutup. Scan yang tertangkap selama pemeriksaan awal diabaikan; mulai menempelkan tag setelah LCD siap.

Uji software tidak dapat memastikan warna LED atau bunyi fisik karena komponen ini tidak memiliki sensor umpan balik. Pemeriksaan awal memastikan driver dapat dibuka dan perintah output dijalankan; periksa warna, bunyi, serta tulisan LCD langsung di perangkat.

## File berubah

| File | Perubahan |
| --- | --- |
| `raspi_asset_iot/app.py` | Pemeriksaan awal, standby, beep scan parsial, penjagaan konteks dan tampilan status RFID. |
| `raspi_asset_iot/hardware.py` | Warna idle yang dipulihkan timer di bawah lock; shutdown tetap semua mati. |
| `raspi_asset_iot/api.py` | Lookup metadata terpisah dan penerjemahan hasil pemasangan ke LCD. |
| `raspi_asset_iot/config.py`, `.env.example` | Batas waktu pemeriksaan awal; `.env` aktual tidak ditimpa. |
| `app/Http/Controllers/RfidController.php` | Field tambahan `data.already_registered` serta kode konflik, tanpa mengganti endpoint/status/field lama. |
| Tes Python dan `RfidEventReceiptTest.php` | Startup, LED, beep, lookup, race respons lama, antrean offline, konflik dan replay. |

## Instalasi dan migrasi

1. Deploy Laravel dari commit yang sama terlebih dahulu. Tidak ada migrasi MySQL atau perubahan skema SQLite pada pembaruan ini. Jalankan tes sebelum push.
2. Di Pi, hentikan `asset-iot@bagastyan.service`. Buat direktori backup baru mode 700 di luar folder program. Backup program dan `.env` secara privat; gunakan `sqlite3.Connection.backup()` atau `.backup` SQLite untuk salinan konsisten dari `DB_PATH` aktual.
3. Ambil source commit GitHub yang sudah di-push. Salin file program saja; pertahankan `.env`, `data`, `.venv`, dan override systemd. Bandingkan hash `.env`, konfigurasi GPIO, serta UUID antrean sebelum/sesudah. Jangan menyalin `.env.example` ke `.env` lama.
4. Instalasi Pi ini menggunakan `.venv` dengan `--system-site-packages`, `pyzbar` di venv, dan `libzbar0t64` dari APT. Pertahankan interpreter tersebut karena paket `python3-pyzbar` tidak tersedia di repositori APT perangkat ini. Dependensi baru tidak ditambahkan dalam pembaruan ini.
5. Jalankan `.venv/bin/python -m unittest discover -s tests -v`, lalu mulai service. Pastikan `systemctl is-enabled asset-iot@bagastyan.service` bernilai `enabled`, `is-active` bernilai `active`, dan log pemeriksaan awal menunjukkan kesiapan yang sebenarnya.

## Rollback

Hentikan service, pulihkan **file program** dari backup, pertahankan `.env`, `.venv`, override systemd, serta SQLite terkini, lalu mulai service. Jangan memulihkan database lama hanya untuk rollback kode: tindakan itu dapat menghilangkan scan yang masuk setelah backup. Respons tambahan Laravel kompatibel dengan Pi lama; tidak perlu rollback database. Jika mengembalikan controller Laravel lama, Pi baru tetap bisa memakai respons lama, tetapi detail konflik/pasangan yang sudah ada menjadi pesan umum.

## Pengujian

Otomatis: 50 tes Python dan 71 tes Laravel / 448 assertion lulus di Windows. Tes mencakup startup terputus/komponen gagal, standby biru dan shutdown mati, pasangan saat offline, lookup timeout/HTML/token/UID salah, respons lama setelah switch/scan baru/selesai/timeout, konflik tanpa perubahan aset, serta replay idempoten.

Hardware:

1. Restart service: periksa urutan merah/hijau/biru, LCD hasil pemeriksaan, satu beep ketika siap, lalu standby biru. Tidak perlu reboot OS untuk memicu pemeriksaan ini.
2. Dalam mode Daftar, tap tag terdaftar tanpa QR: satu beep penerimaan, LCD menunjukkan status dan ID yang benar. Lepaskan lalu tap tag belum terdaftar: satu beep dan `BELUM TERDAFTAR`. Lookup ini tidak mengubah aset.
3. Scan QR aset uji yang sesuai dan tag: pastikan hasil akhir hijau + satu beep; pasangan sama tidak menghasilkan perubahan riwayat tambahan. Gunakan aset/tag uji untuk mencoba konflik dan tag nonaktif.
4. Putuskan akses server: status harus tidak diketahui, bukan belum terdaftar. Lengkapi pasangan, pastikan masuk antrean; sambungkan lagi dan periksa satu pemasangan. Jangan hapus antrean saat pengujian.
5. Pindahkan switch atau tap tag berbeda saat lookup menunggu: respons lama tidak boleh mengganti hasil terbaru. Hentikan service saat indikator aktif: LED/buzzer harus mati. Nyalakan kembali dan cek dashboard Online, preview, serta UID realtime.
