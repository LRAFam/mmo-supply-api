<?php

namespace App\Services;

class SubscriptionService
{
    /**
     * Get all perks for a given tier and role
     */
    public static function getPerksForTier(string $tier, bool $isSeller): array
    {
        $perks = self::getAllPerks();

        $rolePerk = $isSeller ? 'seller' : 'buyer';
        $tierPerks = $perks[$tier] ?? $perks['free'];

        // Merge buyer perks with seller perks if user is a seller
        if ($isSeller && isset($tierPerks['seller'])) {
            return array_merge($tierPerks['buyer'], $tierPerks['seller']);
        }

        return $tierPerks['buyer'];
    }

    /**
     * Get formatted perks list for display
     */
    public static function getFormattedPerks(string $tier, bool $isSeller): array
    {
        $formatted = self::getAllFormattedPerks();

        if ($isSeller && isset($formatted[$tier]['seller'])) {
            return array_merge(
                $formatted[$tier]['buyer'],
                $formatted[$tier]['seller']
            );
        }

        return $formatted[$tier]['buyer'] ?? [];
    }

    /**
     * Complete perks configuration
     */
    private static function getAllPerks(): array
    {
        return [
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
                    'exclusive_events' => false,
                ],
                'seller' => [
                    'featured_listings' => 0,
                    'priority_placement' => false,
                    'analytics_tier' => 'basic',
                    'custom_storefront' => false,
                    'email_marketing' => false,
                    'earnings_percentage' => 80.0,
                ],
            ],
            'premium' => [
                'buyer' => [
                    'premium_spins_per_week' => 1,
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
                    'earnings_percentage' => 88.0,
                ],
            ],
            'elite' => [
                'buyer' => [
                    'premium_spins_per_week' => 2,
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
                    'earnings_percentage' => 92.0,
                ],
            ],
        ];
    }

    /**
     * Formatted perks for UI display
     */
    private static function getAllFormattedPerks(): array
    {
        return [
            'free' => [
                'buyer' => [
                    'Standard marketplace access',
                    'Basic search & filters',
                    'Cart & wishlist',
                    'Free daily spin wheel (24h cooldown)',
                    'Email support (48-72h)',
                    '7-day return window',
                ],
                'seller' => [
                    'Unlimited basic listings',
                    'Basic analytics',
                    'Standard visibility',
                    'Keep 80% of sales',
                ],
            ],
            'premium' => [
                'buyer' => [
                    '✨ 1 premium spin wheel spin per week',
                    '✨ Verified buyer badge',
                    '✨ Priority support (24h response)',
                    '✨ Early access to new deals',
                    '✨ Advanced search filters',
                    '✨ Price drop alerts on wishlist',
                    '✨ 5% bulk purchase discounts ($50+)',
                    '✨ 14-day return window',
                    '✨ Exclusive buyer events',
                ],
                'seller' => [
                    '✨ All buyer perks above',
                    '✨ Verified seller badge',
                    '✨ 3 featured listing slots per month',
                    '✨ Priority placement in search',
                    '✨ Advanced analytics dashboard',
                    '✨ Custom storefront colors & branding',
                    '✨ Email marketing tools',
                    '✨ Keep 88% of sales (+8% vs Free)',
                ],
            ],
            'elite' => [
                'buyer' => [
                    '👑 2 premium spin wheel spins per week',
                    '👑 Elite buyer badge (gold)',
                    '👑 1-hour priority support',
                    '👑 Dedicated account manager',
                    '👑 VIP 24h early access to new releases',
                    '👑 Premium search & market analytics',
                    '👑 Automated price tracking (API access)',
                    '👑 10% bulk purchase discounts ($50+)',
                    '👑 30-day return window',
                    '👑 Exclusive elite events & private auctions',
                    '👑 Concierge shopping assistance',
                ],
                'seller' => [
                    '👑 All buyer perks above',
                    '👑 Elite seller badge (gold)',
                    '👑 Unlimited featured listings',
                    '👑 Top priority placement everywhere',
                    '👑 Premium analytics & AI insights',
                    '👑 Full custom storefront & white-label',
                    '👑 Advanced marketing suite',
                    '👑 API access for automation',
                    '👑 Bulk operations tools',
                    '👑 Early feature access (beta)',
                    '👑 Keep 92% of sales (+12% vs Free)',
                ],
            ],
        ];
    }
}
