#!/bin/bash
set -e

echo "[entrypoint] Running migrations..."
php artisan migrate --force

echo "[entrypoint] Caching config / routes / views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[entrypoint] Starting Octane/Swoole..."
exec php artisan octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port=8000 \
    --workers=auto \
    --task-workers=auto
