FROM php:8.2.6-apache-buster
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY config/php.ini /usr/local/etc/php
COPY config/sites-available.conf /etc/apache2/sites-available/000-default.conf
RUN echo "ServerName app" >> /etc/apache2/apache2.conf

RUN apt-get update
RUN docker-php-ext-configure pdo && docker-php-ext-install pdo && docker-php-ext-enable pdo
RUN docker-php-ext-configure pdo_mysql && docker-php-ext-install pdo_mysql && docker-php-ext-enable pdo_mysql
RUN docker-php-ext-configure opcache && docker-php-ext-install opcache && docker-php-ext-enable opcache
RUN apt-get install -y libzip-dev zlib1g-dev && docker-php-ext-configure zip && docker-php-ext-install zip && docker-php-ext-enable zip
RUN pecl install redis && docker-php-ext-enable redis
RUN a2enmod rewrite
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer
COPY . /var/www/html/app
RUN chmod -R 777 /var/www/html/app/storage/logs
WORKDIR /var/www/html/app
RUN composer install --no-dev --optimize-autoloader \
    && php artisan route:cache
CMD ["apache2-foreground"]
