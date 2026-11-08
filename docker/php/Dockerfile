# Local development only. Cloudron runs the LAMP app, not this image.
FROM php:8.4-fpm

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_mysql intl bcmath zip pcntl opcache gd

COPY php.ini /usr/local/etc/php/conf.d/zz-apes-intranet.ini

WORKDIR /app
