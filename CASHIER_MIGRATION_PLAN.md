# Migration Plan: Stripe Cashier with Universal Subscriptions

## Overview
Transitioning from custom `seller_subscriptions` table to Laravel Cashier with Stripe's native subscription management. **Expanding subscriptions to ALL users (buyers + sellers)** with role-specific perks.

---

## Why Use Cashier?

### Benefits
1. **Native Stripe Integration**: Uses Stripe's subscription tables and webhooks
2. **Automatic Billing**: Handles recurring billing, prorations, grace periods
3. **Webhook Management**: Built-in webhook handlers for all subscription events
4. **Multiple Subscriptions**: Users can have different subscription types
5. **Tax & Metering**: Built-in support for tax calculations and usage-based billing
6. **Trial Periods**: Easy implementation of free trials
7. **Payment Methods**: Manages multiple payment methods per customer

### What Cashier Provides
- `subscriptions` table (Stripe manages the actual subscription)
- `subscription_items` table (for metered billing)
- Billable trait for User model
- Automatic webhook handling
- Payment method management
- Invoice generation

---

## Universal Subscription Tiers

### Free Tier ($0/month) - Everyone
**Buyer Perks:**
- Standard marketplace access
- Basic search & filters
- Cart & wishlist
- Free daily spin wheel (24h cooldown)
- Email support (48-72h)

**Seller Perks:**
- Unlimited basic listings
- Basic analytics
- Standard visibility
- Keep 70% of sales

---

### Premium Tier ($9.99/month) - Buyers & Sellers

**Buyer Perks:**
- ✓ **4 premium spin wheel spins per week** 🎰
- ✓ **Verified buyer badge** (trustworthy, faster deals)
- ✓ **Priority support** (24h response time)
- ✓ **Early access to deals** (see new listings first)
- ✓ **Advanced search filters** (rarity, server, level ranges)
- ✓ **Price drop alerts** (notify when wishlist items drop)
- ✓ **Bulk purchase discounts** (5% off orders over $50)
- ✓ **Exclusive buyer events** (flash sales, giveaways)
- ✓ **Extended return window** (14 days vs 7 days)
- ✓ **Purchase protection** (priority dispute resolution)

**Seller Perks:**
- ✓ All buyer perks above
- ✓ **Verified seller badge**
- ✓ **3 featured listing slots per month**
- ✓ **Priority placement in search**
- ✓ **Advanced analytics dashboard**
- ✓ **Custom storefront colors & branding**
- ✓ **Email marketing tools**
- ✓ **Keep 80% of sales** (+10% vs Free)

---

### Elite Tier ($29.99/month) - Power Users

**Buyer Perks:**
- ✓ **8 premium spin wheel spins per week** 🎰🎰
- ✓ **Elite buyer badge** (gold badge, highest trust)
- ✓ **1-hour priority support**
- ✓ **Dedicated account manager**
- ✓ **VIP access to new releases** (24h early access)
- ✓ **Premium search & analytics** (market trends, price history)
- ✓ **Automated price tracking** (API access for bots)
- ✓ **Bulk purchase discounts** (10% off orders over $50)
- ✓ **Exclusive elite events** (private auctions, beta access)
- ✓ **Extended return window** (30 days)
- ✓ **Premium purchase protection** (instant refunds)
- ✓ **White-glove service** (concierge shopping assistance)

**Seller Perks:**
- ✓ All buyer perks above
- ✓ **Elite seller badge**
- ✓ **Unlimited featured listings**
- ✓ **Top priority placement everywhere**
- ✓ **Premium analytics & AI insights**
- ✓ **Full custom storefront & white-label**
- ✓ **Advanced marketing suite**
- ✓ **API access for automation**
- ✓ **Bulk operations tools**
- ✓ **Early feature access**
- ✓ **Keep 90% of sales** (+20% vs Free)

---

## Database Schema Changes

### Current Schema (to be replaced)
```
seller_subscriptions:
  - user_id
  - tier
  - fee_percentage
  - monthly_price
  - started_at
  - expires_at
  - is_active
  - stripe_subscription_id
```

### New Cashier Schema
```
users:
  - stripe_id (Cashier adds)
  - pm_type (payment method type)
  - pm_last_four
  - trial_ends_at

subscriptions:
  - id
  - user_id
  - type (e.g. 'default', 'premium', 'elite')
  - stripe_id
  - stripe_status
  - stripe_price
  - quantity
  - trial_ends_at
  - ends_at
  - created_at
  - updated_at

subscription_items:
  - id
  - subscription_id
  - stripe_id
  - stripe_product
  - stripe_price
  - quantity
  - created_at
  - updated_at
```

### Custom Perks Table (New)
```
user_subscription_perks:
  - id
  - user_id
  - perk_type (e.g. 'premium_spins', 'featured_listings')
  - allocation (e.g. 4, 3, -1 for unlimited)
  - used (current usage)
  - resets_at (for weekly/monthly perks)
  - created_at
  - updated_at
```

---

## Migration Steps

### 1. Update User Model
```php
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;

    /**
     * Get the current active subscription
     */
    public function currentSubscription()
    {
        return $this->subscription('default');
    }

    /**
     * Check if user has any active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscribed('default');
    }

    /**
     * Get subscription tier
     */
    public function getSubscriptionTier(): string
    {
        if (!$this->hasActiveSubscription()) {
            return 'free';
        }

        $subscription = $this->currentSubscription();
        // Determine tier from Stripe price ID
        return match($subscription->stripe_price) {
            env('STRIPE_PREMIUM_PRICE_ID') => 'premium',
            env('STRIPE_ELITE_PRICE_ID') => 'elite',
            default => 'free'
        };
    }

    /**
     * Get all perks for current subscription
     */
    public function getSubscriptionPerks(): array
    {
        $tier = $this->getSubscriptionTier();
        $isSeller = $this->is_seller;

        return SubscriptionService::getPerksForTier($tier, $isSeller);
    }
}
```

