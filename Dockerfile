# syntax=docker/dockerfile:1

# ---------------------------------------------------------------
# 1. PHP dependencies (no dev packages). Only vendor/ is kept.
# ---------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --ignore-platform-reqs

# ---------------------------------------------------------------
# 2. Front-end assets (Vite). Tailwind scans the pagination views
#    in vendor/, so those are copied in from stage 1.
# ---------------------------------------------------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
COPY --from=vendor /app/vendor/livewire/livewire/src/Features/SupportPagination/views ./vendor/livewire/livewire/src/Features/SupportPagination/views
RUN npm run build

# ---------------------------------------------------------------
# 3. The app: PHP 8.3 + Apache, running as www-data on port 8080.
# ---------------------------------------------------------------
FROM php:8.3-apache AS app

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql intl gd zip bcmath exif pcntl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: serve public/, listen on 8080 (no root needed), log to the container output
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers \
    && sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2

COPY docker/php/php.ini /usr/local/etc/php/conf.d/filmfolio.ini

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan storage:link \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public \
    && chown -R www-data:www-data storage bootstrap/cache public

COPY docker/entrypoint.sh /usr/local/bin/filmfolio-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/filmfolio-entrypoint && chmod +x /usr/local/bin/filmfolio-entrypoint

USER www-data
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS http://localhost:8080/up || exit 1

ENTRYPOINT ["filmfolio-entrypoint"]
CMD ["apache2-foreground"]
