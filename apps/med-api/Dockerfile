FROM backend-shared:latest AS backend-shared
FROM php:8.3.11-fpm
ARG COMPOSER_OPTS="--no-dev --optimize-autoloader"
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY config/php.ini /usr/local/etc/php

RUN apt-get update
RUN docker-php-ext-configure pdo && docker-php-ext-install pdo && docker-php-ext-enable pdo
RUN docker-php-ext-configure pdo_mysql && docker-php-ext-install pdo_mysql && docker-php-ext-enable pdo_mysql
RUN docker-php-ext-configure opcache && docker-php-ext-install opcache && docker-php-ext-enable opcache
RUN apt-get install -y libzip-dev zlib1g-dev && docker-php-ext-configure zip && docker-php-ext-install zip && docker-php-ext-enable zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /med-api
COPY . .
RUN chmod -R 777 storage/logs
COPY --from=backend-shared /app /var/lib/shared
RUN composer install $COMPOSER_OPTS \
    && php artisan route:cache

CMD ["php-fpm"]
