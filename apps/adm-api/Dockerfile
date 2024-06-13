FROM backend-shared:latest as backend-shared

FROM php:7.4.18-apache-buster
ARG COMPOSER_OPTS="--no-dev --optimize-autoloader"
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV TZ=America/Sao_Paulo
RUN ln -fs /usr/share/zoneinfo/$TZ /etc/localtime && dpkg-reconfigure -f noninteractive tzdata

COPY config/php.ini /usr/local/etc/php/php.ini
COPY config/sites-available.conf /etc/apache2/sites-available/000-default.conf
RUN echo "ServerName app" >> /etc/apache2/apache2.conf

RUN apt-get update
RUN docker-php-ext-configure pdo && docker-php-ext-install pdo && docker-php-ext-enable pdo
RUN docker-php-ext-configure pdo_mysql && docker-php-ext-install pdo_mysql && docker-php-ext-enable pdo_mysql
RUN docker-php-ext-configure opcache && docker-php-ext-install opcache && docker-php-ext-enable opcache
RUN apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install gd \
    && docker-php-ext-enable gd
RUN apt-get install -y libzip-dev zlib1g-dev && docker-php-ext-configure zip && docker-php-ext-install zip && docker-php-ext-enable zip
RUN pecl install redis && docker-php-ext-enable redis
RUN apt-get install -y locales
RUN echo "pt_BR.UTF-8 UTF-8" > /etc/locale.gen
RUN locale-gen
RUN a2enmod rewrite
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

COPY . /var/www/html/app

RUN chown -R www-data:www-data /var/www/html/app/log
RUN chmod -R 777 /var/www/html/app/log
RUN chmod -R 777 /var/www/html/app/bootstrap/cache
RUN chmod -R 777 /var/www/html/app/downloads
WORKDIR /var/www/html/app

COPY --from=backend-shared /app /var/lib/shared
RUN composer install $COMPOSER_OPTS

CMD apache2-foreground
EXPOSE 80
