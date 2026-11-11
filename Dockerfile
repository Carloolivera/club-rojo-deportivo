# Club Rojo Deportivo — PHP 8.3 + Nginx (serversideup/php), runs as www-data on port 8080.
FROM serversideup/php:8.3-fpm-nginx AS base
USER root
# intl: number/date formatting · gd: PDF and Excel exports
RUN install-php-extensions intl gd
USER www-data
WORKDIR /var/www/html

# Dependencies in their own layer so code changes don't reinstall them.
FROM base AS vendor
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

FROM base
COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data . .
RUN composer dump-autoload --no-dev --optimize \
 && php artisan package:discover --ansi
