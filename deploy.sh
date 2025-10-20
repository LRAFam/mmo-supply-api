cd $FORGE_SITE_PATH

# Create storage directories in shared storage (only needs to run once, but safe to run multiple times)
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/app/public
mkdir -p storage/logs
chmod -R 775 storage

$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

# Create bootstrap/cache directory in this release
mkdir -p bootstrap/cache
chmod -R 775 bootstrap/cache

# Install composer dependencies with post-autoload scripts disabled initially
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Now run the post-autoload scripts after directories exist
$FORGE_COMPOSER dump-autoload --optimize

# Run artisan package discover manually
$FORGE_PHP artisan package:discover --ansi

# Clear and cache configuration (important for S3/Filament settings)
$FORGE_PHP artisan config:clear
$FORGE_PHP artisan config:cache

# Clear Filament cache and rebuild assets
$FORGE_PHP artisan filament:clear-cache
$FORGE_PHP artisan filament:assets

# Optimize application
$FORGE_PHP artisan optimize

# Create storage link
$FORGE_PHP artisan storage:link

# Run migrations
$FORGE_PHP artisan migrate --force

$ACTIVATE_RELEASE()

$RESTART_QUEUES()

# Restart Reverb WebSocket server via Supervisor
# Copy the Supervisor config if it doesn't exist
if [ ! -f /etc/supervisor/conf.d/reverb.conf ]; then
    echo "Installing Reverb Supervisor config..."
    sudo cp $FORGE_SITE_PATH/current/reverb.conf /etc/supervisor/conf.d/reverb.conf
    sudo supervisorctl reread
    sudo supervisorctl update
fi

# Restart Reverb
sudo supervisorctl restart reverb

echo "Reverb WebSocket server restarted via Supervisor"
