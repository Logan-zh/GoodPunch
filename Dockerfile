# ─────────────────────────────────────────────
# Stage 1 – Build front-end assets
# ─────────────────────────────────────────────
FROM node:20-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ─────────────────────────────────────────────
# Stage 2 – PHP + Swoole application
# ─────────────────────────────────────────────
FROM php:8.3-cli AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl libpng-dev libonig-dev libxml2-dev libzip-dev \
        libfreetype6-dev libjpeg62-turbo-dev zlib1g-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && pecl install swoole \
    && docker-php-ext-enable swoole \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies first (layer cache friendly)
COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-dev

# Copy application source
COPY . .

# Copy compiled front-end assets from stage 1
COPY --from=assets /app/public/build ./public/build

# Finalise autoloader & run post-install hooks
RUN composer dump-autoload --optimize \
    && composer run-script post-autoload-dump || true

# Ensure storage is writable by the web process
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
