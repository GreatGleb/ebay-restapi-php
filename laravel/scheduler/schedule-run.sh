echo "Starting Laravel schedule worker..."
while true
do
    php /var/www/laravel/artisan schedule:run >> /dev/null 2>&1
    sleep 30
done
