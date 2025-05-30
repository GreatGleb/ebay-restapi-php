# Используем официальный образ PHP с PHP-FPM
FROM php:8.2-fpm

# Устанавливаем необходимые расширения PHP для работы с PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql ftp zip \
    && rm -rf /var/lib/apt/lists/*

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Копируем исходный код в контейнер
COPY . /var/www/laravel

# Указываем рабочую директорию
WORKDIR /var/www/laravel

# Устанавливаем зависимости Composer
RUN composer install --optimize-autoloader --no-dev

# Настраиваем права на папки
RUN chown -R www-data:www-data /var/www/laravel/storage /var/www/laravel/bootstrap/cache

# Открываем порт 9000 (для PHP-FPM)
EXPOSE 9000
