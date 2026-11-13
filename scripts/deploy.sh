#!/bin/bash

# ─────────────────────────────────────────────────
# deploy.sh — Script deploy untuk AWS EC2
# Dijalankan oleh GitHub Actions saat push ke main
# ─────────────────────────────────────────────────

set -e  # Hentikan script jika ada perintah yang gagal

APP_PATH="${EC2_APP_PATH:-/var/www/html/asset_management}"
cd "$APP_PATH"

echo "📦 [1/8] Git pull latest code..."
git pull origin main

echo "🔧 [2/8] Install Composer dependencies (production)..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "🎨 [3/8] Install NPM & build assets..."
npm ci
npm run build

echo "🗄️  [4/8] Run database migrations..."
php artisan migrate --force

echo "⚡ [5/8] Clear & cache configs..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "🔄 [6/8] Restart queue workers..."
php artisan queue:restart

echo "🗂️  [7/8] Set correct file permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || \
  chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "🌐 [8/8] Reload web server..."
# Uncomment sesuai web server yang dipakai:
sudo systemctl reload nginx 2>/dev/null || \
sudo systemctl reload apache2 2>/dev/null || \
echo "⚠️  Web server reload skipped (nginx/apache2 not found)"

echo ""
echo "✅ Deploy selesai pada $(date '+%Y-%m-%d %H:%M:%S WIB')"
