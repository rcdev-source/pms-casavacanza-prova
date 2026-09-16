FROM mcr.microsoft.com/devcontainers/php:1-8.4-bookworm

RUN install-php-extensions bcmath intl mbstring pcntl pdo_mysql pdo_sqlite zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
