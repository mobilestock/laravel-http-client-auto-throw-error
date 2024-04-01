FROM composer:2.0 as composer
COPY . /app
WORKDIR /app
RUN composer install --no-dev --optimize-autoloader
