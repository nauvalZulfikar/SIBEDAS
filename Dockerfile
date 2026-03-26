FROM node:18 AS node-base

# Development stage
FROM node-base AS development
WORKDIR /var/www
COPY package*.json ./
RUN npm install
COPY . .
EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# Local development stage for PHP
FROM php:8.2-fpm AS local
WORKDIR /var/www

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# Override PHP memory limit
COPY docker/php/memory-limit.ini /usr/local/etc/php/conf.d/memory-limit.ini

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create www-data user with same UID/GID as host user (1000:1000 is common for first user)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Copy application files
COPY . .

# Install dependencies
RUN composer install

# Create storage directories and set proper permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Create entrypoint script to fix permissions on startup
RUN echo '#!/bin/bash\n\
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache\n\
chmod -R 775 /var/www/storage /var/www/bootstrap/cache\n\
exec "$@"' > /entrypoint.sh && chmod +x /entrypoint.sh

USER www-data

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# Production stage
FROM php:8.2-fpm AS production
WORKDIR /var/www

# Install PHP extensions + supervisor
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# PHP-FPM: listen on TCP port 9000
RUN sed -i 's/listen = .*/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf

# Override PHP memory limit
COPY docker/php/memory-limit.ini /usr/local/etc/php/conf.d/memory-limit.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files (node_modules, .env, sibedas.sql excluded via .dockerignore)
COPY . .

# Install PHP dependencies (production, no dev)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Create required storage directories (will be overridden by bind mount on VPS)
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p storage/app/public \
    && mkdir -p bootstrap/cache

# Supervisor directories
RUN mkdir -p /var/log/supervisor /var/run/supervisor

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/laravel-production.conf /etc/supervisor/conf.d/laravel-production.conf

# Copy startup entrypoint
COPY docker/startup.sh /startup.sh
RUN chmod +x /startup.sh

# Set permissions (will be re-applied at startup for mounted volumes)
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["/startup.sh"]
