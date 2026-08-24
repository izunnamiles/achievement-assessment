#!/bin/bash
set -e

# docker-compose no longer sets APP_ENV as a container env var (see
# docker-compose.yml) - .env is only read by Laravel itself now, so pull
# APP_ENV from that file directly for this shell-level check.
APP_ENV=$(grep -m1 '^APP_ENV=' .env | cut -d '=' -f2-)

# Caching config/routes bakes in whatever .env this container started with,
# which then silently overrides phpunit.xml's test-env values (and hides new
# routes) on any later local run against the same bind-mounted volume - only
# do it for real deploys.
if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
fi

php artisan migrate --seed --force

# Run php-fpm in the background; nginx (the CMD) becomes the container's foreground process.
php-fpm -D

exec "$@"
