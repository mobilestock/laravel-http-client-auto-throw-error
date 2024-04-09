FROM php:8.3.4-cli-alpine
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apk update
RUN apk add --no-cache git
COPY . /app
WORKDIR /app
RUN composer install --no-dev --optimize-autoloader
