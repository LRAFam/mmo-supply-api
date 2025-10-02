#!/bin/bash

# Exit on any error
set -e

echo "🚀 Starting deployment..."

# Create storage directories if they don't exist
echo "📁 Setting up storage directories..."
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Install/update composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Clear and cache config
echo "⚙️ Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Cache everything for production
echo "💾 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers if using queues
echo "🔄 Restarting queue workers..."
php artisan queue:restart

echo "✅ Deployment complete!"
