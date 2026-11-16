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
