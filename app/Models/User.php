<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasApiTokens, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_seller',
        'wallet_balance',
        'bonus_balance',
        'avatar',
        'bio',
        'stripe_account_id',
        'stripe_onboarding_complete',
        'seller_earnings_percentage',
        'seller_tier',
        'monthly_sales',
        'lifetime_sales',
        'monthly_sales_reset_at',
        'auto_tier',
        'referral_code',
        'referred_by',
        'total_referral_earnings',
        'total_referrals',
        'last_login_ip',
        'signup_ip',
        'device_fingerprint',
        'can_withdraw',
        'withdrawal_eligible_at',
        'total_purchases',
        'achievement_points',
        'owned_cosmetics',
        'badge_inventory',
        'active_profile_theme',
        'active_title',
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
            'owned_cosmetics' => 'array',
            'badge_inventory' => 'array',
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
     * Season participations relationship
     */
    public function seasonParticipations()
    {
        return $this->hasMany(UserSeasonParticipation::class);
    }

    /**
     * Season passes relationship
     */
    public function seasonPasses()
    {
        return $this->hasMany(UserSeasonPass::class);
    }

    /**
     * Active perks relationship
     */
    public function activePerks()
    {
        return $this->hasMany(UserActivePerk::class);
    }

    /**
     * Store purchases relationship
     */
    public function storePurchases()
    {
        return $this->hasMany(UserStorePurchase::class);
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
     * Conversations where user is participant one
     */
    public function conversationsAsUserOne(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_one_id');
    }

    /**
     * Conversations where user is participant two
     */
    public function conversationsAsUserTwo(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_two_id');
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
            // Map auto_tier values to seller_tier values
            $sellerTierMapping = [
                'standard' => 'standard',
                'verified' => 'partner',
                'premium' => 'elite',
            ];

            $this->update([
                'auto_tier' => $newTier,
                'seller_tier' => $sellerTierMapping[$newTier],
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
            'premium' => 92.0,   // $5k+/month or $25k+ lifetime
            'verified' => 88.0,  // $1k+/month or $5k+ lifetime
            default => 80.0,     // Standard
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
            'earnings_percentage' => $this->getSellerEarningsPercentage(),
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
        // Get any active subscription
        $subscription = $this->subscriptions()
            ->where('stripe_status', 'active')
            ->first();

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

    /**
     * Determine if the user can access the Filament admin panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin' || $this->role === 'moderator';
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a moderator
     */
    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    /**
     * Get the user who referred this user
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Get all users referred by this user
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * Get all referral relationships where this user is the referrer
     */
    public function referralRelationships()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Get all referral earnings for this user
     */
    public function referralEarnings()
    {
        return $this->hasMany(ReferralEarning::class, 'referrer_id');
    }

    /**
     * Generate a unique referral code for this user
     */
    public function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5($this->id . time() . rand()), 0, 8));
        } while (User::where('referral_code', $code)->exists());

        $this->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * Get or generate referral code
     */
    public function getReferralCode(): string
    {
        if (!$this->referral_code) {
            return $this->generateReferralCode();
        }

        return $this->referral_code;
    }

    /**
     * Get total balance (wallet + bonus)
     */
    public function getTotalBalance(): float
    {
        return floatval($this->wallet_balance) + floatval($this->bonus_balance);
    }

    /**
     * Check if user can withdraw based on all security criteria
     */
    public function canWithdraw(): bool
    {
        return $this->can_withdraw
            && ($this->withdrawal_eligible_at === null || now()->gte($this->withdrawal_eligible_at))
            && $this->total_purchases > 0
            && $this->email_verified_at !== null;
    }

    /**
     * Get withdrawal eligibility status with details
     */
    public function getWithdrawalEligibility(): array
    {
        return [
            'can_withdraw' => $this->canWithdraw(),
            'checks' => [
                'email_verified' => $this->email_verified_at !== null,
                'withdrawals_enabled' => $this->can_withdraw,
                'cooldown_passed' => $this->withdrawal_eligible_at === null || now()->gte($this->withdrawal_eligible_at),
                'has_purchases' => $this->total_purchases > 0,
            ],
            'withdrawal_eligible_at' => $this->withdrawal_eligible_at,
            'total_purchases' => $this->total_purchases,
            'wallet_balance' => $this->wallet_balance,
            'bonus_balance' => $this->bonus_balance,
        ];
    }

    // ============================================================
    // Achievement Points & Store Methods
    // ============================================================

    /**
     * Add cosmetic item to user's inventory
     */
    public function addCosmetic(string $slug, string $category): bool
    {
        $cosmetics = $this->owned_cosmetics ?? [];

        // Check if already owned
        if (isset($cosmetics[$category]) && in_array($slug, $cosmetics[$category])) {
            return false;
        }

        // Add to inventory
        if (!isset($cosmetics[$category])) {
            $cosmetics[$category] = [];
        }
        $cosmetics[$category][] = $slug;

        $this->update(['owned_cosmetics' => $cosmetics]);
        return true;
    }

    /**
     * Check if user owns a cosmetic item
     */
    public function ownsCosmetic(string $slug): bool
    {
        $cosmetics = $this->owned_cosmetics ?? [];

        foreach ($cosmetics as $category => $items) {
            if (in_array($slug, $items)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add badge to user's inventory
     */
    public function addBadge(string $slug, string $icon, string $name): bool
    {
        $badges = $this->badge_inventory ?? [];

        // Check if already owned
        foreach ($badges as $badge) {
            if ($badge['slug'] === $slug) {
                return false;
            }
        }

        // Add badge
        $badges[] = [
            'slug' => $slug,
            'icon' => $icon,
            'name' => $name,
            'earned_at' => now()->toDateTimeString(),
        ];

        $this->update(['badge_inventory' => $badges]);
        return true;
    }

    /**
     * Get usage count for a store item
     */
    public function getItemUsageCount(string $itemSlug): int
    {
        $item = AchievementStoreItem::where('slug', $itemSlug)->first();
        if (!$item) {
            return 0;
        }

        return $this->storePurchases()
            ->where('store_item_id', $item->id)
            ->sum('times_used');
    }

    /**
     * Get last used date for a store item
     */
    public function getItemLastUsed(string $itemSlug)
    {
        $item = AchievementStoreItem::where('slug', $itemSlug)->first();
        if (!$item) {
            return null;
        }

        $purchase = $this->storePurchases()
            ->where('store_item_id', $item->id)
            ->whereNotNull('used_at')
            ->orderBy('used_at', 'desc')
            ->first();

        return $purchase ? $purchase->used_at : null;
    }

    /**
     * Apply marketplace perk to user
     */
    public function applyMarketplacePerk(string $slug, ?array $metadata): bool
    {
        $item = AchievementStoreItem::where('slug', $slug)->first();
        if (!$item) {
            return false;
        }

        // Determine perk type and expiration based on slug
        $perkType = $this->getPerkTypeFromSlug($slug);
        $expiresAt = $this->calculatePerkExpiration($slug, $metadata);

        // Create active perk
        $this->activePerks()->create([
            'store_item_id' => $item->id,
            'perk_type' => $perkType,
            'perk_data' => $metadata,
            'activated_at' => now(),
            'expires_at' => $expiresAt,
            'is_active' => true,
            'uses_remaining' => $item->max_uses,
        ]);

        return true;
    }

    /**
     * Apply functional item to user
     */
    public function applyFunctionalItem(string $slug, ?array $metadata): bool
    {
        $item = AchievementStoreItem::where('slug', $slug)->first();
        if (!$item) {
            return false;
        }

        // For functional items, just record the purchase
        // Actual functionality would be checked when needed
        return true;
    }

    /**
     * Get active perk of a specific type
     */
    public function getActivePerk(string $perkType)
    {
        return $this->activePerks()
            ->active()
            ->where('perk_type', $perkType)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Check if user has an active perk of a specific type
     */
    public function hasActivePerk(string $perkType): bool
    {
        return $this->activePerks()
            ->active()
            ->where('perk_type', $perkType)
            ->exists();
    }

    /**
     * Get perk type from item slug
     */
    private function getPerkTypeFromSlug(string $slug): string
    {
        if (str_contains($slug, 'listing_boost')) {
            return 'listing_boost';
        }
        if (str_contains($slug, 'featured_discount')) {
            return 'featured_discount';
        }
        if (str_contains($slug, 'commission_reduction')) {
            return 'commission_reduction';
        }
        return 'generic';
    }

    /**
     * Calculate perk expiration based on duration
     */
    private function calculatePerkExpiration(string $slug, ?array $metadata)
    {
        // Extract duration from slug or metadata
        if (str_contains($slug, '24h')) {
            return now()->addHours(24);
        }
        if (str_contains($slug, '72h')) {
            return now()->addHours(72);
        }
        if (str_contains($slug, 'week')) {
            return now()->addWeek();
        }
        if (str_contains($slug, 'day') && !str_contains($slug, 'days')) {
            return now()->addDay();
        }
        if (str_contains($slug, 'month')) {
            return now()->addMonth();
        }

        // Default to metadata or 7 days
        return $metadata['expires_at'] ?? now()->addDays(7);
    }

    /**
     * Get active season pass for user
     */
    public function getActiveSeasonPass($seasonId = null)
    {
        $query = $this->seasonPasses()->where('is_active', true);

        if ($seasonId) {
            $query->where('season_id', $seasonId);
        }

        return $query->first();
    }

    /**
     * Get effective platform fee percentage with perks applied
     */
    public function getEffectivePlatformFee(float $baseFee = 10.00): float
    {
        $effectiveFee = $baseFee;

        // Check for commission reduction perk
        $commissionPerk = $this->getActivePerk('commission_reduction');
        if ($commissionPerk && $commissionPerk->perk_data) {
            $reduction = $commissionPerk->perk_data['commission_reduction'] ?? 0;
            $effectiveFee = max(5.0, $baseFee - $reduction); // Min 5% fee
        }

        return $effectiveFee;
    }

    /**
     * Check if user has listing boost active
     */
    public function hasListingBoost(): bool
    {
        return $this->hasActivePerk('listing_boost');
    }

    /**
     * Get listing boost multiplier
     */
    public function getListingBoostMultiplier(): float
    {
        $boostPerk = $this->getActivePerk('listing_boost');
        if ($boostPerk && $boostPerk->perk_data) {
            return $boostPerk->perk_data['boost_multiplier'] ?? 1.0;
        }
        return 1.0;
    }
}
