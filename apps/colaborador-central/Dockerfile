FROM backend-shared:latest AS backend-shared
FROM php:8.3.4-cli-alpine
ARG COMPOSER_OPTS="--no-dev --optimize-autoloader"
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY config/php.ini /usr/local/etc/php

RUN apk update
RUN apk add \
    autoconf \
    git \
    libzip-dev \
    linux-headers \
    zlib-dev
RUN docker-php-ext-configure bcmath && docker-php-ext-install bcmath && docker-php-ext-enable bcmath
RUN docker-php-ext-configure sockets && docker-php-ext-install sockets && docker-php-ext-enable sockets
RUN docker-php-ext-configure pdo && docker-php-ext-install pdo && docker-php-ext-enable pdo
RUN docker-php-ext-configure pdo_mysql && docker-php-ext-install pdo_mysql && docker-php-ext-enable pdo_mysql
RUN docker-php-ext-configure opcache && docker-php-ext-install opcache && docker-php-ext-enable opcache
RUN docker-php-ext-configure zip && docker-php-ext-install zip && docker-php-ext-enable zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /colaborador-central
COPY . .
RUN chmod -R 777 storage/logs
COPY --from=backend-shared /app /var/lib/shared
RUN composer install $COMPOSER_OPTS

CMD php artisan app:monitor-alteracoes-colaborador
