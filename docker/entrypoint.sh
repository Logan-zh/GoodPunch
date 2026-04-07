#!/bin/bash
set -e

# Cloud Run injects PORT (default 8080); local Docker defaults to 8000
APP_PORT="${PORT:-8000}"

# If using SQLite, ensure the database file exists before migrating
if [ "${DB_CONNECTION}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    echo "[entrypoint] SQLite database: $DB_FILE"
fi

echo "[entrypoint] Running migrations..."
php artisan migrate --force

echo "[entrypoint] Caching config / routes / views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[entrypoint] Starting Octane/Swoole on port ${APP_PORT}..."
exec php artisan octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port="${APP_PORT}" \
    --workers=auto \
    --task-workers=auto
