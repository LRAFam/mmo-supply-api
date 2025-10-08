<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Season Pass Pricing
    |--------------------------------------------------------------------------
    |
    | Pricing for each season pass tier. Platform keeps 85% of revenue,
    | 15% goes to achievement rewards pool for the season.
    |
    */
    'pass_prices' => [
        'free' => 0.00,
        'basic' => 4.99,
        'premium' => 9.99,
        'elite' => 19.99,
    ],

    /*
    |--------------------------------------------------------------------------
    | Revenue Split
    |--------------------------------------------------------------------------
    |
    | Platform takes 85%, 15% goes to achievement rewards pool
    |
    */
    'platform_cut' => 0.85,
    'rewards_pool_percentage' => 0.15,

    /*
    |--------------------------------------------------------------------------
    | Reward Multipliers by Pass Tier
    |--------------------------------------------------------------------------
    |
    | Cash reward multipliers based on pass tier:
    | - Free: 0% (no cash rewards, only achievement points)
    | - Basic: 50% of base reward
    | - Premium: 100% of base reward
    | - Elite: 150% of base reward
    |
    */
    'reward_multipliers' => [
        'free' => 0.0,
        'basic' => 0.5,
        'premium' => 1.0,
        'elite' => 1.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Achievement Point Values
    |--------------------------------------------------------------------------
    |
    | Base achievement points awarded for each achievement tier.
    | All users (including free) earn these points.
    |
    */
    'point_values' => [
        'bronze' => 10,
        'silver' => 25,
        'gold' => 50,
        'platinum' => 100,
        'diamond' => 250,
        'master' => 500,
        'grandmaster' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Store Item Pricing (Achievement Points)
    |--------------------------------------------------------------------------
    |
    | Costs for items in the achievement points store
    |
    */
    'store_prices' => [
        // Profile Cosmetics
        'profile_themes' => [
            'common' => 100,
            'uncommon' => 250,
            'rare' => 500,
            'epic' => 1000,
            'legendary' => 2500,
        ],
        'titles' => [
            'common' => 150,
            'uncommon' => 300,
            'rare' => 750,
            'epic' => 1500,
            'legendary' => 3000,
        ],
        'frames' => [
            'common' => 200,
            'uncommon' => 400,
            'rare' => 1000,
            'epic' => 2000,
            'legendary' => 4000,
        ],
        'username_effects' => [
            'common' => 300,
            'uncommon' => 600,
            'rare' => 1500,
            'epic' => 3000,
            'legendary' => 6000,
        ],

        // Marketplace Perks
        'listing_boost_24h' => 200,
        'listing_boost_72h' => 500,
        'listing_boost_week' => 1000,
        'featured_discount_10' => 250,
        'featured_discount_25' => 750,
        'featured_discount_50' => 2000,
        'commission_reduction_day' => 300,
        'commission_reduction_week' => 1500,
        'commission_reduction_month' => 5000,

        // Functional Items
        'bulk_upload_50' => 400,
        'bulk_upload_100' => 750,
        'analytics_access_week' => 200,
        'analytics_access_month' => 600,
        'priority_support_week' => 500,
        'priority_support_month' => 1500,
        'custom_storefront' => 5000,

        // Social Items
        'verified_badge' => 10000,
        'chat_badge_common' => 100,
        'chat_badge_rare' => 500,
        'chat_badge_epic' => 1500,
        'profile_banner_slot' => 2000,
        'custom_profile_url' => 3000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dynamic Reward Scaling
    |--------------------------------------------------------------------------
    |
    | Base reward percentages of the season prize pool for each tier.
    | Actual rewards are calculated dynamically based on:
    | - Total season prize pool (15% of all pass sales)
    | - Number of users who complete each achievement
    | - User's pass tier multiplier
    |
    | Example: If 1000 users complete a gold achievement and the season
    | pool is $10,000, base gold reward would be ($10,000 * 0.05) / 1000 = $0.50
    | Premium user gets $0.50, Elite gets $0.75, Basic gets $0.25
    |
    */
    'base_reward_percentages' => [
        'bronze' => 0.02,    // 2% of pool
        'silver' => 0.03,    // 3% of pool
        'gold' => 0.05,      // 5% of pool
        'platinum' => 0.08,  // 8% of pool
        'diamond' => 0.12,   // 12% of pool
        'master' => 0.20,    // 20% of pool
        'grandmaster' => 0.35, // 35% of pool
    ],

    /*
    |--------------------------------------------------------------------------
    | Seasonal Achievement Settings
    |--------------------------------------------------------------------------
    |
    | Settings for seasonal achievements that grant permanent badges
    |
    */
    'seasonal_achievements' => [
        'enabled' => true,
        'max_per_season' => 10, // Max seasonal achievements per season
        'badge_prefix' => 'S', // Badge naming: S1, S2, S3, etc.
        'grant_permanent_badges' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Achievement Store Settings
    |--------------------------------------------------------------------------
    |
    */
    'store' => [
        'enabled' => true,
        'refresh_inventory_daily' => true, // Rotate limited/seasonal items
        'max_owned_items' => 1000, // Max items a user can own
        'allow_gifting' => false, // For future implementation
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum Values
    |--------------------------------------------------------------------------
    |
    | Minimum thresholds to ensure rewards are meaningful
    |
    */
    'minimums' => [
        'season_pool' => 100.00, // Minimum $100 prize pool to activate cash rewards
        'cash_reward' => 0.10,   // Minimum $0.10 cash reward per achievement claim
        'pass_sales_required' => 20, // Minimum pass sales before activating rewards
    ],
];
