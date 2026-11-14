#!/bin/sh
set -e
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf
echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf
a2enconf servername > /dev/null
php artisan migrate --force
if [ -n "$ADMIN_PASSWORD" ]; then
    php artisan db:seed --force
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec apache2-foreground
