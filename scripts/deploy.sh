#!/usr/bin/env bash
# Called after GitHub Actions has checked out the matching commit on EC2.
# Frontend compilation belongs on CI, never on this small server.
set -euo pipefail

archive=${1:?Usage: deploy.sh FRONTEND_ARCHIVE COMMIT}
revision=${2:?Expected commit is required}
APP_PATH="${EC2_APP_PATH:-/var/www/html/asset_management}"
cd "$APP_PATH"
test "$(git rev-parse HEAD)" = "$revision"
test -f "$archive"

PHP_SERIES=$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')
if ! php -r 'exit(extension_loaded("gd") ? 0 : 1);'; then
    sudo -n apt-get update
    sudo -n apt-get install -y --no-install-recommends "php${PHP_SERIES}-gd"
fi

echo '[1/7] Prepare writable Laravel directories'
sudo mkdir -p storage/logs storage/framework/{cache,sessions,views} storage/app/public/tickets bootstrap/cache
sudo chown -R "$USER:$USER" storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
touch storage/logs/laravel.log
chmod 664 storage/logs/laravel.log
restore_permissions() {
    sudo chown -R www-data:www-data storage bootstrap/cache
    sudo chmod -R 775 storage bootstrap/cache
}
trap restore_permissions EXIT

echo '[2/7] Install production PHP dependencies'
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo '[3/7] Install frontend built by CI'
bash scripts/install-frontend.sh "$archive" "$revision" "$APP_PATH"

echo '[4/7] Clear cached configuration and check database'
php artisan config:clear
# Read-only check: never restart/reinstall MySQL or change its configuration here.
ready=false
for attempt in 1 2 3 4 5; do
    if php -r '
        require "vendor/autoload.php";
        $app = require "bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        try { $app->make("db")->connection()->getPdo(); }
        catch (Throwable $e) { fwrite(STDERR, "Database is not ready\n"); exit(1); }
    '; then
        ready=true
        break
    fi
    sleep 3
done
if [ "$ready" != true ]; then
    echo 'Database unavailable. Check MySQL and memory before retrying deployment.' >&2
    free -h
    swapon --show
    df -h /
    systemctl show mysql -p ActiveState -p SubState -p Result || true
    exit 1
fi

echo '[5/7] Run migrations and cache Laravel'
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo '[6/7] Restart queue workers'
php artisan queue:restart
restore_permissions
trap - EXIT
sudo -u www-data test -w storage/app/public/tickets
test "$(readlink -f public/storage)" = "$(readlink -f storage/app/public)"

echo '[upload] Inspect active web-server upload configuration'
if systemctl is-active --quiet nginx; then
    sudo nginx -T 2>&1 | awk '/^# configuration file / || /^[[:space:]]*(listen |server_name |root |client_max_body_size |fastcgi_pass |include |location )/ { print }'
fi
if [ -f "/etc/php/${PHP_SERIES}/fpm/php.ini" ]; then
    awk '/^[[:space:]]*(upload_max_filesize|post_max_size)[[:space:]]*=/ { print }' "/etc/php/${PHP_SERIES}/fpm/php.ini"
fi

echo '[7/7] Reload web server'
if systemctl is-active --quiet "php${PHP_SERIES}-fpm"; then
    sudo systemctl reload "php${PHP_SERIES}-fpm"
fi
sudo systemctl reload nginx 2>/dev/null || \
    sudo systemctl reload apache2 2>/dev/null || \
    echo 'Web server reload skipped'
echo "Deployment completed for $revision"
