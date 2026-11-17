# Deployment EC2 dengan RAM kecil

## Temuan 17 November 2026

Log systemd menunjukkan MySQL dihentikan OOM killer lalu otomatis hidup kembali.
Server memiliki RAM 909 MiB, tanpa swap, dan disk root tersisa sekitar 1,4 GiB.
`MemoryMax` dan `MemoryHigh` MySQL tidak dibatasi. Kegagalan migrasi
`SQLSTATE[HY000] [2002] Connection refused` konsisten dengan MySQL yang sedang mati;
log tersebut tidak menunjukkan kesalahan password database.

Build Node/Vite sebelumnya berjalan di EC2 bersama MySQL dan PHP. Memindahkan
build mengurangi tekanan memori saat deployment, tetapi tidak menjamin RAM cukup
untuk seluruh beban aplikasi. Periksa juga log kernel bila OOM berulang.

## Perubahan dan pemasangan

- `.github/workflows/deploy.yml`: tes PHP/Pi/installer, Node 22 dan build Vite
  berjalan di runner GitHub. Hasil build disimpan sebagai artifact selama 3 hari,
  lalu dikirim ke EC2 melalui SSH. Deployment produksi dijalankan bergantian.
- `scripts/deploy.sh`: menerima archive dan SHA commit; tidak menjalankan npm.
  Membersihkan config cache sebelum memeriksa koneksi database, mencoba koneksi
  lima kali, kemudian menjalankan migrasi yang memang sudah ada pada pipeline.
  Saat database tetap tidak tersedia, migrasi dihentikan dan statistik memori
  ditampilkan. Tidak mengubah konfigurasi MySQL atau membuat ulang database.
- `scripts/install-frontend.sh`: memeriksa SHA serta file dalam manifest Vite.
  Menyalin aset dahulu, baru mengganti manifest. File dengan hash lama dipertahankan
  untuk tab browser yang masih terbuka. Folder sementara dibersihkan.
- `scripts/tests/test_frontend_install.py`: pengujian instalasi, pengulangan,
  artifact commit lain, JS/CSS hilang, dan manifest kosong.

Push ke `main` menjalankan pipeline. Secret yang dipakai tetap `EC2_HOST`,
`EC2_USERNAME`, `EC2_SSH_KEY`, `EC2_APP_PATH`, dan `APP_ENV_PRODUCTION`.
File `.env` yang sudah ada tidak ditimpa. Tidak ada migrasi baru untuk perubahan
deployment ini; endpoint API dan kode Raspberry Pi tidak berubah.
Server memerlukan Bash, PHP beserta ekstensi aplikasi, Composer, Git, tar, dan
akses sudo yang sudah digunakan pipeline. Node/npm tidak diperlukan di EC2.

Untuk menjalankan manual, gunakan artifact dari run GitHub yang sukses dan
checkout commit yang sama. Dari direktori aplikasi:

```bash
export EC2_APP_PATH="$PWD"
bash scripts/deploy.sh /path/frontend-build.tar.gz SHA_COMMIT_40_KARAKTER
```

Jangan menjalankan ulang workflow lama: workflow lama masih membangun di EC2.
Gunakan run dari commit yang sudah berisi perubahan pipeline ini.

## Tambahan swap 512 MiB (jalankan di EC2)

Ini penyangga saat lonjakan memori, bukan pengganti penambahan kapasitas RAM/disk.
Disk server saat audit tidak cukup longgar untuk swap 2 GiB. Prosedur berikut
menolak menimpa `/swapfile` yang sudah ada dan mensyaratkan setidaknya 1 GiB
ruang kosong sebelum mengalokasikan 512 MiB. Jangan gunakan jika swap lain telah
dipasang setelah audit. Tidak perlu me-restart MySQL.

```bash
sudo bash <<'SWAP'
set -euo pipefail
if [ -e /swapfile ] || [ -L /swapfile ]; then
    echo '/swapfile sudah ada; periksa dulu, tidak ditimpa.' >&2
    exit 1
fi
if [ -n "$(swapon --show --noheadings)" ]; then
    echo 'Swap sudah aktif; periksa dulu.' >&2
    exit 1
fi
available=$(df --output=avail -k / | tail -1 | tr -d ' ')
test "$available" -ge 1048576 || { echo 'Ruang kosong kurang dari 1 GiB.' >&2; exit 1; }
install -m 600 /dev/null /swapfile
dd if=/dev/zero of=/swapfile bs=1M count=512 status=progress
mkswap /swapfile
swapon /swapfile
cp -an /etc/fstab /etc/fstab.before-asset-swap
if ! grep -qE '^[[:space:]]*/swapfile[[:space:]]' /etc/fstab; then
    printf '/swapfile none swap sw 0 0\n' >> /etc/fstab
fi
free -h
swapon --show
df -h /
SWAP
```

Pantau disk karena log, database, upload, dan aset build lama terus dapat tumbuh.
Tambah kapasitas volume bila ruang kosong terus turun; jangan menghapus data
MySQL, upload, atau log secara membabi buta. Jika alokasi swap gagal, periksa
status dan ruang disk sebelum mencoba lagi.

## Verifikasi

Di Linux/WSL, dari root proyek:

```bash
bash -n scripts/deploy.sh
bash -n scripts/install-frontend.sh
python3 -m unittest discover -s scripts/tests -v
npm ci
npm run build
```

Sesudah deploy, cek hasil run GitHub, buka login/dashboard melalui domain HTTPS,
pastikan CSS/JS tidak 404, lalu periksa dari EC2:

```bash
free -h
swapon --show
df -h /
sudo systemctl status mysql --no-pager
sudo journalctl -k --since '30 minutes ago' --no-pager | grep -Ei 'oom|out of memory|killed process' || true
```

Keberhasilan tes CI tidak membuktikan server bebas OOM atau hardware Pi sehat.

## Rollback

Sebelum deploy perubahan aplikasi/migrasi, gunakan prosedur backup database dan
file upload yang berlaku. Perubahan pipeline ini tidak memerlukan rollback
database. Jangan menggunakan `migrate:fresh` atau me-reset `.env`.

Untuk mengembalikan aplikasi, buat commit revert di `main` untuk perubahan
aplikasi yang dimaksud sambil mempertahankan pipeline build di GitHub; CI akan
membangun artifact yang cocok dan melakukan deploy. Bila pipeline sendiri
bermasalah, hentikan deploy sementara dan pulihkan kode beserta `public/build`
dari backup yang cocok, kemudian bangun ulang cache Laravel. Artifact hanya
tersimpan 3 hari; simpan artifact rilis penting di luar retensi tersebut.

Untuk menghapus swap yang dibuat langkah di atas, pastikan RAM cukup dahulu.
Jalankan `sudo swapoff /swapfile`; lanjutkan hanya jika berhasil. Hapus hanya
baris `/swapfile none swap sw 0 0` dari `/etc/fstab`, lalu hapus `/swapfile`.
Jangan menimpa seluruh fstab dengan backup lama bila sudah ada perubahan lain.

Referensi: [AWS: OOM pada EC2 Linux](https://repost.aws/knowledge-center/ec2-linux-high-memory-usage),
[GitHub: berbagi artifact antar job](https://docs.github.com/en/actions/tutorials/store-and-share-data).
