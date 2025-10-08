<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Game;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Seed various event types for the marketplace
     */
    public function run(): void
    {
        // Get some games for event association
        $games = Game::limit(5)->get();
        $osrsGame = $games->first();

        $events = [
            // ============== ACTIVE EVENTS ==============
            [
                'name' => '🎯 Referral Competition - Win $100!',
                'slug' => 'referral-competition-100',
                'description' => 'Bring your friends and WIN! Top 5 referrers share $100. Use your unique referral link to compete!',
                'type' => 'tournament',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18',
                'starts_at' => now()->subDays(3),
                'ends_at' => now()->addDays(25),
                'status' => 'active',
                'max_participants' => null,
                'winner_count' => 5,
                'prizes' => [
                    ['rank' => 1, 'description' => '🥇 Most Referrals: $40', 'wallet_amount' => 40.00],
                    ['rank' => 2, 'description' => '🥈 2nd Place: $25', 'wallet_amount' => 25.00],
                    ['rank' => 3, 'description' => '🥉 3rd Place: $20', 'wallet_amount' => 20.00],
                    ['rank' => 4, 'description' => '4th Place: $10', 'wallet_amount' => 10.00],
                    ['rank' => 5, 'description' => '5th Place: $5', 'wallet_amount' => 5.00],
                ],
                'rules' => [
                    'Refer friends using your unique referral link from /referrals',
                    'Points counted when referrals make their first purchase',
                    'Live leaderboard at /referrals',
                    'Winners announced at event end',
                    'Must have at least 2 qualifying referrals to win',
                ],
                'requirements' => null,
                'is_featured' => true,
            ],
            [
                'name' => '🎁 Discord Server Boost Rewards',
                'slug' => 'discord-boost-rewards',
                'description' => 'Boost our Discord server and get instant $10 credits + enter monthly $50 giveaway! Support the community and get rewarded.',
                'type' => 'giveaway',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1614680376408-81e91ffe3db7',
                'starts_at' => now()->subDays(1),
                'ends_at' => now()->addMonths(1),
                'status' => 'active',
                'max_participants' => null,
                'winner_count' => 3,
                'prizes' => [
                    ['rank' => 'instant', 'description' => 'Instant: $10 Credits (All Boosters)', 'wallet_amount' => 10.00],
                    ['rank' => 1, 'description' => 'Monthly Draw: $30', 'wallet_amount' => 30.00],
                    ['rank' => 2, 'description' => 'Monthly Draw: $15', 'wallet_amount' => 15.00],
                    ['rank' => 3, 'description' => 'Monthly Draw: $5', 'wallet_amount' => 5.00],
                ],
                'rules' => [
                    'Join Discord: discord.gg/JEmcpU8XjD',
                    'Boost the server to get $10 instant credits',
                    'Auto-entered into monthly $50 giveaway',
                    'DM an admin your MMO.SUPPLY username to claim',
                    'Exclusive booster role and perks',
                ],
                'requirements' => null,
                'is_featured' => true,
            ],
            [
                'name' => '🏆 First Purchase Bonus',
                'slug' => 'first-purchase-bonus',
                'description' => 'Make your first purchase and receive bonus credits! New buyers get instant rewards to kickstart their trading journey.',
                'type' => 'giveaway',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1607082349566-187342175e2f',
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->addMonths(3),
                'status' => 'active',
                'max_participants' => null,
                'winner_count' => 0,
                'prizes' => [
                    ['rank' => 'all', 'description' => '$5 Bonus Credits on First $25+ Purchase', 'wallet_amount' => 5.00],
                ],
                'rules' => [
                    'Valid for first-time buyers only',
                    'Minimum $25 purchase required',
                    'Bonus credited within 24 hours',
                    'Cannot be combined with other offers',
                ],
                'requirements' => ['first_purchase_only' => true],
                'is_featured' => false,
            ],

            // ============== UPCOMING EVENTS (BUDGET-FRIENDLY) ==============
            [
                'name' => '🎮 Weekend Warriors Drop Party',
                'slug' => 'weekend-warriors',
                'description' => 'Every weekend we celebrate our community! Join us for prizes and community fun.',
                'type' => 'drop_party',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7',
                'starts_at' => now()->addDays(3),
                'ends_at' => now()->addDays(5),
                'status' => 'upcoming',
                'max_participants' => 100,
                'winner_count' => 20,
                'prizes' => [
                    ['rank' => '1-5', 'description' => '$5 Credits', 'wallet_amount' => 5.00],
                    ['rank' => '6-10', 'description' => '$2.50 Credits', 'wallet_amount' => 2.50],
                    ['rank' => '11-20', 'description' => '$1 Credits', 'wallet_amount' => 1.00],
                ],
                'rules' => [
                    'Be active in Discord during event',
                    'Multiple ways to win throughout weekend',
                    'Follow event announcements',
                    'Community engagement rewarded',
                ],
                'requirements' => null,
                'is_featured' => false,
            ],
            [
                'name' => '🔥 Flash Giveaway Friday',
                'slug' => 'flash-giveaway-friday',
                'description' => 'Quick random giveaways every Friday! Be online and active to win instant prizes.',
                'type' => 'giveaway',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1533090161767-e6ffed986c88',
                'starts_at' => now()->addDays(4),
                'ends_at' => now()->addDays(4)->endOfDay(),
                'status' => 'upcoming',
                'max_participants' => null,
                'winner_count' => 10,
                'prizes' => [
                    ['rank' => '1-10', 'description' => '$5 Credits', 'wallet_amount' => 5.00],
                ],
                'rules' => [
                    'Be active in Discord or site when giveaway announced',
                    'Random selection from active users',
                    'Multiple giveaways throughout the day',
                    'Must claim within 1 hour',
                ],
                'requirements' => null,
                'is_featured' => false,
            ],
            [
                'name' => '📝 Review & Win',
                'slug' => 'review-and-win',
                'description' => 'Leave reviews on your purchases to enter monthly prize draw! Quality feedback gets rewarded.',
                'type' => 'giveaway',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3',
                'starts_at' => now()->addWeek(),
                'ends_at' => now()->addWeek()->endOfMonth(),
                'status' => 'upcoming',
                'max_participants' => null,
                'winner_count' => 5,
                'prizes' => [
                    ['rank' => 1, 'description' => '$25 Credits', 'wallet_amount' => 25.00],
                    ['rank' => '2-5', 'description' => '$10 Credits', 'wallet_amount' => 10.00],
                ],
                'rules' => [
                    'Each review = 1 entry',
                    'Reviews must be legitimate and helpful',
                    'Winners drawn at end of month',
                    'Quality reviews prioritized',
                ],
                'requirements' => ['min_purchases' => 1],
                'is_featured' => false,
            ],

            // ============== DRAFT/INACTIVE EVENTS (FUTURE/LARGER BUDGET) ==============
            [
                'name' => 'New Year Extravaganza',
                'slug' => 'new-year-extravaganza',
                'description' => 'Ring in the new year with our biggest celebration yet! Massive prizes, exclusive items, and surprise drops throughout the event.',
                'type' => 'drop_party',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1467810563316-b5476525c0f9',
                'starts_at' => now()->addWeeks(2),
                'ends_at' => now()->addWeeks(2)->addDays(3),
                'status' => 'draft',
                'max_participants' => 1000,
                'winner_count' => 250,
                'prizes' => [
                    ['rank' => 1, 'description' => 'Ultimate Prize Pack: $50 Value', 'wallet_amount' => 50.00],
                    ['rank' => '2-5', 'description' => '$12.50 Credits + Rare Items', 'wallet_amount' => 12.50],
                    ['rank' => '6-20', 'description' => '$5 Credits', 'wallet_amount' => 5.00],
                    ['rank' => '21-50', 'description' => '$2.50 Credits', 'wallet_amount' => 2.50],
                    ['rank' => '51-100', 'description' => '$1.25 Credits', 'wallet_amount' => 1.25],
                    ['rank' => '101-250', 'description' => '$0.50 Credits', 'wallet_amount' => 0.50],
                ],
                'rules' => [
                    'Multiple prize drops throughout event',
                    'Be active and online to claim',
                    'Special VIP tier benefits',
                    'Bonus entries for referring friends',
                ],
                'requirements' => null,
                'is_featured' => false,
            ],
            [
                'name' => 'PVP Championship Series',
                'slug' => 'pvp-championship',
                'description' => 'Test your skills in the ultimate PVP tournament. Battle it out for glory and incredible prizes!',
                'type' => 'tournament',
                'game_id' => $osrsGame?->id,
                'banner_image' => 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc',
                'starts_at' => now()->addWeek(),
                'ends_at' => now()->addWeek()->addDays(2),
                'status' => 'draft',
                'max_participants' => 64,
                'winner_count' => 8,
                'prizes' => [
                    ['rank' => 1, 'description' => 'Champion Prize: $75 + Trophy', 'wallet_amount' => 75.00],
                    ['rank' => 2, 'description' => 'Runner-up: $37.50', 'wallet_amount' => 37.50],
                    ['rank' => 3, 'description' => 'Third Place: $20', 'wallet_amount' => 20.00],
                    ['rank' => 4, 'description' => 'Semi-Finalist: $10', 'wallet_amount' => 10.00],
                    ['rank' => '5-8', 'description' => 'Quarter-Finalist: $5', 'wallet_amount' => 5.00],
                ],
                'rules' => [
                    'Single elimination bracket',
                    'Must provide valid RSN',
                    'Standard PVP rules apply',
                    'Matches scheduled with participants',
                ],
                'requirements' => ['min_purchases' => 5, 'min_account_age_days' => 60],
                'is_featured' => false,
            ],
            [
                'name' => 'Community Treasure Hunt',
                'slug' => 'community-treasure-hunt',
                'description' => 'Follow the clues, solve the puzzles, and find the hidden treasures! Amazing prizes for the clever hunters.',
                'type' => 'giveaway',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1533158388470-9a56699990c6',
                'starts_at' => now()->addDays(10),
                'ends_at' => now()->addDays(17),
                'status' => 'draft',
                'max_participants' => null,
                'winner_count' => 50,
                'prizes' => [
                    ['rank' => 1, 'description' => 'First Finder: $25 + Exclusive Badge', 'wallet_amount' => 25.00],
                    ['rank' => '2-5', 'description' => '$10 Credits', 'wallet_amount' => 10.00],
                    ['rank' => '6-15', 'description' => '$5 Credits', 'wallet_amount' => 5.00],
                    ['rank' => '16-30', 'description' => '$2.50 Credits', 'wallet_amount' => 2.50],
                    ['rank' => '31-50', 'description' => '$1.25 Credits', 'wallet_amount' => 1.25],
                ],
                'rules' => [
                    'Clues released daily',
                    'First to complete all challenges wins',
                    'Use community resources and forums',
                    'No sharing of solutions',
                ],
                'requirements' => null,
                'is_featured' => false,
            ],
            [
                'name' => 'Black Friday Bonanza',
                'slug' => 'black-friday-bonanza',
                'description' => 'The biggest shopping event of the year! Massive discounts, exclusive deals, and incredible giveaways.',
                'type' => 'giveaway',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1607083206968-13611e3d76db',
                'starts_at' => now()->addMonths(2),
                'ends_at' => now()->addMonths(2)->addDays(3),
                'status' => 'draft',
                'max_participants' => null,
                'winner_count' => 100,
                'prizes' => [
                    ['rank' => 1, 'description' => '$125 Shopping Spree', 'wallet_amount' => 125.00],
                    ['rank' => 2, 'description' => '$75 Credits', 'wallet_amount' => 75.00],
                    ['rank' => 3, 'description' => '$50 Credits', 'wallet_amount' => 50.00],
                    ['rank' => '4-10', 'description' => '$25 Credits', 'wallet_amount' => 25.00],
                    ['rank' => '11-30', 'description' => '$12.50 Credits', 'wallet_amount' => 12.50],
                    ['rank' => '31-50', 'description' => '$5 Credits', 'wallet_amount' => 5.00],
                    ['rank' => '51-100', 'description' => '$2.50 Credits', 'wallet_amount' => 2.50],
                ],
                'rules' => [
                    'Automatic entry with any purchase',
                    'Extra entries for larger purchases',
                    'Winners drawn live on stream',
                    'Must claim prizes within 7 days',
                ],
                'requirements' => null,
                'is_featured' => false,
            ],
            [
                'name' => 'Monthly Seller Spotlight',
                'slug' => 'monthly-seller-spotlight',
                'description' => 'Recognition and rewards for our top sellers! Compete for the top spot and earn exclusive benefits.',
                'type' => 'tournament',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1556740738-b6a63e27c4df',
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->endOfMonth(),
                'status' => 'draft',
                'max_participants' => null,
                'winner_count' => 10,
                'prizes' => [
                    ['rank' => 1, 'description' => 'Top Seller: $50 + Featured Profile', 'wallet_amount' => 50.00],
                    ['rank' => 2, 'description' => '$25 + Featured Profile', 'wallet_amount' => 25.00],
                    ['rank' => 3, 'description' => '$15 + Featured Profile', 'wallet_amount' => 15.00],
                    ['rank' => '4-5', 'description' => '$7.50 Credits', 'wallet_amount' => 7.50],
                    ['rank' => '6-10', 'description' => '$3.75 Credits', 'wallet_amount' => 3.75],
                ],
                'rules' => [
                    'Based on total sales volume',
                    'Must maintain 4.0+ rating',
                    'Quality and customer service matter',
                    'Automatic entry for all sellers',
                ],
                'requirements' => ['is_seller' => true],
                'is_featured' => false,
            ],
        ];

        foreach ($events as $event) {
            Event::create($event);
        }

        $this->command->info('Event seeder completed!');
        $this->command->info('Created ' . count($events) . ' events across different types:');
        $this->command->info('- Drop Parties: Fun community drops with prizes');
        $this->command->info('- Tournaments: Competitive events with rankings');
        $this->command->info('- Giveaways: Random winner selections');
    }
}
