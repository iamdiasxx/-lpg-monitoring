#!/bin/sh
set -e

mkdir -p /var/www/html/storage/framework/{sessions,views,cache,testing}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public/bukti_penerimaan
mkdir -p /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

php artisan config:cache
php artisan route:cache
(php artisan view:cache || true)

php artisan migrate --force

# Seed hanya jika admin default belum ada
if php -r "
require_once '/var/www/html/vendor/autoload.php';
\$app = require_once '/var/www/html/bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
exit(\App\Models\User::where('email', 'admin@gmail.com')->exists() ? 0 : 1);
" 2>/dev/null; then
    echo 'Default admin exists, skipping seed.'
else
    echo 'Default admin not found, seeding...'
    php artisan db:seed --force
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
