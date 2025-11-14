# PHP Ebay API integration

[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php)](https://php.net)
[![Docs](https://img.shields.io/badge/Docs-Online-blue)](https://yourusername.github.io/php-refactoring/docs/)

description

## **Installation**
```bash
git clone https://github.com/GreatGleb/ebay-restapi-php.git
cd ebay-restapi-php
docker compose up -d
docker compose exec app composer install --prefer-source
docker compose exec app php artisan migrate

docker-compose exec app bash
> chown -R www-data:www-data /var/www/laravel/storage; \
> chown -R www-data:www-data /var/www/laravel/bootstrap/cache; \
> chmod -R 775 /var/www/laravel/storage; \
> chmod -R 775 /var/www/laravel/bootstrap/cache;

add laravel/app/Http/Controllers/API/tokens.json
add python/google_sheets/tokens/service-account.json

docker compose exec tecdoc composer install --prefer-source

docker compose down; docker compose up -d;
```
