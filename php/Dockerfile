FROM php:8.2-fpm

RUN apt-get update && apt-get install -y git unzip libzip-dev     && docker-php-ext-install pdo pdo_mysql zip     && pecl install apcu && docker-php-ext-enable apcu     && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --chown=www-data:www-data ./ /var/www/html
