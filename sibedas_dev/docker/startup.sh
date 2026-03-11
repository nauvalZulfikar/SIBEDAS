#!/bin/bash
set -e

echo "=== Sibedas Production Startup ==="

# Create necessary supervisor runtime directories
mkdir -p /var/log/supervisor /var/run/supervisor

# Create and set permissions for storage directories
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/framework/{sessions,views,cache}
mkdir -p /var/www/storage/app/public
mkdir -p /var/www/bootstrap/cache

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Wait for database to be ready
max_tries=40
count=0
echo "Waiting for database connection..."
sleep 5

while ! php artisan db:monitor > /dev/null 2>&1; do
    count=$((count + 1))
    if [ $count -gt $max_tries ]; then
        echo "ERROR: Database connection timeout after $max_tries attempts. Exiting."
        exit 1
    fi
    echo "Waiting for database... ($count/$max_tries)"
    sleep 5
done

echo "Database connected!"

# Run migrations (idempotent - safe to run on every startup)
echo "Running migrations..."
php artisan migrate --force

# Create storage symlink if not exists
echo "Setting up storage link..."
if [ ! -e /var/www/public/storage ]; then
    php artisan storage:link --force 2>/dev/null || true
fi

# Optimize Laravel for production
echo "Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Starting Supervisord (PHP-FPM + Queue + Scheduler) ==="
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
