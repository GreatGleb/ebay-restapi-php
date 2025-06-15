FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    supervisor \
    git unzip zip libzip-dev libpq-dev libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql ftp zip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www/laravel
COPY ./php-fpm-www.conf/www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/laravel

RUN composer install --optimize-autoloader --no-dev
RUN chown -R www-data:www-data /var/www/laravel/storage /var/www/laravel/bootstrap/cache

COPY supervisord.conf /etc/supervisord.conf

EXPOSE 9000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
