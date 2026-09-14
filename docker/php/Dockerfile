FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        bash curl git icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install bcmath intl mbstring pcntl pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]
