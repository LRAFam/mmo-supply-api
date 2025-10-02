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
            // ACTIVE EVENTS
            [
                'name' => 'Summer Mega Drop Party',
                'slug' => 'summer-mega-drop-party',
                'description' => 'Join our biggest drop party of the year! We\'re dropping millions worth of gold, items, and accounts. First come, first served!',
                'type' => 'drop_party',
                'game_id' => $osrsGame?->id,
                'banner_image' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420',
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(5),
                'status' => 'active',
                'max_participants' => 500,
                'winner_count' => 100,
                'prizes' => [
                    ['rank' => 1, 'description' => '1M OSRS Gold + Rare Item', 'wallet_amount' => 5.00],
                    ['rank' => 2, 'description' => '750K OSRS Gold', 'wallet_amount' => 3.75],
                    ['rank' => 3, 'description' => '500K OSRS Gold', 'wallet_amount' => 2.50],
                    ['rank' => '4-10', 'description' => '250K OSRS Gold', 'wallet_amount' => 1.25],
                    ['rank' => '11-50', 'description' => '100K OSRS Gold', 'wallet_amount' => 0.50],
                    ['rank' => '51-100', 'description' => '50K OSRS Gold', 'wallet_amount' => 0.25],
                ],
                'rules' => [
                    'Be online at the designated time',
                    'First to claim gets the prize',
                    'One prize per person',
                    'Must be verified account to participate',
                ],
                'requirements' => ['min_purchases' => 1],
                'is_featured' => true,
            ],
            [
                'name' => 'Weekly Trading Tournament',
                'slug' => 'weekly-trading-tournament',
                'description' => 'Compete against other traders to make the most profit! Top traders win cash prizes and marketplace credits.',
                'type' => 'tournament',
                'game_id' => null, // All games
                'banner_image' => 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(6),
                'status' => 'active',
                'max_participants' => 200,
                'winner_count' => 20,
                'prizes' => [
                    ['rank' => 1, 'description' => 'Grand Prize: $25 + $5 Credits', 'wallet_amount' => 30.00],
                    ['rank' => 2, 'description' => '$12.50 + $3.75 Credits', 'wallet_amount' => 16.25],
                    ['rank' => 3, 'description' => '$7.50 + $2.50 Credits', 'wallet_amount' => 10.00],
                    ['rank' => '4-5', 'description' => '$3.75 + $1.25 Credits', 'wallet_amount' => 5.00],
                    ['rank' => '6-10', 'description' => '$2.50 Credits', 'wallet_amount' => 2.50],
                    ['rank' => '11-20', 'description' => '$1.25 Credits', 'wallet_amount' => 1.25],
                ],
                'rules' => [
                    'Score based on total sales made during event',
                    'Only verified sellers can participate',
                    'Must maintain 4.5+ rating',
                    'No cheating or fake sales',
                ],
                'requirements' => ['is_seller' => true, 'min_account_age_days' => 30],
                'is_featured' => true,
            ],
            [
                'name' => 'Flash Giveaway: 10M OSRS Gold',
                'slug' => 'flash-giveaway-osrs',
                'description' => '🔥 FLASH GIVEAWAY! 10 Million OSRS Gold split between 20 lucky winners. Enter now - ends in 24 hours!',
                'type' => 'giveaway',
                'game_id' => $osrsGame?->id,
                'banner_image' => 'https://images.unsplash.com/photo-1614680376593-902f74cf0d41',
                'starts_at' => now()->subHours(3),
                'ends_at' => now()->addHours(21),
                'status' => 'active',
                'max_participants' => null, // Unlimited
                'winner_count' => 20,
                'prizes' => [
                    ['rank' => '1-5', 'description' => '750K OSRS Gold', 'wallet_amount' => 3.75],
                    ['rank' => '6-10', 'description' => '500K OSRS Gold', 'wallet_amount' => 2.50],
                    ['rank' => '11-20', 'description' => '250K OSRS Gold', 'wallet_amount' => 1.25],
                ],
                'rules' => [
                    'Random winners selected at event end',
                    'Must be active account (1+ purchases)',
                    'Winners will be contacted via email',
                    'Prizes distributed within 24 hours',
                ],
                'requirements' => ['min_purchases' => 1],
                'is_featured' => false,
            ],

            // UPCOMING EVENTS
            [
                'name' => 'New Year Extravaganza',
                'slug' => 'new-year-extravaganza',
                'description' => 'Ring in the new year with our biggest celebration yet! Massive prizes, exclusive items, and surprise drops throughout the event.',
                'type' => 'drop_party',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1467810563316-b5476525c0f9',
                'starts_at' => now()->addWeeks(2),
                'ends_at' => now()->addWeeks(2)->addDays(3),
                'status' => 'upcoming',
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
                'is_featured' => true,
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
                'status' => 'upcoming',
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
                'is_featured' => true,
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
                'status' => 'upcoming',
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

            // MORE VARIETY
            [
                'name' => 'Weekend Warriors Drop Party',
                'slug' => 'weekend-warriors',
                'description' => 'Every weekend we celebrate our community! Join us for hourly drops and prizes all weekend long.',
                'type' => 'drop_party',
                'game_id' => null,
                'banner_image' => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7',
                'starts_at' => now()->addDays(3),
                'ends_at' => now()->addDays(5),
                'status' => 'upcoming',
                'max_participants' => 300,
                'winner_count' => 75,
                'prizes' => [
                    ['rank' => '1-25', 'description' => '$2.50 Credits + Items', 'wallet_amount' => 2.50],
                    ['rank' => '26-50', 'description' => '$1.25 Credits', 'wallet_amount' => 1.25],
                    ['rank' => '51-75', 'description' => '$0.50 Credits', 'wallet_amount' => 0.50],
                ],
                'rules' => [
                    'Drops happen every hour',
                    'Be online to participate',
                    'Multiple prizes per person allowed',
                    'Follow event chat for drop announcements',
                ],
                'requirements' => ['min_purchases' => 1],
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
                'status' => 'upcoming',
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
                'is_featured' => true,
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
                'status' => 'active',
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
