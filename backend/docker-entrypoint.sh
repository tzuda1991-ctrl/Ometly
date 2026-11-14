#!/bin/bash
set -e

mkdir -p /var/www/html/storage/app/public/uploads
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

php artisan storage:link || true

if [ -n "$GOOGLE_CREDENTIALS_BASE64" ]; then
  echo "$GOOGLE_CREDENTIALS_BASE64" | base64 -d > /var/www/html/googleCredentials.json
elif [ -n "$GOOGLE_CREDENTIALS_JSON" ]; then
  printf '%s' "$GOOGLE_CREDENTIALS_JSON" > /var/www/html/googleCredentials.json
fi

# Ensure only mpm_prefork is loaded (required by mod_php)
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
a2enmod mpm_prefork >/dev/null 2>&1 || true

PORT="${PORT:-80}"
sed -i -E "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i -E "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
