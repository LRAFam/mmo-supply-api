<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tier',
        'fee_percentage',
        'monthly_price',
        'started_at',
        'expires_at',
        'is_active',
        'stripe_subscription_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'fee_percentage' => 'decimal:2',
        'monthly_price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the creator earnings percentage for this subscription
     * Returns what percentage the creator/seller keeps
     */
    public function getCreatorEarningsPercentage(): float
    {
        return match($this->tier) {
            'basic' => 70.0,     // Platform gets 30%
            'premium' => 80.0,   // Platform gets 20%
            'elite' => 90.0,     // Platform gets 10%
            default => 70.0,
        };
    }

    /**
     * Get the platform fee percentage (inverse of creator earnings)
     */
    public function getPlatformFee(): float
    {
        return 100 - $this->getCreatorEarningsPercentage();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get subscription benefits
     */
    public function getBenefits(): array
    {
        return match($this->tier) {
            'premium' => [
                // Marketplace Benefits
                'verified_badge' => true,
                'priority_listings' => true,
                'featured_listings' => 3, // 3 featured listings per month
                'custom_storefront' => true,
                'analytics' => 'advanced',

                // Support
                'priority_support' => true,
                'support_response_time' => '24 hours',

                // Marketing Tools
                'email_marketing' => true,
                'promotional_banners' => true,

                // Gaming Features
                'premium_spins_per_week' => 4,
                'exclusive_events' => true,
                'early_feature_access' => false,

                // Financial
                'reduced_fees' => true,
                'earnings_percentage' => 80.0,
            ],
            'elite' => [
                // Marketplace Benefits
                'verified_badge' => true,
                'priority_listings' => true,
                'featured_listings' => -1, // Unlimited
                'custom_storefront' => true,
                'analytics' => 'premium',

                // Support
                'priority_support' => true,
                'support_response_time' => '1 hour',
                'dedicated_account_manager' => true,

                // Marketing Tools
                'email_marketing' => true,
                'promotional_banners' => true,
                'white_label_options' => true,

                // Gaming Features
                'premium_spins_per_week' => 8,
                'exclusive_events' => true,
                'early_feature_access' => true,

                // Advanced Features
                'api_access' => true,
                'bulk_operations' => true,

                // Financial
                'reduced_fees' => true,
                'earnings_percentage' => 90.0,
            ],
            default => [
                // Basic tier (no subscription)
                'verified_badge' => false,
                'priority_listings' => false,
                'featured_listings' => 0, // Pay per use
                'custom_storefront' => false,
                'analytics' => 'basic',

                'priority_support' => false,
                'support_response_time' => '48-72 hours',

                'email_marketing' => false,
                'promotional_banners' => false,

                'premium_spins_per_week' => 0,
                'exclusive_events' => false,
                'early_feature_access' => false,

                'reduced_fees' => false,
                'earnings_percentage' => 70.0,
            ],
        };
    }

    /**
     * Get formatted benefits list for display
     */
    public function getFormattedBenefits(): array
    {
        return match($this->tier) {
            'premium' => [
                '✓ Verified seller badge',
                '✓ 3 featured listing slots per month',
                '✓ Priority placement in search results',
                '✓ Advanced analytics dashboard',
                '✓ Custom storefront colors & branding',
                '✓ Email marketing tools',
                '✓ Priority support (24h response)',
                '✓ 4 premium spin wheel spins per week',
                '✓ Access to exclusive events',
                '✓ Keep 80% of earnings (vs 70%)',
            ],
            'elite' => [
                '✓ Verified seller badge',
                '✓ Unlimited featured listings',
                '✓ Top priority placement everywhere',
                '✓ Premium analytics & insights',
                '✓ Full custom storefront & white-label',
                '✓ Advanced marketing suite',
                '✓ Dedicated account manager',
                '✓ 1-hour priority support',
                '✓ 8 premium spin wheel spins per week',
                '✓ Exclusive events & early features',
                '✓ API access for automation',
                '✓ Keep 90% of earnings (vs 70%)',
            ],
            default => [
                '✓ Unlimited basic listings',
                '✓ Basic analytics',
                '✓ Email support (48-72h)',
                '✓ Standard marketplace visibility',
                '✓ Keep 70% of earnings',
            ],
        };
    }
}
