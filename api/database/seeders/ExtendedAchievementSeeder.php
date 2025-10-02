<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class ExtendedAchievementSeeder extends Seeder
{
    /**
     * Extended tier system with 10 levels for long-term engagement
     */
    public function run(): void
    {
        // Clear existing achievements (handle foreign keys)
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('user_achievements')->truncate();
        \DB::table('achievements')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $achievements = [];

        // BUYER CATEGORY - 10 Tiers
        $buyerTiers = [
            ['tier' => 'copper', 'level' => 1, 'purchases' => 1, 'spent' => 10, 'points' => 10, 'reward' => 5, 'icon' => '🎯', 'name' => 'First Steps', 'desc' => 'Make your first purchase'],
            ['tier' => 'bronze', 'level' => 2, 'purchases' => 5, 'spent' => 50, 'points' => 25, 'reward' => 10, 'icon' => '🛒', 'name' => 'Window Shopper', 'desc' => 'Complete 5 purchases'],
            ['tier' => 'silver', 'level' => 3, 'purchases' => 15, 'spent' => 150, 'points' => 50, 'reward' => 25, 'icon' => '🛍️', 'name' => 'Regular Customer', 'desc' => 'Complete 15 purchases'],
            ['tier' => 'gold', 'level' => 4, 'purchases' => 30, 'spent' => 500, 'points' => 100, 'reward' => 50, 'icon' => '💳', 'name' => 'Big Spender', 'desc' => 'Spend $500 total'],
            ['tier' => 'emerald', 'level' => 5, 'purchases' => 50, 'spent' => 1000, 'points' => 150, 'reward' => 100, 'icon' => '💎', 'name' => 'Premium Buyer', 'desc' => 'Spend $1,000 total'],
            ['tier' => 'sapphire', 'level' => 6, 'purchases' => 75, 'spent' => 2000, 'points' => 250, 'reward' => 200, 'icon' => '💠', 'name' => 'VIP Shopper', 'desc' => 'Spend $2,000 total'],
            ['tier' => 'ruby', 'level' => 7, 'purchases' => 100, 'spent' => 3500, 'points' => 400, 'reward' => 350, 'icon' => '💍', 'name' => 'Elite Collector', 'desc' => 'Spend $3,500 total'],
            ['tier' => 'diamond', 'level' => 8, 'purchases' => 150, 'spent' => 5000, 'points' => 600, 'reward' => 500, 'icon' => '💎', 'name' => 'Diamond Client', 'desc' => 'Spend $5,000 total'],
            ['tier' => 'master', 'level' => 9, 'purchases' => 250, 'spent' => 10000, 'points' => 1000, 'reward' => 1000, 'icon' => '👑', 'name' => 'Master Buyer', 'desc' => 'Spend $10,000 total'],
            ['tier' => 'grandmaster', 'level' => 10, 'purchases' => 500, 'spent' => 25000, 'points' => 2500, 'reward' => 2500, 'icon' => '🏆', 'name' => 'Legendary Patron', 'desc' => 'Spend $25,000 total - You are legendary!'],
        ];

        foreach ($buyerTiers as $tier) {
            $achievements[] = [
                'name' => $tier['name'],
                'slug' => 'buyer-' . $tier['tier'] . '-' . $tier['level'],
                'description' => $tier['desc'],
                'icon' => $tier['icon'],
                'category' => 'buyer',
                'tier' => $tier['tier'],
                'level' => $tier['level'],
                'points' => $tier['points'],
                'wallet_reward' => $tier['reward'],
                'requirements' => json_encode(['total_purchases' => $tier['purchases'], 'total_spent' => $tier['spent']]),
                'is_active' => true,
                'is_secret' => false,
            ];
        }

        // SELLER CATEGORY - 10 Tiers
        $sellerTiers = [
            ['tier' => 'copper', 'level' => 1, 'sales' => 1, 'revenue' => 10, 'points' => 15, 'reward' => 10, 'icon' => '📦', 'name' => 'First Sale', 'desc' => 'Make your first sale'],
            ['tier' => 'bronze', 'level' => 2, 'sales' => 5, 'revenue' => 100, 'points' => 40, 'reward' => 25, 'icon' => '📈', 'name' => 'Rookie Seller', 'desc' => 'Complete 5 sales'],
            ['tier' => 'silver', 'level' => 3, 'sales' => 15, 'revenue' => 300, 'points' => 75, 'reward' => 50, 'icon' => '🏪', 'name' => 'Rising Merchant', 'desc' => 'Complete 15 sales'],
            ['tier' => 'gold', 'level' => 4, 'sales' => 30, 'revenue' => 1000, 'points' => 150, 'reward' => 100, 'icon' => '💰', 'name' => 'Profit Maker', 'desc' => 'Generate $1,000 revenue'],
            ['tier' => 'emerald', 'level' => 5, 'sales' => 50, 'revenue' => 2500, 'points' => 250, 'reward' => 200, 'icon' => '💵', 'name' => 'Business Owner', 'desc' => 'Generate $2,500 revenue'],
            ['tier' => 'sapphire', 'level' => 6, 'sales' => 75, 'revenue' => 5000, 'points' => 400, 'reward' => 400, 'icon' => '🎖️', 'name' => 'Top Seller', 'desc' => 'Generate $5,000 revenue'],
            ['tier' => 'ruby', 'level' => 7, 'sales' => 125, 'revenue' => 10000, 'points' => 650, 'reward' => 750, 'icon' => '⭐', 'name' => 'Sales Champion', 'desc' => 'Generate $10,000 revenue'],
            ['tier' => 'diamond', 'level' => 8, 'sales' => 200, 'revenue' => 20000, 'points' => 1000, 'reward' => 1500, 'icon' => '🏆', 'name' => 'Marketplace Tycoon', 'desc' => 'Generate $20,000 revenue'],
            ['tier' => 'master', 'level' => 9, 'sales' => 350, 'revenue' => 50000, 'points' => 2000, 'reward' => 3000, 'icon' => '👑', 'name' => 'Master Merchant', 'desc' => 'Generate $50,000 revenue'],
            ['tier' => 'grandmaster', 'level' => 10, 'sales' => 500, 'revenue' => 100000, 'points' => 5000, 'reward' => 10000, 'icon' => '🌟', 'name' => 'Empire Builder', 'desc' => 'Generate $100,000 revenue - You built an empire!'],
        ];

        foreach ($sellerTiers as $tier) {
            $achievements[] = [
                'name' => $tier['name'],
                'slug' => 'seller-' . $tier['tier'] . '-' . $tier['level'],
                'description' => $tier['desc'],
                'icon' => $tier['icon'],
                'category' => 'seller',
                'tier' => $tier['tier'],
                'level' => $tier['level'],
                'points' => $tier['points'],
                'wallet_reward' => $tier['reward'],
                'requirements' => json_encode(['is_seller' => true, 'total_sales' => $tier['sales'], 'total_revenue' => $tier['revenue']]),
                'is_active' => true,
                'is_secret' => false,
            ];
        }

        // SOCIAL CATEGORY - 10 Tiers
        $socialTiers = [
            ['tier' => 'copper', 'level' => 1, 'messages' => 1, 'reviews' => 1, 'points' => 5, 'reward' => 2, 'icon' => '💬', 'name' => 'Hello World', 'desc' => 'Send your first message'],
            ['tier' => 'bronze', 'level' => 2, 'messages' => 10, 'reviews' => 3, 'points' => 20, 'reward' => 10, 'icon' => '📨', 'name' => 'Conversationalist', 'desc' => 'Send 10 messages and leave 3 reviews'],
            ['tier' => 'silver', 'level' => 3, 'messages' => 25, 'reviews' => 10, 'points' => 40, 'reward' => 25, 'icon' => '✍️', 'name' => 'Active Member', 'desc' => 'Send 25 messages and leave 10 reviews'],
            ['tier' => 'gold', 'level' => 4, 'messages' => 50, 'reviews' => 20, 'points' => 75, 'reward' => 50, 'icon' => '🗣️', 'name' => 'Community Voice', 'desc' => 'Send 50 messages and leave 20 reviews'],
            ['tier' => 'emerald', 'level' => 5, 'messages' => 100, 'reviews' => 35, 'points' => 125, 'reward' => 100, 'icon' => '🦋', 'name' => 'Social Butterfly', 'desc' => 'Send 100 messages and leave 35 reviews'],
            ['tier' => 'sapphire', 'level' => 6, 'messages' => 200, 'reviews' => 50, 'points' => 200, 'reward' => 175, 'icon' => '📝', 'name' => 'Review Expert', 'desc' => 'Send 200 messages and leave 50 reviews'],
            ['tier' => 'ruby', 'level' => 7, 'messages' => 350, 'reviews' => 75, 'points' => 350, 'reward' => 300, 'icon' => '👥', 'name' => 'Community Pillar', 'desc' => 'Send 350 messages and leave 75 reviews'],
            ['tier' => 'diamond', 'level' => 8, 'messages' => 500, 'reviews' => 100, 'points' => 500, 'reward' => 500, 'icon' => '🎖️', 'name' => 'Community Leader', 'desc' => 'Send 500 messages and leave 100 reviews'],
            ['tier' => 'master', 'level' => 9, 'messages' => 1000, 'reviews' => 150, 'points' => 1000, 'reward' => 1000, 'icon' => '⭐', 'name' => 'Master Communicator', 'desc' => 'Send 1000 messages and leave 150 reviews'],
            ['tier' => 'grandmaster', 'level' => 10, 'messages' => 2500, 'reviews' => 250, 'points' => 2500, 'reward' => 2500, 'icon' => '🌟', 'name' => 'Community Legend', 'desc' => 'Send 2500 messages and leave 250 reviews - A true legend!'],
        ];

        foreach ($socialTiers as $tier) {
            $achievements[] = [
                'name' => $tier['name'],
                'slug' => 'social-' . $tier['tier'] . '-' . $tier['level'],
                'description' => $tier['desc'],
                'icon' => $tier['icon'],
                'category' => 'social',
                'tier' => $tier['tier'],
                'level' => $tier['level'],
                'points' => $tier['points'],
                'wallet_reward' => $tier['reward'],
                'requirements' => json_encode(['messages_sent' => $tier['messages'], 'reviews_count' => $tier['reviews']]),
                'is_active' => true,
                'is_secret' => false,
            ];
        }

        // SPECIAL CATEGORY - Unique achievements
        $specialAchievements = [
            [
                'name' => 'Early Adopter',
                'slug' => 'early-adopter',
                'description' => 'Join the platform in its first month',
                'icon' => '🚀',
                'category' => 'special',
                'tier' => 'gold',
                'level' => 1,
                'points' => 100,
                'wallet_reward' => 50.00,
                'requirements' => json_encode(['account_age_days' => 1]),
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'Veteran Member',
                'slug' => 'veteran-member',
                'description' => 'Be a member for 6 months',
                'icon' => '🎖️',
                'category' => 'special',
                'tier' => 'sapphire',
                'level' => 2,
                'points' => 150,
                'wallet_reward' => 75.00,
                'requirements' => json_encode(['account_age_days' => 180]),
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'One Year Strong',
                'slug' => 'one-year-strong',
                'description' => 'Be a member for 1 year',
                'icon' => '🏅',
                'category' => 'special',
                'tier' => 'diamond',
                'level' => 3,
                'points' => 300,
                'wallet_reward' => 150.00,
                'requirements' => json_encode(['account_age_days' => 365]),
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'Jack of All Trades',
                'slug' => 'jack-of-all-trades',
                'description' => 'Be active as both buyer, seller, and community member',
                'icon' => '🎭',
                'category' => 'special',
                'tier' => 'ruby',
                'level' => 1,
                'points' => 250,
                'wallet_reward' => 200.00,
                'requirements' => json_encode([
                    'total_purchases' => 25,
                    'total_sales' => 25,
                    'reviews_count' => 25,
                    'messages_sent' => 100
                ]),
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'Perfect Rating',
                'slug' => 'perfect-rating',
                'description' => 'Maintain 5.0 average rating with 50+ reviews',
                'icon' => '⭐',
                'category' => 'special',
                'tier' => 'diamond',
                'level' => 1,
                'points' => 400,
                'wallet_reward' => 300.00,
                'requirements' => json_encode(['is_seller' => true, 'average_rating' => 5.0, 'reviews_count' => 50]),
                'is_active' => true,
                'is_secret' => false,
            ],
            [
                'name' => 'Secret Treasure',
                'slug' => 'secret-treasure',
                'description' => 'Found the hidden achievement!',
                'icon' => '🎁',
                'category' => 'special',
                'tier' => 'master',
                'level' => 1,
                'points' => 500,
                'wallet_reward' => 500.00,
                'requirements' => json_encode(['total_purchases' => 1, 'total_sales' => 1, 'messages_sent' => 100]),
                'is_active' => true,
                'is_secret' => true,
            ],
            [
                'name' => 'Diamond Hands',
                'slug' => 'diamond-hands',
                'description' => 'Complete the ultimate challenge',
                'icon' => '💎',
                'category' => 'special',
                'tier' => 'grandmaster',
                'level' => 1,
                'points' => 2000,
                'wallet_reward' => 5000.00,
                'requirements' => json_encode([
                    'total_purchases' => 200,
                    'total_spent' => 10000,
                    'total_sales' => 200,
                    'total_revenue' => 25000,
                    'average_rating' => 4.8,
                    'reviews_count' => 100
                ]),
                'is_active' => true,
                'is_secret' => true,
            ],
            [
                'name' => 'The Ultimate',
                'slug' => 'the-ultimate',
                'description' => 'Reach the peak of all progression paths',
                'icon' => '🌟',
                'category' => 'special',
                'tier' => 'grandmaster',
                'level' => 10,
                'points' => 10000,
                'wallet_reward' => 25000.00,
                'requirements' => json_encode([
                    'total_purchases' => 500,
                    'total_spent' => 25000,
                    'total_sales' => 500,
                    'total_revenue' => 100000,
                    'average_rating' => 4.9,
                    'reviews_count' => 250,
                    'messages_sent' => 2500
                ]),
                'is_active' => true,
                'is_secret' => true,
            ],
        ];

        $achievements = array_merge($achievements, $specialAchievements);

        // Insert all achievements
        foreach ($achievements as $achievement) {
            Achievement::create($achievement);
        }

        $this->command->info('Extended achievement system created with 10 tiers per category!');
        $this->command->info('Total achievements: ' . count($achievements));
        $this->command->info('Buyer: 10 tiers | Seller: 10 tiers | Social: 10 tiers | Special: 8 unique');
    }
}
