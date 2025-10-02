$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

# Create storage directories BEFORE composer install
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Install composer dependencies with post-autoload scripts disabled initially
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Now run the post-autoload scripts after directories exist
$FORGE_COMPOSER dump-autoload --optimize

# Run artisan package discover manually
$FORGE_PHP artisan package:discover --ansi

# Optimize application
$FORGE_PHP artisan optimize

# Create storage link
$FORGE_PHP artisan storage:link

# Run migrations
$FORGE_PHP artisan migrate --force

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
