FROM mcr.microsoft.com/devcontainers/php:1-8.4-bookworm

USER root

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libonig-dev libsqlite3-dev libzip-dev \
    && docker-php-ext-install bcmath intl mbstring pcntl pdo_mysql pdo_sqlite zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
