# Audit UI, scanner, dan responsivitas Inventaris TI

Audit mencakup antarmuka staf dan admin, dashboard, inventaris, paket aset, tiket, lokasi, riwayat, scanner QR/RFID, login, dan laporan publik. Alur kamera ditelusuri dari webcam dan service Pi hingga penyimpanan Laravel dan tampilan browser.

| Dampak | Temuan pada kode awal | Perbaikan |
| --- | --- | --- |
| Kritis | `raspi_asset_iot/app.py` membaca QR tetapi tidak mengirim gambar. Petunjuk browser mengarah ke IP LAN dan menyarankan mengizinkan mixed content. | Webcam yang sama sekarang mengirim JPEG dari proses terpisah ke Laravel melalui `API_BASE_URL`. Browser mengambil gambar dari origin aplikasi dan menunjukkan status tanpa alamat IP lokal. |
| Mayor | Kegagalan saat membuka LCD opsional menghentikan seluruh service sebelum kamera dan RFID dimulai. | Kegagalan akses I2C dicatat di jurnal. Scanner, antrean, dan upload kamera dapat tetap berjalan. |
| Kritis | UI membaca hasil scan sebagai `data.data.data`, padahal objek aset berada pada `data.data`. Halaman validasi RFID hanya bereaksi pada input UID manual. | Keduanya mengambil hasil terbaru dari server. Mode cek dan daftar menerbitkan hasil sukses dengan ID scan. Halaman validasi tetap menerima input manual. |
| Mayor | Endpoint upload kamera menerima file tanpa autentikasi atau validasi tipe, dan halaman pengganti mencoba mengambil stream dari IP yang diisi pengguna. | Upload memerlukan token RFID dan JPEG hingga 512 KB. Frame terbaru ditulis atomik. Rute proxy lama memakai stream yang dikelola Laravel, tanpa koneksi ke IP dari browser. Endpoint API hasil scan juga memerlukan token. |
| Mayor | Drawer mobile tidak menyimpan fokus atau menutup dengan Escape. Halaman sempit menumpuk judul, merek, avatar, dan indikator sistem yang statis. | Shell memakai navigasi yang konsisten, penanda halaman aktif, pintasan ke konten, tombol berlabel, dan drawer yang dapat dikendalikan dengan keyboard. |
| Mayor | Tabel utama selebar 950 px memaksa pembaca seluler menggeser untuk membaca nama dan aksi. Pilihan filter langsung memuat ulang halaman. | Tabel inventaris menampilkan baris berlabel di layar sempit. Filter diterapkan dengan tombol. Tabel administratif yang lebar memiliki area gulir yang dapat difokuskan. |
| Mayor | Halaman laporan publik kehilangan deskripsi ketika validasi gagal. Pesan kesalahan tidak terhubung ke kolom. | Deskripsi diisi ulang dari input lama. Pesan per kolom terhubung lewat atribut ARIA, dan format foto dijelaskan sebelum pengguna memilih file. |
| Minor | Dashboard memakai beberapa gaya kartu dan aksi dengan bobot visual serupa. Login, informasi publik, dan halaman tiket punya kerangka visual sendiri. | Dashboard menempatkan ringkasan metrik dan aktivitas sebelum rincian. Layar publik dan staf memakai satu tipografi, palet gelap, warna aksen mint, dan ukuran kontrol yang konsisten. |
| Minor | Status persetujuan aset tampil sebagai *write off* dalam daftar. | Status investigasi dan penghapusan tampil sebagai menunggu persetujuan. |

## Cakupan tampilan dan penilaian

Penilaian hierarki visual dari sumber awal memenuhi 3 dari 8 kriteria Refactoring UI, sekitar 4/10. Lebar teks, hierarki label tabel, dan pembeda dasar permukaan sudah ada. Urutan aksi, konsistensi jarak, keterbacaan tanpa warna, kontras teks kecil, dan kedalaman panel perlu pembenahan. Redesign menempatkan permukaan dan pola interaksi di stylesheet bersama. Layar utama yang dibangun ulang adalah dashboard, scanner, validasi RFID, inventaris, login, informasi aset publik, formulir laporan, dan konfirmasi tiket. Layar pengelolaan lain menggunakan shell, tipografi, fokus, dan palet baru.

