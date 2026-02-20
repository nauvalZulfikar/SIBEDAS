#!/bin/bash

# Enable error reporting
set -e
set -x

# Create necessary directories with proper permissions as root
mkdir -p /var/log/supervisor
mkdir -p /var/run/supervisor
chown -R www-data:www-data /var/log/supervisor
chown -R www-data:www-data /var/run/supervisor
chmod -R 775 /var/log/supervisor
chmod -R 775 /var/run/supervisor

# Create storage directories with proper permissions
mkdir -p /var/www/storage/logs
chown -R www-data:www-data /var/www/storage
chmod -R 775 /var/www/storage

# Ensure Laravel storage and cache directories are writable
chown -R www-data:www-data /var/www/bootstrap/cache
chmod -R 775 /var/www/bootstrap/cache

# Wait for database to be ready (with increased timeout and better error handling)
max_tries=30
count=0
echo "Waiting for database connection..."

# First, wait a bit for the database container to fully initialize
sleep 10

while ! php artisan db:monitor > /dev/null 2>&1; do
    count=$((count + 1))
    if [ $count -gt $max_tries ]; then
        echo "Database connection timeout after $max_tries attempts"
        echo "Checking database container status..."
        # Try to connect directly to MySQL to get more detailed error
        mysql -h db -u admindb_arifal -parifal201 -e "SELECT 1" || true
        exit 1
    fi
    echo "Waiting for database... ($count/$max_tries)"
    sleep 5
done

echo "Database connection established!"

# Run database-dependent commands
echo "Running database migrations..."
php artisan migrate --force

echo "Running database seeders..."
php artisan db:seed --force

echo "Optimizing Laravel..."
php artisan optimize:clear
php artisan optimize

# Start supervisor (which will manage PHP-FPM)
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf 