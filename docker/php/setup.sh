#!/bin/bash
set -e

# docker-compose no longer sets APP_ENV as a container env var (see
# docker-compose.yml) - .env is only read by Laravel itself now, so pull
# APP_ENV from that file directly for this shell-level check.
APP_ENV=$(grep -m1 '^APP_ENV=' .env | cut -d '=' -f2-)

if [ "$APP_ENV" = "production" ]; then
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
else
    # Keeps dev dependencies (Pest, etc.) available inside the container too,
    # so the host never needs its own PHP/Composer to run `composer install` -
    # this writes back to the bind-mounted host directory just like any other
    # change made from inside the container.
    composer install --no-interaction --prefer-dist
fi

# Generate APP_KEY / JWT_SECRET on first boot if a fresh .env (copied from
# .env.example) doesn't have them yet, so Docker setup needs no manual
# `artisan key:generate` / `artisan jwt:secret` step. Both write back to the
# bind-mounted .env, so this only ever runs once per environment - an
# already-set production key is never touched.
if [ -z "$(grep -m1 '^APP_KEY=' .env | cut -d '=' -f2-)" ]; then
    php artisan key:generate --force
fi

if [ -z "$(grep -m1 '^JWT_SECRET=' .env | cut -d '=' -f2-)" ]; then
    php artisan jwt:secret --force
fi

# Caching config/routes bakes in whatever .env this container started with,
# which then silently overrides phpunit.xml's test-env values (and hides new
# routes) on any later local run against the same bind-mounted volume - only
# do it for real deploys.
if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
fi

php artisan migrate --seed --force
