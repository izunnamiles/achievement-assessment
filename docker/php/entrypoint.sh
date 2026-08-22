#!/bin/bash
set -e

php artisan config:cache
php artisan route:cache
php artisan migrate --force

# Run php-fpm in the background; nginx (the CMD) becomes the container's foreground process.
php-fpm -D

exec "$@"
