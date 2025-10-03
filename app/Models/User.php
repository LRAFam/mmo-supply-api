<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_seller',
        'wallet_balance',
        'avatar',
        'bio',
        'stripe_customer_id',
        'stripe_account_id',
        'stripe_onboarding_complete',
        'seller_earnings_percentage',
        'seller_tier',
        'monthly_sales',
        'lifetime_sales',
        'monthly_sales_reset_at',
        'auto_tier',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_seller' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function sellerSubscription(): HasOne
    {
        return $this->hasOne(SellerSubscription::class);
    }

    public function featuredListings(): HasMany
    {
        return $this->hasMany(FeaturedListing::class);
    }

    /**
     * Achievements relationship
     */
    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('unlocked_at', 'is_notified', 'progress_data')
            ->withTimestamps();
    }

    /**
     * Events relationship
     */
    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_participants')
            ->withPivot('joined_at', 'score', 'rank', 'status', 'prize_data', 'prize_claimed')
            ->withTimestamps();
    }

    /**
     * Event participants
     */
    public function eventParticipations(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    /**
     * Sent messages
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Reviews given by this user
     */
    public function givenReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    /**
     * Reviews received by this user (as a seller)
     */
    public function receivedReviews()
    {
        return Review::whereHas('orderItem.product', function ($query) {
            $query->where('user_id', $this->id);
        });
    }

    /**
     * Orders where this user is the seller
     */
    public function sellerOrders()
    {
        return Order::whereHas('items', function ($query) {
            $query->whereHas('product', function ($q) {
                $q->where('user_id', $this->id);
            });
        });
    }

    // Helper to get or create wallet
    public function getOrCreateWallet(): Wallet
    {
        return $this->wallet ?? $this->wallet()->create([
            'balance' => 0,
            'pending_balance' => 0,
            'currency' => 'USD',
            'is_active' => true,
        ]);
    }

    /**
     * Get the active seller subscription or default to basic tier
     */
    public function getActiveSubscription(): SellerSubscription
    {
        $subscription = $this->sellerSubscription()
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();

        // If no active subscription, return a basic tier default
        if (!$subscription) {
            return new SellerSubscription([
                'tier' => 'basic',
                'fee_percentage' => 30.00, // Platform gets 30%, creator gets 70%
                'monthly_price' => 0,
            ]);
        }

        return $subscription;
    }


    /**
     * Get the platform fee percentage based on seller subscription or custom rate
     */
    public function getPlatformFeePercentage(): float
    {
        return 100 - $this->getSellerEarningsPercentage();
    }

    /**
     * Set custom seller earnings percentage (for special partnerships, etc)
     */
    public function setSellerEarnings(float $percentage, string $tier = 'standard'): void
    {
        $this->update([
            'seller_earnings_percentage' => $percentage,
            'seller_tier' => $tier,
        ]);
    }

    /**
     * Add a sale to the user's monthly and lifetime sales
     */
    public function addSale(float $amount): void
    {
        $this->increment('monthly_sales', $amount);
        $this->increment('lifetime_sales', $amount);

        // Check if tier needs to be updated
        $this->checkAndUpdateTier();
    }

    /**
     * Check and update tier based on sales volume
     */
    public function checkAndUpdateTier(): void
    {
        $monthlySales = floatval($this->monthly_sales);
        $lifetimeSales = floatval($this->lifetime_sales);

        $newTier = 'standard';

        // Premium: $5,000/month OR $25,000 lifetime
        if ($monthlySales >= 5000 || $lifetimeSales >= 25000) {
            $newTier = 'premium';
        }
        // Verified: $1,000/month OR $5,000 lifetime
        elseif ($monthlySales >= 1000 || $lifetimeSales >= 5000) {
            $newTier = 'verified';
        }

        // Update if changed
        if ($this->auto_tier !== $newTier) {
            $this->update([
                'auto_tier' => $newTier,
                'seller_tier' => $newTier,
            ]);
        }
    }

    /**
     * Get the seller earnings based on auto tier (volume-based)
     */
    public function getSellerEarningsPercentage(): float
    {
        // Check if user has custom earnings percentage set (admin override)
        if ($this->seller_earnings_percentage) {
            return floatval($this->seller_earnings_percentage);
        }

        // Use auto tier based on sales volume
        return match($this->auto_tier ?? 'standard') {
            'premium' => 90.0,   // $5k+/month or $25k+ lifetime
            'verified' => 80.0,  // $1k+/month or $5k+ lifetime
            default => 70.0,     // Standard
        };
    }

    /**
     * Reset monthly sales (should be run by scheduler on 1st of each month)
     */
    public function resetMonthlySales(): void
    {
        $this->update([
            'monthly_sales' => 0,
            'monthly_sales_reset_at' => now(),
        ]);

        // Re-check tier after reset
        $this->checkAndUpdateTier();
    }

    /**
     * Get tier progress information
     */
    public function getTierProgress(): array
    {
        $monthly = floatval($this->monthly_sales);
        $lifetime = floatval($this->lifetime_sales);
        $currentTier = $this->auto_tier ?? 'standard';

        $progress = [
            'current_tier' => $currentTier,
            'monthly_sales' => $monthly,
            'lifetime_sales' => $lifetime,
            'earnings_percentage' => $this->getCreatorEarningsPercentage(),
        ];

        // Calculate progress to next tier
        if ($currentTier === 'standard') {
            $progress['next_tier'] = 'verified';
            $progress['monthly_needed'] = max(0, 1000 - $monthly);
            $progress['lifetime_needed'] = max(0, 5000 - $lifetime);
            $progress['monthly_progress'] = min(100, ($monthly / 1000) * 100);
            $progress['lifetime_progress'] = min(100, ($lifetime / 5000) * 100);
        } elseif ($currentTier === 'verified') {
            $progress['next_tier'] = 'premium';
            $progress['monthly_needed'] = max(0, 5000 - $monthly);
            $progress['lifetime_needed'] = max(0, 25000 - $lifetime);
            $progress['monthly_progress'] = min(100, ($monthly / 5000) * 100);
            $progress['lifetime_progress'] = min(100, ($lifetime / 25000) * 100);
        } else {
            $progress['next_tier'] = null;
            $progress['monthly_needed'] = 0;
            $progress['lifetime_needed'] = 0;
            $progress['monthly_progress'] = 100;
            $progress['lifetime_progress'] = 100;
        }

        return $progress;
    }

    /**
     * Get current Cashier subscription tier
     */
    public function getSubscriptionTier(): string
    {
        if (!$this->subscribed('default')) {
            return 'free';
        }

        $subscription = $this->subscription('default');
        if (!$subscription) {
            return 'free';
        }

        $priceId = $subscription->stripe_price;

        return match($priceId) {
            config('services.stripe.premium_price_id') => 'premium',
            config('services.stripe.elite_price_id') => 'elite',
            default => 'free'
        };
    }

    /**
     * Check if user has active Cashier subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscribed('default');
    }

    /**
     * Get subscription perks (uses SubscriptionService)
     */
    public function getSubscriptionPerks(): array
    {
        $tier = $this->getSubscriptionTier();
        return \App\Services\SubscriptionService::getPerksForTier($tier, $this->is_seller);
    }
}
