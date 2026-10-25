#!/bin/sh
set -e

# APP_KEY: pakai yang dikirim lewat environment bila ada. Bila kosong,
# pakai kunci yang pernah dibuat dan tersimpan di volume storage; bila
# belum ada sama sekali, buat satu dan simpan di volume itu supaya tidak
# berubah-ubah setiap container dibuat ulang. Token Sanctum sendiri
# disimpan sebagai hash di database, jadi tidak bergantung pada APP_KEY.
if [ -z "$APP_KEY" ] && [ -f /app/storage/app.key ]; then
    APP_KEY=$(cat /app/storage/app.key)
    export APP_KEY
fi
if [ -z "$APP_KEY" ]; then
    APP_KEY="base64:$(openssl rand -base64 32)"
    export APP_KEY
    echo "$APP_KEY" > /app/storage/app.key
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache

# Tunggu database siap, lalu migrasi + seed (idempotent: updateOrCreate).
i=0
until php artisan migrate --force; do
    i=$((i + 1))
    if [ "$i" -ge 30 ]; then
        echo "Migrasi gagal setelah 30 percobaan, berhenti."
        exit 1
    fi
    echo "Database belum siap, mencoba lagi ($i/30)..."
    sleep 2
done
php artisan db:seed --force || true
php artisan config:clear
php artisan route:clear

exec frankenphp run --config /etc/caddy/Caddyfile
