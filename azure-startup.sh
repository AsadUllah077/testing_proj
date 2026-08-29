# #!/bin/bash
# php artisan optimize:clear

# # 1. Update Nginx configuration (Required for Laravel /public folder)
# echo "Configuring Nginx..."
# cp /home/site/wwwroot/default /etc/nginx/sites-available/default
# service nginx reload

# echo "Link the storage account"
# php /home/site/wwwroot/artisan storage:link --force

# # 2. AUTOMATE MIGRATIONS
# # This runs every time you push and the app restarts
# echo "Running migrations..."
# php /home/site/wwwroot/artisan migrate --force
# # php /home/site/wwwroot/artisan db:seed --force

# # 3. SETUP CRON JOB (Laravel Scheduler)
# # This loop runs the scheduler every 60 seconds in the background
# echo "Starting Laravel Scheduler..."
# while [ true ]
# do
#   php /home/site/wwwroot/artisan queue:work &
#   php /home/site/wwwroot/artisan schedule:run &
#   sleep 60
# done &

# echo "Startup process finished."


#!/bin/bash

# echo "client_max_body_size 100M;" > /etc/nginx/conf.d/client_max_body_size.conf

# service nginx reload

# php-fpm

# Clear Laravel caches
php /home/site/wwwroot/artisan optimize:clear
echo "Configuring Nginx..."
cp /home/site/wwwroot/default /etc/nginx/sites-available/default
service nginx reload

# ls -l public/storage
# rm -rf public/storage

echo "Linking storage account..."
php /home/site/wwwroot/artisan storage:link --force

# php artisan optimize:clear

# ls -l storage/app/public/exports

# 3. Run migrations
echo "Running migrations..."
php /home/site/wwwroot/artisan migrate --force

# 4. Populate Employee IDs
# echo "Populating missing employee IDs..."
# php /home/site/wwwroot/artisan employee:populate-ids

php /home/site/wwwroot/artisan migrate --force
php /home/site/wwwroot/artisan db:seed --force

# 4. Start Queue Worker (single long-running process)
echo "Starting Laravel Queue Worker..."
php /home/site/wwwroot/artisan queue:work --daemon --tries=3 --timeout=60 &
# nohup php /home/site/wwwroot/artisan queue:work --tries=3 --timeout=60 > /dev/null 2>&1 &

# 5. Setup Laravel Scheduler (proper cron approach)
echo "Setting up Laravel Scheduler..."
# (crontab -l 2>/dev/null; echo "* * * * * cd /home/site/wwwroot && php artisan schedule:run >> /dev/null 2>&1") | crontab -
# service cron start

echo "Startup process finished."
