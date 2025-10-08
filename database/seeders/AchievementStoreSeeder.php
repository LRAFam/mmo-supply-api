<?php

namespace Database\Seeders;

use App\Models\AchievementStoreItem;
use Illuminate\Database\Seeder;

class AchievementStoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Profile Themes
            [
                'name' => 'Cyber Neon Theme',
                'slug' => 'cyber-neon-theme',
                'description' => 'Futuristic neon-themed profile with animated backgrounds',
                'category' => 'profile_theme',
                'icon' => '🌆',
                'points_cost' => 500,
                'rarity' => 'rare',
                'is_active' => true,
            ],
            [
                'name' => 'Dark Galaxy Theme',
                'slug' => 'dark-galaxy-theme',
                'description' => 'Deep space theme with twinkling stars',
                'category' => 'profile_theme',
                'icon' => '🌌',
                'points_cost' => 750,
                'rarity' => 'epic',
                'is_active' => true,
            ],
            [
                'name' => 'Golden Royale Theme',
                'slug' => 'golden-royale-theme',
                'description' => 'Luxurious gold-themed profile for elite traders',
                'category' => 'profile_theme',
                'icon' => '👑',
                'points_cost' => 1500,
                'rarity' => 'legendary',
                'is_active' => true,
            ],

            // Titles
            [
                'name' => 'Master Trader',
                'slug' => 'master-trader-title',
                'description' => 'Display "Master Trader" as your profile title',
                'category' => 'title',
                'icon' => '💼',
                'points_cost' => 300,
                'rarity' => 'uncommon',
                'is_active' => true,
            ],
            [
                'name' => 'Legendary Merchant',
                'slug' => 'legendary-merchant-title',
                'description' => 'Display "Legendary Merchant" as your profile title',
                'category' => 'title',
                'icon' => '🏆',
                'points_cost' => 800,
                'rarity' => 'epic',
                'is_active' => true,
            ],
            [
                'name' => 'Grand Master',
                'slug' => 'grand-master-title',
                'description' => 'Display "Grand Master" as your profile title',
                'category' => 'title',
                'icon' => '⚡',
                'points_cost' => 2000,
                'rarity' => 'legendary',
                'is_active' => true,
            ],

            // Marketplace Perks
            [
                'name' => 'Priority Listing',
                'slug' => 'priority-listing-24h',
                'description' => 'Your listings appear higher in search for 24 hours',
                'category' => 'listing_boost',
                'icon' => '🚀',
                'points_cost' => 200,
                'rarity' => 'uncommon',
                'metadata' => json_encode([
                    'duration_hours' => 24,
                    'boost_multiplier' => 2.0,
                ]),
                'max_uses' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Featured Seller Badge (7 Days)',
                'slug' => 'featured-seller-7d',
                'description' => 'Display a featured badge on your profile for 7 days',
                'category' => 'marketplace_perk',
                'icon' => '⭐',
                'points_cost' => 500,
                'rarity' => 'rare',
                'metadata' => json_encode([
                    'duration_days' => 7,
                    'badge_type' => 'featured',
                ]),
                'max_uses' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Reduced Commission (30 Days)',
                'slug' => 'reduced-commission-30d',
                'description' => 'Get 5% reduced marketplace fees for 30 days',
                'category' => 'marketplace_perk',
                'icon' => '💰',
                'points_cost' => 1000,
                'rarity' => 'epic',
                'metadata' => json_encode([
                    'duration_days' => 30,
                    'commission_reduction' => 5.0,
                ]),
                'max_uses' => 1,
                'cooldown_days' => 30,
                'is_active' => true,
            ],

            // Profile Frames
            [
                'name' => 'Bronze Frame',
                'slug' => 'bronze-frame',
                'description' => 'Bronze border around your profile picture',
                'category' => 'frame',
                'icon' => '🥉',
                'points_cost' => 150,
                'rarity' => 'common',
                'is_active' => true,
            ],
            [
                'name' => 'Silver Frame',
                'slug' => 'silver-frame',
                'description' => 'Silver border around your profile picture',
                'category' => 'frame',
                'icon' => '🥈',
                'points_cost' => 400,
                'rarity' => 'uncommon',
                'is_active' => true,
            ],
            [
                'name' => 'Gold Frame',
                'slug' => 'gold-frame',
                'description' => 'Gold border around your profile picture',
                'category' => 'frame',
                'icon' => '🥇',
                'points_cost' => 1000,
                'rarity' => 'rare',
                'is_active' => true,
            ],
            [
                'name' => 'Diamond Frame',
                'slug' => 'diamond-frame',
                'description' => 'Diamond-encrusted border around your profile picture',
                'category' => 'frame',
                'icon' => '💎',
                'points_cost' => 2500,
                'rarity' => 'legendary',
                'is_active' => true,
            ],

            // Username Effects
            [
                'name' => 'Rainbow Name',
                'slug' => 'rainbow-name-effect',
                'description' => 'Your username displays with rainbow colors',
                'category' => 'username_effect',
                'icon' => '🌈',
                'points_cost' => 600,
                'rarity' => 'rare',
                'is_active' => true,
            ],
            [
                'name' => 'Glowing Name',
                'slug' => 'glowing-name-effect',
                'description' => 'Your username has a glowing effect',
                'category' => 'username_effect',
                'icon' => '✨',
                'points_cost' => 900,
                'rarity' => 'epic',
                'is_active' => true,
            ],

            // Seasonal Items
            [
                'name' => 'Winter Frost Theme',
                'slug' => 'winter-frost-theme',
                'description' => 'Exclusive winter-themed profile (Limited time)',
                'category' => 'seasonal',
                'icon' => '❄️',
                'points_cost' => 1200,
                'rarity' => 'epic',
                'is_limited' => true,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            AchievementStoreItem::updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );
        }

        $this->command->info('Achievement store items seeded successfully!');
    }
}
