FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
        default-mysql-client \
        libonig-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install pdo_mysql

WORKDIR /var/www/html
