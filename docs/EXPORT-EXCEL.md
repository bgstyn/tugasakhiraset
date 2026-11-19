# Ekspor daftar aset ke Excel

Tombol **Unduh Excel** pada daftar aset menghasilkan file `.xlsx` asli. URL `/assets/export` dan pembatasan login tetap sama; format CSV diganti sesuai permintaan pengguna. Manifest CSV untuk ekspor label QR melalui command Artisan tidak berubah.

## Isi dan tampilan

- Satu sheet `Daftar Aset`, judul, waktu ekspor beserta zona waktu aplikasi, header biru dengan teks putih, baris selang-seling dan garis pemisah tipis.
- 19 kolom: nomor urut, ID aset TI, inventaris, nomor seri, nama, kategori, merek, model, spesifikasi, gedung, lantai, ruangan, penanggung jawab, tahun, status, UID RFID, status RFID, tanggal input dan perubahan.
- Filter Excel pada baris 5; header dan dua kolom identitas dibekukan. Lebar kolom ditentukan menurut isinya. Deskripsi dibungkus dengan tinggi baris menyesuaikan, dibatasi tinggi maksimum Excel 409 pt; isi teks tidak dipotong. Teks sangat panjang tetap dapat dibaca melalui formula bar Excel.
- ID, inventaris, nomor seri dan UID tetap teks, termasuk angka nol di depan dan kode panjang. Tahun/nomor urut numerik, tanggal nilai Excel yang bisa diurutkan. Sel teks diawali `=` disimpan sebagai teks biasa, bukan formula.
- Semua hasil pencarian, status, lokasi, filter cepat dan urutan mengikuti daftar aset yang sedang dibuka. Ekspor memuat seluruh hasil, bukan hanya sepuluh baris halaman aktif. Hasil kosong tetap workbook valid dengan header, tanpa data palsu.

`AssetController::assetListQuery()` dipakai bersama oleh daftar dan ekspor agar logika filter tidak berbeda. `AssetExcelExport` membaca batch 500 aset menggunakan OpenSpout 4.28.5, tanpa memuat seluruh inventaris ke RAM. File sementara dibuat di direktori sistem di luar public, dihapus setelah respons dikirim atau saat penulisan gagal. Batas satu sheet 1.048.576 baris termasuk judul/header; ekspor yang melampaui batas gagal sebelum file dikirim.

## File dan instalasi

| File | Perubahan |
| --- | --- |
| `app/Services/AssetExcelExport.php` | Writer XLSX, format tabel, tipe data dan pembacaan batch. |
| `app/Http/Controllers/AssetController.php` | Query daftar bersama, unduhan aman dan pembersihan file sementara. |
| `resources/views/assets/index.blade.php` | Tombol Excel meneruskan filter aktif, tanpa nomor halaman. |
| `composer.json`, `composer.lock` | OpenSpout `~4.28.5`, kompatibel PHP 8.2/8.3/8.4. |
| `tests/Feature/AssetExcelExportTest.php` | Unduhan, login, format/tipe data, filter, hasil kosong, 501 baris dan cleanup gagal. |

Jalankan `composer install --no-interaction --prefer-dist --optimize-autoloader`, lalu `php artisan view:clear`. PHP memerlukan ekstensi dom, fileinfo, filter, libxml, xmlreader dan zip. Di Ubuntu, ekstensi XML tersedia melalui paket `php8.3-xml` atau versi PHP yang dipakai server; zip melalui paket `php8.3-zip`. Jangan mengubah `.env` atau database. Tidak ada migrasi. Pipeline deployment sudah memasang dependency dari lock file.

Rollback: kembalikan file controller, view, service, serta `composer.json`/lock ke commit sebelumnya; jalankan `composer install` dan `php artisan view:clear`. Tidak perlu rollback database atau konfigurasi Pi.

## Pengujian

77 tes Laravel / 516 assertion lulus pada SQLite terisolasi. Tes ekspor memeriksa XLSX yang dibaca kembali, header/filter/panes, string bernol depan, tanggal bertipe, teks berbahaya tidak menjadi formula, filter gabungan dan urutan, hasil kosong, lintas batas batch 500, warna banded pada sel teks dan tanggal, serta penghapusan file yang gagal dibuat.

Unduhan dari browser lokal dengan akun demo berhasil untuk enam aset dan filter `rusak` (satu aset). File dibaca kembali dan dirender untuk memeriksa tampilan kiri/kanan tabel. Tidak mengubah data demo atau database produksi.

## Temuan dependency yang terpisah

`composer audit --locked` pada 19 November 2026 melaporkan 38 advisory pada 10 dependency lama: guzzlehttp/guzzle, guzzlehttp/psr7, laravel/framework, league/commonmark, league/flysystem, symfony/http-foundation, symfony/http-kernel, symfony/mime, symfony/routing dan symfony/yaml. Versi semua paket tersebut sama dengan commit sebelum fitur Excel. OpenSpout tidak tercantum dalam daftar advisory pemeriksaan ini. Perbaikan dependency lama memerlukan pembaruan dan pengujian tersendiri; hasil tes fungsional tidak menyatakan seluruh dependency bebas kerentanan.

Untuk uji manual: buka daftar aset, terapkan status/lokasi, pilih **Unduh Excel**, lalu buka di Excel. Periksa jumlah hasil, filter header, scroll ke kanan/bawah, serta nomor seri/UID. Coba tanpa filter untuk seluruh inventaris dan pencarian tanpa hasil. Endpoint API RFID/kamera tidak berubah.
