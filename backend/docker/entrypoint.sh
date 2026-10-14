#!/bin/sh
set -e

cd /var/www/html

# Always read the container environment, never a stale cached config.
php artisan config:clear >/dev/null 2>&1 || true

# Wait for MySQL. The compose healthcheck normally covers this, but keep it
# robust for standalone runs.
host="${DB_HOST:-mysql}"
port="${DB_PORT:-3306}"
until php -r 'exit(@fsockopen(getenv("DB_HOST") ?: "mysql", (int) (getenv("DB_PORT") ?: 3306)) ? 0 : 1);'; do
    echo "Waiting for MySQL at ${host}:${port}..."
    sleep 2
done

php artisan migrate --force --seed
php artisan storage:link >/dev/null 2>&1 || true

exec php \
    -d upload_max_filesize=6M \
    -d post_max_size=8M \
    -d display_startup_errors=0 \
    -S 0.0.0.0:8000 \
    -t public server.php
