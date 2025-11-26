FROM php:8.2-fpm-alpine

RUN apk add --no-cache mysql-client \
    && docker-php-ext-install pdo_mysql

WORKDIR /var/www/html
COPY . .

# Права на запись для метрик
RUN chown -R www-data:www-data /tmp

EXPOSE 9000
CMD ["php-fpm"]