### 2. Create Stripe Products
```bash
# Run once in production
stripe products create --name="MMO Supply Premium" --description="Premium membership for buyers and sellers"
stripe prices create --product=prod_xxx --unit-amount=999 --currency=usd --recurring[interval]=month

stripe products create --name="MMO Supply Elite" --description="Elite membership for power users"
stripe prices create --product=prod_xxx --unit-amount=2999 --currency=usd --recurring[interval]=month
```

### 3. Environment Variables
```env
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

STRIPE_PREMIUM_PRICE_ID=price_xxx
STRIPE_ELITE_PRICE_ID=price_xxx
```

### 4. Create Subscription Service
```php
class SubscriptionService
{
    public static function getPerksForTier(string $tier, bool $isSeller): array
    {
        $perks = [
            'free' => [
                'buyer' => [
                    'premium_spins_per_week' => 0,
                    'verified_badge' => false,
                    'priority_support' => false,
                    'support_response_time' => '48-72h',
                    'early_deal_access' => false,
                    'advanced_filters' => false,
                    'price_alerts' => false,
                    'bulk_discount' => 0,
                    'return_window_days' => 7,
                ],
                'seller' => [
                    'featured_listings' => 0,
                    'priority_placement' => false,
                    'analytics_tier' => 'basic',
                    'custom_storefront' => false,
                    'email_marketing' => false,
                    'earnings_percentage' => 70.0,
                ],
            ],
            'premium' => [
                'buyer' => [
                    'premium_spins_per_week' => 4,
                    'verified_badge' => true,
                    'priority_support' => true,
                    'support_response_time' => '24h',
                    'early_deal_access' => true,
                    'advanced_filters' => true,
                    'price_alerts' => true,
                    'bulk_discount' => 5,
                    'return_window_days' => 14,
                    'exclusive_events' => true,
                ],
                'seller' => [
                    'featured_listings' => 3,
                    'priority_placement' => true,
                    'analytics_tier' => 'advanced',
                    'custom_storefront' => true,
                    'email_marketing' => true,
                    'earnings_percentage' => 80.0,
                ],
            ],
            'elite' => [
                'buyer' => [
                    'premium_spins_per_week' => 8,
                    'verified_badge' => true,
                    'elite_badge' => true,
                    'priority_support' => true,
                    'support_response_time' => '1h',
                    'account_manager' => true,
                    'early_deal_access' => true,
                    'vip_new_releases' => true,
                    'advanced_filters' => true,
                    'premium_analytics' => true,
                    'price_alerts' => true,
                    'api_access' => true,
                    'bulk_discount' => 10,
                    'return_window_days' => 30,
                    'exclusive_events' => true,
                    'concierge_service' => true,
                ],
                'seller' => [
                    'featured_listings' => -1, // unlimited
                    'priority_placement' => true,
                    'top_priority' => true,
                    'analytics_tier' => 'premium',
                    'custom_storefront' => true,
                    'white_label' => true,
                    'email_marketing' => true,
                    'api_access' => true,
                    'bulk_operations' => true,
                    'early_features' => true,
                    'earnings_percentage' => 90.0,
                ],
            ],
        ];

        $rolePerk = $isSeller ? 'seller' : 'buyer';
        return array_merge($perks[$tier]['buyer'], $perks[$tier][$rolePerk] ?? []);
    }
}
```

### 5. Update Controller
```php
class SubscriptionController extends Controller
{
    public function checkout(Request $request, string $tier)
    {
        $user = $request->user();

        $priceId = match($tier) {
            'premium' => config('services.stripe.premium_price_id'),
            'elite' => config('services.stripe.elite_price_id'),
            default => throw new \InvalidArgumentException('Invalid tier')
        };

        return $user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success'),
                'cancel_url' => route('subscription.cancel'),
            ]);
    }

    public function cancel(Request $request)
    {
        $user = $request->user();
        $user->subscription('default')->cancel();

        return response()->json(['message' => 'Subscription cancelled']);
    }
}
```

---

## Frontend Changes

### Update Subscription Page
- Show buyer AND seller perks in two columns
- Make it clear: "Everyone benefits!"
- Highlight buyer perks like premium spins, badges, discounts
- Use visual separation for buyer vs seller perks

### New Buyer-Focused Pages
- `/subscriptions` or `/premium` - Main subscription page
- Show value propositions for buyers
- Emphasize gaming features (spins, badges)
- Show savings calculator for bulk buyers

---

## Implementation Timeline

1. ✅ Install Cashier
2. ✅ Publish migrations
3. ⏳ Run migrations (`php artisan migrate`)
4. ⏳ Create Stripe products & prices
5. ⏳ Add Billable trait to User model
6. ⏳ Create SubscriptionService
7. ⏳ Update controllers to use Cashier
8. ⏳ Migrate existing subscriptions
9. ⏳ Update frontend UI
10. ⏳ Test webhook handling
11. ⏳ Deploy & monitor

---

## Benefits of Universal Subscriptions

### For Platform
- 📈 **2x subscription potential** (buyers + sellers vs sellers only)
- 💰 **Recurring revenue from all users**
- 🎯 **Better engagement** (gaming features for buyers)
- 🔄 **Cross-role upgrades** (buyers become sellers, already subscribed)

### For Users
- 🎁 **More value** (perks for everyone)
- 🎮 **Gaming features** (spins, badges, events)
- 💎 **Status symbols** (verified badges)
- 🛡️ **Protection** (better support, returns, disputes)

---

**Last Updated:** October 2025
