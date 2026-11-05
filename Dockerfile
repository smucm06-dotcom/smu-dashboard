FROM composer:2 AS composer_bin
FROM php:8.4-fpm-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev libxml2-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql mysqli zip intl gd bcmath mbstring opcache \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer_bin /usr/bin/composer /usr/local/bin/composer
COPY ./docker/php/local.ini /usr/local/etc/php/conf.d/99-local.ini
WORKDIR /var/www/html
CMD ["php-fpm"]
