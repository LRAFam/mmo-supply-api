<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class ProgressiveAchievementSeeder extends Seeder
{
    /**
     * Progressive achievement system where each achievement has 10 tiers
     */
    public function run(): void
    {
        // Clear existing achievements
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('user_achievements')->truncate();
        \DB::table('achievements')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $tierConfig = [
            ['tier' => 'copper', 'level' => 1, 'multiplier' => 1],
            ['tier' => 'bronze', 'level' => 2, 'multiplier' => 2],
            ['tier' => 'silver', 'level' => 3, 'multiplier' => 4],
            ['tier' => 'gold', 'level' => 4, 'multiplier' => 8],
            ['tier' => 'emerald', 'level' => 5, 'multiplier' => 15],
            ['tier' => 'sapphire', 'level' => 6, 'multiplier' => 25],
            ['tier' => 'ruby', 'level' => 7, 'multiplier' => 40],
            ['tier' => 'diamond', 'level' => 8, 'multiplier' => 60],
            ['tier' => 'master', 'level' => 9, 'multiplier' => 100],
            ['tier' => 'grandmaster', 'level' => 10, 'multiplier' => 200],
        ];

        // Define base achievements with their scaling metrics
        $baseAchievements = [
            // BUYER ACHIEVEMENTS
            [
                'group' => 'total-buyer',
                'base_name' => 'Shopping Journey',
                'category' => 'buyer',
                'icon' => '🛒',
                'base_desc' => 'Complete purchases on the marketplace',
                'requirement_type' => 'total_purchases',
                'base_value' => 5,
                'base_points' => 10,
                'base_reward' => 0.25,
            ],
            [
                'group' => 'big-spender',
                'base_name' => 'Big Spender',
                'category' => 'buyer',
                'icon' => '💰',
                'base_desc' => 'Spend money on the marketplace',
                'requirement_type' => 'total_spent',
                'base_value' => 50,
                'base_points' => 15,
                'base_reward' => 0.50,
            ],

            // SELLER ACHIEVEMENTS
            [
                'group' => 'total-seller',
                'base_name' => 'Merchant Path',
                'category' => 'seller',
                'icon' => '📦',
                'base_desc' => 'Complete sales as a seller',
                'requirement_type' => 'total_sales',
                'base_value' => 5,
                'base_points' => 15,
                'base_reward' => 0.50,
                'extra_requirement' => ['is_seller' => true],
            ],
            [
                'group' => 'profit-maker',
                'base_name' => 'Revenue Generator',
                'category' => 'seller',
                'icon' => '💵',
                'base_desc' => 'Generate revenue from sales',
                'requirement_type' => 'total_revenue',
                'base_value' => 100,
                'base_points' => 20,
                'base_reward' => 1.00,
                'extra_requirement' => ['is_seller' => true],
            ],

            // SOCIAL ACHIEVEMENTS
            [
                'group' => 'social-butterfly',
                'base_name' => 'Social Butterfly',
                'category' => 'social',
                'icon' => '💬',
                'base_desc' => 'Send messages to other users',
                'requirement_type' => 'messages_sent',
                'base_value' => 10,
                'base_points' => 5,
                'base_reward' => 0.10,
            ],
            [
                'group' => 'review-master',
                'base_name' => 'Review Master',
                'category' => 'social',
                'icon' => '✍️',
                'base_desc' => 'Leave helpful reviews',
                'requirement_type' => 'reviews_count',
                'base_value' => 5,
                'base_points' => 10,
                'base_reward' => 0.25,
            ],

            // ENGAGEMENT ACHIEVEMENTS
            [
                'group' => 'daily-spinner',
                'base_name' => 'Daily Spinner',
                'category' => 'engagement',
                'icon' => '🎰',
                'base_desc' => 'Complete daily spins',
                'requirement_type' => 'daily_spins_count',
                'base_value' => 7,
                'base_points' => 5,
                'base_reward' => 0.10,
            ],
            [
                'group' => 'premium-spinner',
                'base_name' => 'Premium Spinner',
                'category' => 'engagement',
                'icon' => '💎',
                'base_desc' => 'Complete premium spins',
                'requirement_type' => 'premium_spins_count',
                'base_value' => 5,
                'base_points' => 10,
                'base_reward' => 0.25,
            ],
            [
                'group' => 'login-streak',
                'base_name' => 'Dedicated User',
                'category' => 'engagement',
                'icon' => '🔥',
                'base_desc' => 'Maintain login streaks',
                'requirement_type' => 'login_streak_days',
                'base_value' => 7,
                'base_points' => 10,
                'base_reward' => 0.20,
            ],
            [
                'group' => 'wishlist-master',
                'base_name' => 'Wishlist Master',
                'category' => 'engagement',
                'icon' => '⭐',
                'base_desc' => 'Add items to wishlist',
                'requirement_type' => 'wishlist_items',
                'base_value' => 10,
                'base_points' => 5,
                'base_reward' => 0.10,
            ],
        ];

        foreach ($baseAchievements as $base) {
            $previousId = null;

            foreach ($tierConfig as $tier) {
                $scaledValue = $base['base_value'] * $tier['multiplier'];
                $scaledPoints = $base['base_points'] * $tier['multiplier'];
                $scaledReward = $base['base_reward'] * $tier['multiplier'];

                $requirements = [
                    $base['requirement_type'] => $scaledValue
                ];

                if (isset($base['extra_requirement'])) {
                    $requirements = array_merge($requirements, $base['extra_requirement']);
                }

                $achievement = Achievement::create([
                    'name' => $base['base_name'] . ' ' . ucfirst($tier['tier']),
                    'slug' => $base['group'] . '-' . $tier['tier'],
                    'achievement_group' => $base['group'],
                    'description' => $base['base_desc'] . ' (' . $this->formatRequirement($base['requirement_type'], $scaledValue) . ')',
                    'icon' => $base['icon'],
                    'category' => $base['category'],
                    'tier' => $tier['tier'],
                    'level' => $tier['level'],
                    'next_tier_id' => null, // Will be set after creating the next tier
                    'points' => $scaledPoints,
                    'wallet_reward' => $scaledReward,
                    'requirements' => $requirements,
                    'is_active' => true,
                    'is_secret' => false,
                ]);

                // Link previous tier to this one
                if ($previousId) {
                    Achievement::where('id', $previousId)->update(['next_tier_id' => $achievement->id]);
                }

                $previousId = $achievement->id;
            }
        }

        // Add special achievements
        $specialAchievements = [
            [
                'name' => 'Early Adopter',
                'slug' => 'early-adopter',
                'achievement_group' => 'special-early',
                'description' => 'Join the platform in its first month',
                'icon' => '🚀',
                'category' => 'special',
                'tier' => 'gold',
                'level' => 1,
                'points' => 100,
                'wallet_reward' => 2.50,
                'requirements' => ['account_age_days' => 1],
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'Jack of All Trades',
                'slug' => 'jack-of-all-trades',
                'achievement_group' => 'special-jack',
                'description' => 'Be active as buyer, seller, and community member',
                'icon' => '🎭',
                'category' => 'special',
                'tier' => 'ruby',
                'level' => 1,
                'points' => 250,
                'wallet_reward' => 10.00,
                'requirements' => [
                    'total_purchases' => 25,
                    'total_sales' => 25,
                    'reviews_count' => 25,
                    'messages_sent' => 100
                ],
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'Perfect Service',
                'slug' => 'perfect-service',
                'achievement_group' => 'special-perfect',
                'description' => 'Maintain 5.0 average rating with 50+ reviews',
                'icon' => '⭐',
                'category' => 'special',
                'tier' => 'diamond',
                'level' => 1,
                'points' => 400,
                'wallet_reward' => 15.00,
                'requirements' => ['is_seller' => true, 'average_rating' => 5.0, 'reviews_count' => 50],
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'Secret Treasure',
                'slug' => 'secret-treasure',
                'achievement_group' => 'special-secret',
                'description' => 'Found the hidden achievement!',
                'icon' => '🎁',
                'category' => 'special',
                'tier' => 'master',
                'level' => 1,
                'points' => 500,
                'wallet_reward' => 25.00,
                'requirements' => ['total_purchases' => 1, 'total_sales' => 1, 'messages_sent' => 100],
                'is_active' => true,
                'is_secret' => true,
            ],
            [
                'name' => 'The Ultimate',
                'slug' => 'the-ultimate',
                'achievement_group' => 'special-ultimate',
                'description' => 'Reach Grandmaster in all main achievement categories',
                'icon' => '🌟',
                'category' => 'special',
                'tier' => 'grandmaster',
                'level' => 10,
                'points' => 10000,
                'wallet_reward' => 100.00,
                'requirements' => [
                    'total_purchases' => 1000,
                    'total_spent' => 10000,
                    'total_sales' => 1000,
                    'total_revenue' => 20000,
                    'reviews_count' => 1000,
                    'messages_sent' => 2000
                ],
                'is_active' => true,
                'is_secret' => true,
            ],
            [
                'name' => 'Lucky Streak',
                'slug' => 'lucky-streak',
                'achievement_group' => 'special-lucky',
                'description' => 'Win 3 spin wheel prizes in a row',
                'icon' => '🍀',
                'category' => 'special',
                'tier' => 'gold',
                'level' => 1,
                'points' => 50,
                'wallet_reward' => 1.00,
                'requirements' => ['consecutive_spin_wins' => 3],
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'Spin Master',
                'slug' => 'spin-master',
                'achievement_group' => 'special-spin-master',
                'description' => 'Complete 100 total spins (daily + premium)',
                'icon' => '🎡',
                'category' => 'special',
                'tier' => 'emerald',
                'level' => 1,
                'points' => 150,
                'wallet_reward' => 3.00,
                'requirements' => ['total_spins' => 100],
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'Loyal Member',
                'slug' => 'loyal-member',
                'achievement_group' => 'special-loyal',
                'description' => 'Maintain 30-day login streak',
                'icon' => '🏅',
                'category' => 'special',
                'tier' => 'sapphire',
                'level' => 1,
                'points' => 200,
                'wallet_reward' => 5.00,
                'requirements' => ['login_streak_days' => 30],
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'First Timer',
                'slug' => 'first-timer',
                'achievement_group' => 'special-first',
                'description' => 'Complete your first daily spin',
                'icon' => '🎯',
                'category' => 'special',
                'tier' => 'copper',
                'level' => 1,
                'points' => 10,
                'wallet_reward' => 0.10,
                'requirements' => ['daily_spins_count' => 1],
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'High Roller',
                'slug' => 'high-roller',
                'achievement_group' => 'special-high-roller',
                'description' => 'Win $5+ from a single spin',
                'icon' => '💸',
                'category' => 'special',
                'tier' => 'gold',
                'level' => 1,
                'points' => 75,
                'wallet_reward' => 1.50,
                'requirements' => ['max_spin_win' => 5.00],
                'is_active' => true,
                'is_secret' => false,
            ],
        ];

        foreach ($specialAchievements as $achievement) {
            Achievement::create($achievement);
        }

        $totalAchievements = Achievement::count();
        $achievementGroups = Achievement::distinct('achievement_group')->count('achievement_group');

        $this->command->info('Progressive achievement system created!');
        $this->command->info('Total achievements: ' . $totalAchievements);
        $this->command->info('Achievement groups: ' . $achievementGroups . ' (10 progressive + 5 special)');
        $this->command->info('Each progressive achievement has 10 tiers that unlock sequentially');
    }

    private function formatRequirement(string $type, int $value): string
    {
        $labels = [
            'total_purchases' => $value . ' purchases',
            'total_spent' => '$' . number_format($value),
            'total_sales' => $value . ' sales',
            'total_revenue' => '$' . number_format($value) . ' revenue',
            'messages_sent' => $value . ' messages',
            'reviews_count' => $value . ' reviews',
            'daily_spins_count' => $value . ' daily spins',
            'premium_spins_count' => $value . ' premium spins',
            'login_streak_days' => $value . ' day streak',
            'wishlist_items' => $value . ' wishlist items',
        ];

        return $labels[$type] ?? $value;
    }
}
