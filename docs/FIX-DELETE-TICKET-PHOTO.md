# Error hapus aset dan unggah foto tiket

Perbaikan 19 November 2026 untuk laporan HTTP 500 di domain publik. Tidak ada migrasi database, penggantian `.env`, atau perubahan protokol Raspberry Pi. Pengujian penghapusan dan pembuatan tiket memakai SQLite/file terisolasi, bukan aset produksi.

## Masalah yang direproduksi dari kode

1. `AssetController::destroy()` menghapus aset terlebih dahulu, kemudian menulis audit dengan jabatan/lokasi sesi yang mungkin null. Kolom audit wajib terisi: kegagalan audit dapat menghasilkan HTTP 500 **setelah aset terhapus**. Kegagalan lain ketika membuat audit juga tidak mengembalikan aset dan relasinya.
2. Model `Asset` melarang penghapusan Write Off dengan exception umum. Controller tidak menanganinya; pengguna mendapat HTTP 500 untuk arsip yang memang tidak boleh dihapus.
3. `TicketController::storePublic()` memindahkan foto ke `public/uploads/tickets`, sementara deploy menyiapkan izin tulis hanya untuk storage dan bootstrap/cache. Lokasi ini tidak mengikuti disk Laravel dan mengabaikan `Storage::fake()` saat tes. Kontak pelapor dinyatakan opsional tetapi diakses sebagai key wajib; request yang tidak mengirim key tersebut menghasilkan error.
4. Tiket dan riwayat awal disimpan tanpa satu transaksi. Kegagalan riwayat dapat menyisakan tiket/foto yang belum lengkap.

Temuan di atas dibuktikan lewat tes regresi yang gagal pada kode sebelumnya. Pengguna melaporkan HTTP 500; stack trace produksi belum tersedia sehingga laporan ini tidak menyatakan semua kasus server disebabkan satu exception yang sama.

## Perilaku baru

- Hapus aset dan audit memakai satu transaksi dengan lock baris aset. Jika audit gagal, aset, relasi, dan audit semula tetap ada. Nilai aktor memakai profil login/role atau `-` jika sesi kosong. Perilaku foreign key tidak diubah: tiket terkait dihapus sesuai cascade lama, riwayat perubahan aset dipertahankan dengan ID aset null.
- Write Off tetap arsip permanen. Tombol hapus disembunyikan; permintaan langsung mendapat notifikasi yang jelas, tanpa menghapus arsip. Konfirmasi penghapusan aset biasa menjelaskan dampak pada tiket dan audit, serta menghindari interpolasi nama mentah ke JavaScript.
- Foto tiket baru disimpan pada disk public di `storage/app/public/tickets`, dengan nama acak dan URL `storage/tickets/...`. Foto lama di `public/uploads/tickets` tetap dapat dibuka; tidak dipindahkan atau dihapus. Validasi JPEG/PNG/WebP, maksimal 5 MB tetap berlaku.
- Kegagalan menulis foto menghasilkan pesan pada kolom foto, tanpa membuat tiket setengah jadi. Kontak pelapor boleh tidak dikirim. Tiket, audit aset, dan riwayat awal menjadi satu transaksi; jika transaksi gagal, foto yang baru ditulis dibersihkan.
- Pemeriksaan HTTP ke URL yang tidak cocok route membuktikan request 1,5 MB dan 5 MB ditolak Nginx dengan 413; request kecil mendapat 404 seperti semestinya, tanpa membuat tiket. Konfigurasi aktif menunjukkan vhost `/etc/nginx/sites-enabled/asset_management`, socket PHP 8.2-FPM, tanpa `client_max_body_size`, serta batas PHP awal 2 MB per file dan 8 MB POST.

## File

| File | Perubahan |
| --- | --- |
| `app/Http/Controllers/AssetController.php` | Penghapusan atomik, fallback aktor, dan feedback Write Off. |
| `app/Http/Controllers/TicketController.php` | Disk public untuk foto tiket, kontak opsional, transaksi dan cleanup. |
| `resources/views/assets/index.blade.php`, `show.blade.php` | Tindakan hapus sesuai status dan konfirmasi yang aman. |
| `scripts/deploy.sh` | Siapkan folder foto, storage link, dan periksa akses tulis www-data setelah pemulihan izin. |
| `scripts/configure-upload-limits.py`, `scripts/tests/test_upload_limits.py` | Backup, pembatasan unggahan khusus situs, validasi target dan rollback jika validasi Nginx gagal. |
| `tests/Feature/AssetDeletionTest.php`, `TicketPhotoUploadTest.php`, `TicketingTest.php` | Tes regresi dan foto dengan data gambar nyata. |

