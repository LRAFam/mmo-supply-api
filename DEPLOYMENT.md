# Production Deployment Guide

## Quick Fix for Current S3 Upload Issue

The Filament admin panel image uploads are not working because the production server has cached configuration. Run these commands on the production server:

```bash
php artisan config:clear
php artisan config:cache
```

Or simply trigger a deployment in Laravel Forge, which will now automatically clear and recache the configuration.

## Manual Deployment Steps

If you need to manually deploy without Forge:

1. SSH into the production server
2. Navigate to the application directory
3. Run the following commands:

```bash
# Pull latest code
git pull origin master

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear and cache configuration
php artisan config:clear
php artisan config:cache

# Clear views
php artisan view:clear

# Run migrations
php artisan migrate --force

# Optimize application
php artisan optimize

# Restart queue workers
php artisan queue:restart
```

## Automated Deployment (Laravel Forge)

The `deploy.sh` script is configured for Laravel Forge zero-downtime deployments. It automatically:

- Creates necessary storage directories
- Installs Composer dependencies
- Discovers packages
- **Clears and caches configuration** (crucial for S3/Filament settings)
- Clears view cache
- Optimizes the application
- Creates storage symlinks
- Runs database migrations
- Restarts queue workers

Simply push to the `master` branch or manually trigger deployment in Forge.

## Important Notes

- The `FILAMENT_FILESYSTEM_DISK=s3` environment variable is already set in production
- The `config/filament.php` file now defaults to S3 for file uploads
- After any configuration changes, always run `php artisan config:cache` on production
- Cache clearing is now automated in the deployment script
