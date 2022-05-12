#!/bin/sh

echo "Waiting for db..."

sleep 30

su www-data -c "php /app/artisan key:generate -n" -s /bin/sh
su www-data -c "php /app/artisan optimize" -s /bin/sh
su www-data -c "php /app/artisan event:cache" -s /bin/sh
su www-data -c "php /app/artisan migrate --force" -s /bin/sh
su www-data -c "php /app/artisan db:seed --class InitialSeeder --force" -s /bin/sh

echo "Started!"

supervisord -c /app/supervisor.ini -n