Validasi kontras dan keyboard untuk komponen utama tidak menggantikan audit WCAG lengkap dengan pembaca layar dan pengguna nyata. Foto webcam dan pembacaan RC522 fisik juga memerlukan pengujian pada Pi.

## Verifikasi

- Chromium memuat 18 halaman dengan data fiktif pada lebar 320, 390, 820, dan 1440 px tanpa overflow horizontal pada halaman. Uji interaksi mencakup login, Escape dan pemulihan fokus menu, filter aset, upload JPEG simulasi, hasil RFID terbaru, validasi UID manual, dan pengiriman laporan publik.
- Suite Laravel lulus dengan 44 tes dan 204 assertion. Suite Python lulus dengan 13 tes. Vite berhasil membuat bundle produksi.
- Untuk membuka desain lokal, jalankan `npm ci` lalu `npm run dev` bersama server Laravel.

## Cek saat menjalankan di Pi

1. Terapkan versi Laravel dan Pi dari perubahan yang sama. Gunakan domain HTTPS dan awalan `/api` untuk `API_BASE_URL`. Pertahankan `.env` serta database antrean saat menyalin folder Pi, sesuai [panduan Pi](../raspi_asset_iot/README.md).
2. Aktifkan `CAMERA_ENABLED=true` dan `CAMERA_STREAM_ENABLED=true`, lalu restart `asset-iot@<username>.service`. Gunakan satu proses untuk satu webcam. Periksa `CAMERA_INDEX` lewat `v4l2-ctl --list-devices` bila kamera tidak terbuka.
3. Buka **Scan aset → Raspberry Pi** lewat domain publik. Service yang berhasil mengirim gambar mencatat `Frame kamera diterima Laravel` di jurnal systemd. Port 5000 dan IP langsung EC2 tidak digunakan oleh browser dalam alur ini.
4. Pilih mode Cek dan tempel tag yang terdaftar. Hasil seharusnya tampil pada **Scan aset** dan **Validasi RFID**. Tag yang belum terdaftar ditandai oleh indikator Pi dan bisa diperiksa memakai kolom UID di halaman Validasi RFID.

## Perbaikan lanjutan pada scanner

- Sidebar memakai scrollbar tipis dengan warna sesuai tema.
- Pi menargetkan preview 5 FPS dengan JPEG maksimal 480 piksel. Decoder QR tetap memakai frame kamera asli dan mendahulukan ZBar seperti program lama, dengan cadangan OpenCV. Library QR browser dibundel lewat NPM sehingga pemuatannya mengikuti versi aplikasi.
- Browser menghindari unduhan ulang JPEG yang sama lewat ETag dan menghapus jeda tambahan 500 ms pada respons yang lambat. Target FPS bergantung pada waktu decode dan koneksi Pi ke server.
- Kolom UID di halaman validasi dan dialog pairing berlangganan notifikasi pembacaan RFID dari Pi. Tag yang belum terdaftar juga dapat mengisi kolom. Saat dialog dibuka, UID lama yang masih ada di server diabaikan. Pengisian kolom pairing menunggu konfirmasi **Simpan RFID**.
- Permukaan kartu, tabel, badge status, avatar, dan dialog memakai warna solid. Gradasi dan blur pada latar dialog telah dihapus.
- Pengujian lanjutan membaca 26 dari 26 label QR PNG proyek memakai ZBar asli di Linux. Uji browser mencakup pengisian UID tag terdaftar dan baru, pemindaian file QR demo, penolakan UID lama saat dialog dibuka, serta pembatalan pairing tanpa perubahan data.
- Setelah perbaikan lanjutan, suite Laravel lulus 45 tes dengan 226 assertion dan suite Python lulus 17 tes. Build produksi serta uji browser pada build statis lulus, termasuk pemeriksaan tidak adanya gradasi dan blur pada dashboard dan dialog pairing. FPS nyata, LED, buzzer, switch, dan pembacaan kamera/RC522 tetap perlu diuji pada perangkat Pi.

## Tema mengacu Tabler dan grafik dashboard

