#!/usr/bin/env bash
set -euo pipefail
if [[ "$(id -u)" == "0" ]]; then
  echo 'Jalankan sebagai user biasa, bukan sudo bash install.sh'
  exit 1
fi
ROOT="$(cd "$(dirname "$0")" && pwd)"
echo "[1] Instal dependency Trixie via apt ..."
sudo apt update
sudo apt install -y python3-gpiozero python3-lgpio python3-spidev python3-smbus python3-opencv python3-pyzbar python3-requests python3-dotenv i2c-tools v4l-utils sqlite3
if [[ ! -f "$ROOT/.env" ]]; then
  cp "$ROOT/.env.example" "$ROOT/.env"
  chmod 600 "$ROOT/.env"
  echo "File konfigurasi dibuat: $ROOT/.env"
fi
mkdir -p "$ROOT/data"
if [[ "$ROOT" != "$HOME/raspi_asset_iot" ]]; then
  echo "Perhatian: service systemd standar mengharapkan folder di $HOME/raspi_asset_iot"
  echo "Pindahkan folder ke sana sebelum mengaktifkan service (atau sesuaikan unit)."
fi
echo '[2] Aktifkan SPI dan I2C lewat sudo raspi-config, lalu reboot.'
echo '[3] Ubah .env: URL/token, pin sesuai wiring, LCD/LED/buzzer setelah aman.'
echo '[4] Jalankan: python3 app.py --diagnose'
echo '[5] Uji tiap komponen: python3 hardware_test.py switch, rfid, camera, lcd, led, buzzer.'
echo '[6] Setelah tes aman, jalankan: python3 app.py'
