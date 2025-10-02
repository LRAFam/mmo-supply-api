# MMO Supply - API

Backend API for MMO Supply marketplace built with Laravel 11.

## 🚀 Tech Stack

- **Framework:** Laravel 11
- **Database:** MySQL 8.0
- **Storage:** AWS S3 / Local
- **Cache:** Redis
- **Payments:** Stripe (Laravel Cashier)
- **Email:** SMTP / Mailgun
- **Authentication:** Laravel Sanctum

## 📦 Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

## 🔧 Environment Variables

Configure your `.env` file with:

```env
APP_NAME="MMO Supply"
APP_URL=https://your-domain.com
FRONTEND_URL=https://your-frontend-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mmo_supply
DB_USERNAME=root
DB_PASSWORD=

# Stripe
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret
STRIPE_WEBHOOK_SECRET=your_webhook_secret

# AWS S3 (optional)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000,your-frontend-domain.com
SESSION_DOMAIN=.your-domain.com
```

## 🗄️ Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### Seed Data Includes:
- Test users (buyer, sellers)
- 20+ popular games
- Sample items, currencies, accounts, services
- Achievements system
- Spin wheel prizes
- Leaderboard rewards

## 🏃 Development

```bash
php artisan serve
```

API will be available at `http://localhost:8000`

## 🔐 Key Features

- **Authentication:** Laravel Sanctum (SPA + API tokens)
- **Orders System:** Full order lifecycle with status tracking
- **Wallet System:** Internal credits, deposits, withdrawals
- **Payment Processing:** Stripe integration with webhooks
- **Subscriptions:** Premium membership tiers
- **Provider Tiers:** Automatic seller tier upgrades based on volume
- **Achievements:** Progressive achievement system with rewards
- **Spin Wheel:** Free daily spins + premium member spins
- **Leaderboard:** Monthly top seller rewards
- **Messaging:** Buyer-seller direct messaging
- **Reviews:** Product review system
- **Events:** Seasonal events and competitions

## 📁 Project Structure

```
├── app/
│   ├── Http/Controllers/  # API Controllers
│   ├── Models/            # Eloquent Models
│   ├── Services/          # Business Logic
│   └── Mail/              # Email Templates
├── database/
│   ├── migrations/        # Database Migrations
│   └── seeders/           # Database Seeders
├── routes/
│   └── api.php            # API Routes
└── storage/               # File Storage
```

## 🚀 Deployment (Laravel Forge)

1. **Create Server:** DigitalOcean droplet (2GB+ recommended)
2. **Install:** PHP 8.2, MySQL 8.0, Redis, Nginx
3. **Deploy Site:** Connect to GitHub repo
4. **Environment:** Copy production `.env` variables
5. **Deploy Script:**
```bash
cd /home/forge/your-domain.com
git pull origin $FORGE_BRANCH
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
php artisan queue:restart
```

6. **Queue Worker:** Enable Laravel queue worker in Forge
7. **Scheduler:** Enable Laravel scheduler in Forge
8. **SSL:** Enable Let's Encrypt SSL

## 🔗 Related Repositories

- **Frontend:** [mmo-supply-frontend](https://github.com/LRAFam/mmo-supply-frontend)

## 📝 License

All rights reserved.
