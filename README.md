# PHP Ebay API integration

[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php)](https://php.net)
[![Docs](https://img.shields.io/badge/Docs-Online-blue)](https://yourusername.github.io/php-refactoring/docs/)

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

## Initial Setup

Before using the application, you must initialize the database (perform data seeding).

### 1. Brand Seeding
Execute the API request to import brands:
* **Endpoint:** `http://localhost/api/update/brands`
* **UI Path:** On the home page, navigate to `Producer brands` -> `Update DB producer brands`.

### 2. Category Seeding
Load the latest categories from Google Sheets:
* **Endpoint:** `http://localhost/python/categories/save_to_db_from_google_sheets`
* **UI Path:** Go to the `Categories` section -> `Update DB categories from Google Sheets`.

## Access Points

You can access the main application and the database interface using the following links:

* **Main Application:** [http://localhost/](http://localhost/)
* **Database Editor (Schema: public):** [http://localhost:3000/project/default/editor/17177?schema=public](http://localhost:3000/project/default/editor/17177?schema=public)