Atas pilihan pengguna, tampilan mengikuti [Tabler](https://tabler.io/admin-template): latar abu-abu terang, panel putih solid, aksen biru, border tipis, sidebar ringkas, dan font Inter. Ini implementasi di Blade/CSS proyek; tidak menambahkan framework atau runtime Tabler. Logo sidebar memakai simbol dari gambar logo login yang sama melalui clipping CSS, tanpa mengubah file gambar. Judul dashboard yang berulang dihapus, kontrol sidebar memakai ikon menu sederhana, dan bagian rincian grafik memakai chevron dengan kontrol keyboard bawaan `details`.

Grafik memakai SVG/HTML yang dirender server tanpa CDN atau dependensi JavaScript tambahan. Grafik donat mencakup semua status termasuk menunggu persetujuan; grafik lokasi membedakan gedung, lantai, dan ruangan serta menampilkan enam ruangan terbanyak dengan tabel lengkap yang bisa dibuka. Grafik tiket memuat enam bulan kalender, termasuk bulan kosong: laporan masuk dihitung dari tanggal dibuat, sedangkan selesai dari riwayat penyelesaian, maksimal satu per tiket per bulan. Pembaruan komentar tidak menggeser angka penyelesaian ke bulan lain. Angka ringkasan dan grafik berasal dari database, bukan data contoh yang ditulis dalam tampilan.

Panel Raspberry Pi memakai `GET /raspi/status` untuk staf login dan heartbeat bertoken `POST /api/raspi/heartbeat`. Pi mengirim setiap 10 detik; batas online 35 detik, data terakhir disimpan maksimum satu hari. Browser memeriksa setiap 5 detik, mengosongkan rincian saat offline, dan membedakan server tidak terjangkau dari Pi offline. Sebelum Pi diperbarui dan mengirim heartbeat, tampilannya memang belum terhubung. Status ini untuk satu Pi, sesuai sumber frame dan hasil scan tunggal yang digunakan aplikasi saat ini.

File utama: `DashboardController.php` (agregasi), `RaspiStatusController.php` (status perangkat), `dashboard.blade.php` dan partial `raspi-status` (grafik/panel), `layouts/layout.blade.php` dan `layouts/assets.blade.php` (shell/font), `app.css` (tema), `assets/index.blade.php` (filter), `assets/scan.blade.php` dan `assets/show.blade.php` (petunjuk QR/RFID), `raspi-status.js` (polling), serta `raspi_asset_iot/api.py`, `app.py`, dan `hardware.py` (heartbeat). Tes agregasi tersedia di `tests/Feature/DashboardTest.php`; autentikasi dan kedaluwarsa status di `tests/Feature/RaspiStatusTest.php`.

Instalasi: pertahankan `.env`, jalankan `npm ci`, `npm run build`, dan `php artisan view:clear`; bila route di-cache, buat ulang cache route. Perbarui file program Pi dan restart service systemd yang sudah dipasang. Tidak ada perubahan skema atau migrasi database. Rollback tampilan dengan mengembalikan file UI dan controller dashboard ke revisi sebelumnya lalu build ulang; jika heartbeat juga dikembalikan, pulihkan controller, route, dan program Pi yang terkait. Jangan menjalankan `migrate:rollback` untuk perubahan ini.

Verifikasi desain awal mencakup 11 halaman pada lebar 320, 390, 820, dan 1440 px tanpa overflow halaman; revisi berikutnya memeriksa filter/pencarian/reset, simbol logo, tombol sidebar, chevron dengan keyboard, modal pada tinggi layar 477 px, serta alur UID dan QR file. Penilaian heuristik visual sementara 8/10 (6 dari 8 diagnostik): skala jarak dan ukuran teks pada beberapa formulir lama masih perlu diseragamkan. Pengujian pengguna dan hardware fisik tetap diperlukan; penilaian ini bukan sertifikasi aksesibilitas.

Hasil akhir setelah penambahan heartbeat: Laravel 51 tes / 272 assertion, Python 20 tes, dan build Vite 90 modul lulus. Uji browser status mencakup belum pernah terhubung, online, offline, server tidak tersedia, pengosongan data lama, dan empat ukuran layar. Simulasi status hanya diintersep pada browser pengujian; tidak menerbitkan heartbeat palsu ke server preview.

## Pilihan tema dan penyempurnaan header

Tombol matahari/bulan pada header staf dan publik mengganti mode terang/gelap. Pilihan disimpan pada `localStorage` dengan key `inventaris-theme`; tanpa pilihan tersimpan mengikuti preferensi sistem. Inisialisasi di head dijalankan sebelum stylesheet untuk menghindari kilatan tema yang salah. `resources/js/theme.js` menyelaraskan tombol, perubahan preferensi sistem, dan pilihan antar-tab. Sesuai permintaan pengguna, mode gelap memakai hitam murni `#000000` pada halaman, sidebar, header, dan kartu; aksen tombol/grafik serta badge status dipertahankan. Warna tetap solid.

Header aplikasi dibuat sticky. Chevron sidebar berada di tepi kiri header dan berbalik sesuai keadaan buka/tutup. Label “Ruang kerja” dan paragraf petunjuk Pi dihapus; waktu terakhir terhubung tersedia pada title badge. Dropdown filter memakai chevron SVG dengan jarak kanan 12 px dan ruang teks kanan 40 px; pada forced-colors kembali ke kontrol native. Tidak ada migrasi atau paket baru; instalasi dan rollback mengikuti prosedur UI di atas.

Verifikasi lanjutan lulus pada 48 kombinasi halaman/ukuran layar untuk kedua tema, persistensi setelah navigasi/reload, preferensi sistem, chevron sidebar, dan jarak dropdown. Pemeriksaan akhir memastikan latar hitam murni, header sticky pada desktop/ponsel, serta pemulihan ke mode terang. Build Vite terakhir lulus dengan 91 modul.

Scrollbar sidebar disempurnakan menjadi 3 px pada Chromium, tanpa radius atau tombol panah, dengan thumb abu-abu mengikuti tema dan track sesuai sidebar. Properti scrollbar native dinonaktifkan hanya pada browser yang mendukung pseudo-element WebKit agar ukuran tepat tidak tertimpa; browser lain tetap memakai fallback `thin`. Uji browser memastikan gutter 3 px, radius 0, warna kedua tema, serta fungsi scroll pada menu terbuka dan diciutkan; build lulus.

## Pengajuan penggantian aset — 19 November 2026

Laporan pengguna: **Ajukan Ganti** kembali tanpa feedback dan pengajuan tidak terlihat
di indikator persetujuan administrator. Reproduksi menemukan dua masalah: alasan
kurang dari 10 karakter ditolak server tetapi error tidak ditampilkan dalam form,
dan badge navigasi hanya menghitung tabel `asset_approvals`, padahal penggantian
disimpan di `replacement_requests`. Query daftar administrator sendiri sudah
mengambil kedua jenis pengajuan.

`AssetApprovalController::storeReplacement` sekarang memakai error bag `replacement`
dan pesan validasi bahasa Indonesia. Form di `assets/show.blade.php` menyelaraskan
batas 10–1000 karakter, mempertahankan input, menampilkan error, dan otomatis
membuka kembali dialog setelah validasi server gagal. `AssetController::show`
mengambil pengajuan penggantian yang pending bagi staf login; tombol menampilkan
**Penggantian menunggu persetujuan** selama pengajuan belum diproses.
`layouts/layout.blade.php` menghitung kedua tabel untuk badge administrator.
Pengajuan yang valid tetap disimpan sebagai pending dan muncul di bagian
**Permohonan Penggantian Aset** pada halaman Persetujuan aset. Pengajuan duplikat
tetap ditolak dan persetujuan tetap khusus administrator.

Regresi otomatis ada di `tests/Feature/ReplacementRequestTest.php`: pengajuan staf
hingga daftar admin, hitungan gabungan, error/input form, duplikasi, hak akses,
dan proses persetujuan hingga riwayat. Jalankan
`php artisan test --filter=ReplacementRequestTest` atau seluruh suite. Gunakan
konfigurasi testing SQLite, bukan database produksi.

Verifikasi perbaikan lulus 59 tes Laravel dengan 348 assertion. Uji browser pada
database QA terpisah memeriksa batas karakter native, penolakan server setelah
validasi browser dilewati, pesan error, dialog terbuka kembali, input tetap terisi,
pengiriman ulang pada viewport ponsel, feedback sukses, status pending, daftar
administrator, dan badge pengajuan. Tidak membuat pengajuan percobaan di produksi.

Tidak ada migrasi, endpoint API Pi, atau konfigurasi rahasia yang berubah.
Instalasi mengikuti deployment biasa. Rollback mengembalikan perubahan controller
dan Blade pada commit ini; jangan menghapus pengajuan yang sudah tersimpan atau
menjalankan rollback database. Pengajuan lama yang valid tetap kompatibel.
