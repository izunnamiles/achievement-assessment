# syntax=docker/dockerfile:1

##
## Stage 1: install PHP (Composer) dependencies
##
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-progress \
    --prefer-dist \
    --no-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

##
## Stage 2: runtime image (PHP-FPM)
##
FROM php:8.3-fpm-alpine AS app

# System packages + PHP extensions Laravel needs
RUN apk add --no-cache \
        bash \
        curl \
        nginx \
        libzip \
        libzip-dev \
        libpng \
        libpng-dev \
        libjpeg-turbo \
        libjpeg-turbo-dev \
        freetype \
        freetype-dev \
        icu-libs \
        icu-dev \
        oniguruma \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        gd \
        zip \
        intl \
        mbstring \
        bcmath \
        opcache \
    && apk del libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev icu-dev oniguruma-dev \
    # php-fpm only needs to be reachable by nginx in this same container
    && sed -i 's/^listen = 9000/listen = 127.0.0.1:9000/' /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

COPY --from=vendor /app ./

RUN addgroup -g 1000 www && adduser -G www -g www -s /bin/sh -D www \
    && chown -R www:www /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    # let the unprivileged www user own nginx's runtime/log dirs (it binds >1024 so root isn't needed)
    && mkdir -p /run/nginx \
    && chown -R www:www /run/nginx /var/lib/nginx /var/log/nginx /etc/nginx

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
