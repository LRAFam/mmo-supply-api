<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reward Scaling Message
    |--------------------------------------------------------------------------
    |
    | Transparent message about how rewards grow with the platform
    |
    */
    'scaling_message' => 'Event prizes and referral bonuses scale with platform growth. As MMO Supply grows, so do the rewards! 🚀',

    /*
    |--------------------------------------------------------------------------
    | Referral System Configuration
    |--------------------------------------------------------------------------
    */
    'referral' => [
        // Minimum spend for a referral to count as valid
        'min_referred_purchase' => 20.00, // $20 minimum

        // Referrer rewards (when someone they referred makes qualifying purchase)
        'referrer_bonus' => [
            'amount' => 1.50,
            'type' => 'bonus_balance', // 'bonus_balance' = platform credit only, 'balance' = withdrawable
            'description' => 'Referral bonus - platform credit',
        ],

        // Referred user welcome bonus (one-time)
        'referred_bonus' => [
            'amount' => 1.50,
            'type' => 'bonus_balance',
            'min_spend' => 20.00,
            'description' => 'Welcome bonus for joining via referral',
        ],

        // Tiered commission on ongoing purchases (optional - disabled for now)
        'commission_enabled' => false,
        'commission_tiers' => [
            [
                'months' => 3,
                'percentage' => 3.0,
                'type' => 'bonus_balance',
            ],
            [
                'months' => 6,
                'percentage' => 1.5,
                'type' => 'bonus_balance',
            ],
            [
                'months' => null, // Forever after
                'percentage' => 1.0,
                'type' => 'bonus_balance',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Welcome Bonus for All New Users
    |--------------------------------------------------------------------------
    */
    'welcome_bonus' => [
        'enabled' => true,
        'tiers' => [
            [
                'min_spend' => 20.00,
                'max_spend' => 49.99,
                'bonus' => 2.00,
                'type' => 'bonus_balance',
            ],
            [
                'min_spend' => 50.00,
                'max_spend' => 99.99,
                'bonus' => 5.00,
                'type' => 'bonus_balance',
            ],
            [
                'min_spend' => 100.00,
                'max_spend' => null,
                'bonus' => 10.00,
                'type' => 'bonus_balance',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monthly Referral Rally Event
    |--------------------------------------------------------------------------
    */
    'referral_rally' => [
        'enabled' => true,
        'prize_pool' => 20.00, // $20/month - ultra-lean bootstrap mode
        'prizes' => [
            ['rank' => 1, 'amount' => 10.00, 'type' => 'bonus_balance', 'badge' => 'top_referrer'],
            ['rank' => 2, 'amount' => 6.00, 'type' => 'bonus_balance', 'badge' => 'top_referrer'],
            ['rank' => 3, 'amount' => 4.00, 'type' => 'bonus_balance', 'badge' => null],
        ],
        'requirements' => [
            'min_referred_purchase' => 15.00,
            'email_verified_only' => true,
            'active_referral_multiplier' => 2, // Referrals with 2+ purchases count double
        ],
        'featured_benefits' => [
            'profile_highlight' => true,
            'leaderboard_display' => true,
            'exclusive_badge' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reward Tiers Based on Platform Revenue
    |--------------------------------------------------------------------------
    | These define how rewards automatically scale as platform grows
    */
    'scaling_tiers' => [
        [
            'name' => 'Bootstrap',
            'monthly_revenue' => 0,
            'referral_rally_pool' => 20.00,
            'welcome_bonus_multiplier' => 1.0,
            'message' => '🌱 Bootstrap Phase - Growing together!',
        ],
        [
            'name' => 'Growth',
            'monthly_revenue' => 500,
            'referral_rally_pool' => 50.00,
            'welcome_bonus_multiplier' => 1.25,
            'message' => '📈 Growth Phase - Rewards increasing!',
        ],
        [
            'name' => 'Scale',
            'monthly_revenue' => 2000,
            'referral_rally_pool' => 100.00,
            'welcome_bonus_multiplier' => 1.5,
            'message' => '🚀 Scale Phase - Big rewards!',
        ],
        [
            'name' => 'Established',
            'monthly_revenue' => 10000,
            'referral_rally_pool' => 500.00,
            'welcome_bonus_multiplier' => 2.0,
            'message' => '💎 Established - Maximum rewards!',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Free Growth Tactics (Always Enabled)
    |--------------------------------------------------------------------------
    */
    'free_benefits' => [
        'top_referrer_badge' => true,
        'featured_seller_spotlight' => true,
        'leaderboard_display' => true,
        'social_recognition' => true,
        'custom_titles' => true,
        'profile_highlights' => true,
    ],
];
