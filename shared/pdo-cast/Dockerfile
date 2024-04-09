FROM php:8.3.4-cli
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . /app
WORKDIR /app
RUN composer install --no-dev --optimize-autoloader
