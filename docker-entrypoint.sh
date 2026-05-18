#!/bin/sh
set -e

echo "==> Migrations PostgreSQL..."
php artisan migrate --force

echo "==> Optimisations production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Démarrage queue worker..."
php artisan queue:work --tries=3 --backoff=60 --daemon &

echo "==> Démarrage serveur Laravel..."
exec php artisan serve --host=0.0.0.0 --port=8000