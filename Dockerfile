FROM php:8.4-fpm

# 1. 安裝系統依賴 + Node.js 22
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libsqlite3-dev \
    libzip-dev \
    zip \
    git \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_sqlite gd zip \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

# 2. 複製程式碼
COPY . /var/www/html
WORKDIR /var/www/html

# 3. 安裝 Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 4. 執行 Composer Install
RUN composer install --no-dev --optimize-autoloader

# 5. 安裝 npm 依賴並 build 前端
RUN npm ci && npm run build && rm -rf node_modules

RUN mkdir -p /var/www/html/bootstrap/cache

# 6. 在 build time 完成所有初始化（Cloud Run 無 volume，SQLite bake 進 image）
RUN cp .env.example .env \
    && php artisan key:generate \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chmod -R 775 storage bootstrap/cache \
    && rm -f database/database.sqlite \
    && touch database/database.sqlite \
    && php artisan migrate --force \
    && php artisan db:seed --force

EXPOSE 8080
# 啟動時只更新 APP_URL（若有設環境變數）再直接 serve，讓容器快速就緒
CMD ["sh", "-c", "\
    [ -n \"$APP_URL\" ] && sed -i \"s|APP_URL=.*|APP_URL=$APP_URL|\" .env ; \
    php artisan serve --host=0.0.0.0 --port=8080"]