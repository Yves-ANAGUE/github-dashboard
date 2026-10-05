#!/bin/bash
set -e

cd /var/www/html

echo "==> Clearing old caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

echo "==> Caching config..."
php artisan config:cache

echo "==> Caching routes..."
php artisan route:cache

echo "==> Caching views..."
php artisan view:cache

echo "==> Running migrations..."
php artisan migrate --force || echo "Migration failed, continuing anyway"

echo "==> Creating storage link..."
php artisan storage:link || true

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx..."
nginx -g "daemon off;"