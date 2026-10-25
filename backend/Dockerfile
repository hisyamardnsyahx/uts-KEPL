# Backend Laravel dijalankan dengan FrankenPHP (bukan php artisan serve,
# bukan PHP-FPM + Nginx). Image dasar dipin ke versi yang jelas, bukan latest.
# TIDAK ADA password/key/token yang ditulis di berkas ini: semua kredensial
# masuk lewat environment dari docker-compose.yml (yang membacanya dari .env).
FROM dunglas/frankenphp:1.13.3-php8.4

WORKDIR /app

# Ekstensi yang dibutuhkan Laravel + driver MySQL.
RUN install-php-extensions pdo_mysql zip intl opcache

# Composer hanya untuk tahap build di dalam image ini.
COPY --from=composer:2.8.10 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Konfigurasi Caddy/FrankenPHP (mendengarkan port 80 di dalam container).
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
