# Используем официальный образ PHP с PHP-FPM
FROM php:8.2-fpm

# Устанавливаем необходимые расширения PHP для работы с PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Копируем исходный код в контейнер
COPY . /var/www/docker-laravel-postgresql-supabase

# Указываем рабочую директорию
WORKDIR /var/www/docker-laravel-postgresql-supabase

# Устанавливаем зависимости Composer
RUN composer install --optimize-autoloader --no-dev

# Настраиваем права на папки
RUN chown -R www-data:www-data /var/www/docker-laravel-postgresql-supabase/storage /var/www/docker-laravel-postgresql-supabase/bootstrap/cache

# Открываем порт 9000 (для PHP-FPM)
EXPOSE 9000
