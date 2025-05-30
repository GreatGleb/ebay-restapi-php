FROM php:7.3-fpm

RUN apt-get update && apt-get install -y git unzip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www/tecdoc

WORKDIR /var/www/tecdoc

RUN composer install --optimize-autoloader --no-dev

EXPOSE 9000