## Pemasangan

Pipeline deploy menjalankan dependency, migrasi yang sudah ada, cache dan reload seperti sebelumnya; pembaruan ini tidak menambah migrasi. Langkah baru membuat `storage/app/public/tickets`, menjalankan `php artisan storage:link`, lalu memeriksa bahwa www-data dapat menulis folder tersebut dan `public/storage` mengarah ke `storage/app/public` yang benar. Tidak menggunakan chmod 777 atau mengubah isi unggahan lama.

Penerapan manual dari folder Laravel:

```bash
php artisan storage:link
php artisan view:clear
sudo mkdir -p storage/app/public/tickets
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo -u www-data test -w storage/app/public/tickets
readlink -f public/storage
```

Jika `public/storage` sudah berupa direktori atau tautan ke lokasi berbeda, periksa isinya dan backup dahulu; jangan paksa menimpanya.

Untuk vhost Nginx yang sudah diperiksa, deploy menjalankan `configure-upload-limits.py`: cocokkan hostname dari APP_URL, satu root publik aplikasi dan socket FPM; backup file asli secara privat di `/etc/nginx/asset-management-backups/<batch>/`; lalu tambahkan request limit 16M hanya pada vhost aplikasi. SSL, redirect, lokasi lain dan default vhost dipertahankan. `.user.ini` pada public root dibuat/digabung, per file minimum 5M dan POST minimum 16M agar dua foto logbook juga muat; opsi lain dan batas yang sudah lebih besar tetap dipertahankan. `.user.ini` tidak dilacak Git. Konfigurasi global `php.ini` tidak ditimpa.

Perubahan ditulis atomik, diuji dengan `nginx -t`, lalu PHP-FPM direload sesuai socket vhost (bukan menebak dari versi CLI). Jika validasi/reload gagal, file dikembalikan sebelum error dilaporkan. Nginx direload pada akhir deploy. Skrip menolak vhost lain, root berbeda, socket tidak dikenal, symlink `.user.ini`, atau limit operator yang belum dikelola skrip, agar tidak menimpa konfigurasi di luar cakupan.

## Rollback

Kembalikan file kode ke commit sebelumnya dan bersihkan cache view. Pertahankan `public/storage`, seluruh folder storage/unggahan dan database terkini. Kode lama memakai `asset($ticket->photo)`, sehingga URL foto baru tetap dapat ditampilkan selama storage link dipertahankan. Tidak perlu rollback database. Jangan memulihkan database lama hanya untuk membatalkan kode karena dapat menghilangkan perubahan pengguna terbaru.

Rollback limit server bersifat terpisah: lokasi target asli dan keberadaan `.user.ini` dicatat pada `rollback.txt` di batch backup. Pulihkan vhost dan `.user.ini` yang benar dari batch tersebut (atau hapus hanya `.user.ini` yang baru dibuat jika sebelumnya tidak ada), jalankan `sudo nginx -t`, lalu reload Nginx dan PHP-FPM yang sesuai. Pastikan foto pengguna di `storage/app/public` tetap dipertahankan. Mengembalikan limit lama dapat memunculkan lagi penolakan foto besar.

## Pengujian

85 tes Laravel / 567 assertion lulus. Delapan tes baru mencakup hapus dengan sesi nullable, arsip Write Off, rollback audit dan tiket terkait, unggah gambar valid, kontak/foto opsional, rollback tiket/riwayat/foto, berkas tidak valid atau melebihi 5 MB, dan gagal tulis storage. Tes ticketing lama kini memakai gambar nyata dan memastikan file benar-benar tersimpan. Sintaks deploy diperiksa dengan `bash -n`; tes installer Linux dijalankan di CI (lingkungan WSL lokal tidak memiliki PHP, sehingga lima tes installer dilewati di sana).

Delapan tes tambahan konfigurasi unggahan lulus di Windows dan Linux: perubahan hanya pada vhost yang cocok, penerapan berulang, opsi/batas PHP lama dipertahankan, penolakan target atau limit ambigu, pemulihan file jika `nginx -t` gagal, backup, dan reload socket FPM yang benar.

Verifikasi setelah deploy: buat laporan kerusakan dengan foto pada aset pengujian, buka foto dari detail tiket sebagai staf, lalu uji hapus aset pengujian yang bukan Write Off. Periksa notifikasi dan auditnya. Arsip Write Off harus tetap ada dan tidak menawarkan hapus.
