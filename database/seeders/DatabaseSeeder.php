<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Game;
use App\Models\Item;
use App\Models\Currency;
use App\Models\Account;
use App\Models\Service;
use App\Models\Provider;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users (or get existing ones)
        $buyer = User::firstOrCreate(
            ['email' => 'buyer@example.com'],
            [
                'name' => 'Test Buyer',
                'password' => Hash::make('password'),
                'is_seller' => false,
            ]
        );

        $seller1 = User::firstOrCreate(
            ['email' => 'seller1@example.com'],
            [
                'name' => 'RuneScape Pro Seller',
                'password' => Hash::make('password'),
                'is_seller' => true,
                'bio' => 'Trusted RuneScape gold and items seller since 2020. Fast delivery guaranteed!',
            ]
        );

        $seller2 = User::firstOrCreate(
            ['email' => 'seller2@example.com'],
            [
                'name' => 'OSRS Master',
                'password' => Hash::make('password'),
                'is_seller' => true,
                'bio' => 'Specializing in rare items and powerleveling services.',
            ]
        );

        // Create wallets for all users (or get existing ones)
        Wallet::firstOrCreate(
            ['user_id' => $buyer->id],
            [
                'balance' => 100.00, // Give buyer some starting balance
                'pending_balance' => 0,
                'currency' => 'USD',
                'is_active' => true,
            ]
        );

        Wallet::firstOrCreate(
            ['user_id' => $seller1->id],
            [
                'balance' => 250.00,
                'pending_balance' => 0,
                'currency' => 'USD',
                'is_active' => true,
            ]
        );

        Wallet::firstOrCreate(
            ['user_id' => $seller2->id],
            [
                'balance' => 180.00,
                'pending_balance' => 0,
                'currency' => 'USD',
                'is_active' => true,
            ]
        );

        // Create Games (or get existing ones)
        $osrs = Game::firstOrCreate(
            ['slug' => 'old-school-runescape'],
            [
                'title' => 'Old School RuneScape',
                'description' => 'The iconic MMORPG that started it all. Trade, quest, and skill your way to glory in Gielinor.',
                'logo' => '01J8QZF4F4D1MSE11JS4S0CBG8.png',
                'is_active' => true,
            ]
        );

        $rs3 = Game::firstOrCreate(
            ['slug' => 'runescape-3'],
            [
                'title' => 'RuneScape 3',
                'description' => 'The modern evolution of RuneScape with enhanced graphics and new combat system.',
                'logo' => null,
                'is_active' => true,
            ]
        );

        $wow = Game::firstOrCreate(
            ['slug' => 'world-of-warcraft'],
            [
                'title' => 'World of Warcraft',
                'description' => 'The legendary MMORPG from Blizzard. Trade gold, mounts, and boost your character to new heights.',
                'logo' => null,
                'is_active' => true,
            ]
        );

        $valorant = Game::firstOrCreate(['slug' => 'valorant'], [
            'title' => 'Valorant',
            'description' => 'Riot\'s tactical 5v5 shooter. Buy accounts with rare skins, radianite points, and competitive ranks.',
            'logo' => null,
            'is_active' => true,
        ]);

        $minecraft = Game::firstOrCreate(['slug' => 'minecraft'], [
            'title' => 'Minecraft',
            'description' => 'The world\'s most popular sandbox game. Trade accounts, server ranks, and in-game items.',
            'logo' => null,
            'is_active' => true,
        ]);

        $lol = Game::firstOrCreate(['slug' => 'league-of-legends'], [
            'title' => 'League of Legends',
            'description' => 'The most played MOBA in the world. Buy accounts with rare skins, blue essence, and ranked tiers.',
            'logo' => null,
            'is_active' => true,
        ]);

        $csgo = Game::firstOrCreate(['slug' => 'counter-strike-2'], [
            'title' => 'CS2 (Counter-Strike 2)',
            'description' => 'The legendary FPS esport. Trade skins, accounts, and Prime status.',
            'logo' => null,
            'is_active' => true,
        ]);

        $dota2 = Game::firstOrCreate(['slug' => 'dota-2'], [
            'title' => 'Dota 2',
            'description' => 'Valve\'s premier MOBA. Trade cosmetics, arcanas, and ranked accounts.',
            'logo' => null,
            'is_active' => true,
        ]);

        $fortnite = Game::firstOrCreate(['slug' => 'fortnite'], [
            'title' => 'Fortnite',
            'description' => 'Epic\'s battle royale phenomenon. Buy accounts with rare skins and save the world progress.',
            'logo' => null,
            'is_active' => true,
        ]);

        $genshin = Game::firstOrCreate(['slug' => 'genshin-impact'], [
            'title' => 'Genshin Impact',
            'description' => 'miHoYo\'s open-world action RPG. Trade accounts with 5-star characters and primogems.',
            'logo' => null,
            'is_active' => true,
        ]);

        $albion = Game::firstOrCreate(['slug' => 'albion-online'], [
            'title' => 'Albion Online',
            'description' => 'Player-driven sandbox MMORPG. Trade silver, gear, and premium accounts.',
            'logo' => null,
            'is_active' => true,
        ]);

        $ffxiv = Game::firstOrCreate(['slug' => 'final-fantasy-xiv'], [
            'title' => 'Final Fantasy XIV',
            'description' => 'Square Enix\'s critically acclaimed MMORPG. Trade gil, mounts, and leveling services.',
            'logo' => null,
            'is_active' => true,
        ]);

        $eso = Game::firstOrCreate(['slug' => 'elder-scrolls-online'], [
            'title' => 'Elder Scrolls Online',
            'description' => 'Explore Tamriel in this massive MMORPG. Trade gold, crowns, and rare collectibles.',
            'logo' => null,
            'is_active' => true,
        ]);

        $bdo = Game::firstOrCreate(['slug' => 'black-desert-online'], [
            'title' => 'Black Desert Online',
            'description' => 'Action-packed MMORPG with stunning graphics. Trade silver, pearl items, and enhanced gear.',
            'logo' => null,
            'is_active' => true,
        ]);

        $lostark = Game::firstOrCreate(['slug' => 'lost-ark'], [
            'title' => 'Lost Ark',
            'description' => 'Amazon\'s popular action RPG. Trade gold, skins, and powerleveling services.',
            'logo' => null,
            'is_active' => true,
        ]);

        $destiny2 = Game::firstOrCreate(['slug' => 'destiny-2'], [
            'title' => 'Destiny 2',
            'description' => 'Bungie\'s looter shooter MMO. Trade carries, exotic weapons, and seasonal rewards.',
            'logo' => null,
            'is_active' => true,
        ]);

        $poe = Game::firstOrCreate(['slug' => 'path-of-exile'], [
            'title' => 'Path of Exile',
            'description' => 'Deep action RPG with extensive character customization. Trade currency, uniques, and services.',
            'logo' => null,
            'is_active' => true,
        ]);

        $rocket = Game::firstOrCreate(['slug' => 'rocket-league'], [
            'title' => 'Rocket League',
            'description' => 'Soccer meets cars in this competitive sports game. Trade credits, items, and blueprints.',
            'logo' => null,
            'is_active' => true,
        ]);

        $apex = Game::firstOrCreate(['slug' => 'apex-legends'], [
            'title' => 'Apex Legends',
            'description' => 'Fast-paced battle royale from Respawn. Trade accounts with heirlooms and legendary skins.',
            'logo' => null,
            'is_active' => true,
        ]);

        $tarkov = Game::firstOrCreate(['slug' => 'escape-from-tarkov'], [
            'title' => 'Escape from Tarkov',
            'description' => 'Hardcore tactical FPS. Trade roubles, rare items, and leveling services.',
            'logo' => null,
            'is_active' => true,
        ]);

        $gw2 = Game::firstOrCreate(['slug' => 'guild-wars-2'], [
            'title' => 'Guild Wars 2',
            'description' => 'ArenaNet\'s dynamic MMORPG. Trade gold, gems, and legendary weapons.',
            'logo' => null,
            'is_active' => true,
        ]);

        // Create Currencies
        Currency::create([
            'user_id' => $seller1->id,
            'game_id' => $osrs->id,
            'name' => 'OSRS Gold',
            'slug' => 'osrs-gold-100m',
            'description' => 'Old School RuneScape gold. Instant delivery via trade. Minimum order 10M, maximum 500M per transaction.',
            'rate' => 0.50,
            'price_per_unit' => 0.50,
            'stock' => 500, // 500 units available
            'min_amount' => 10, // 10M minimum
            'max_amount' => 500, // 500M maximum
            'is_active' => true,
        ]);

        Currency::create([
            'user_id' => $seller2->id,
            'game_id' => $osrs->id,
            'name' => 'OSRS Gold - Budget',
            'slug' => 'osrs-gold-budget',
            'description' => 'Cheapest OSRS gold on the market. Safe and fast delivery. Bulk orders welcome!',
            'rate' => 0.52,
            'price_per_unit' => 0.52,
            'stock' => 1000, // 1000 units available
            'min_amount' => 5, // 5M minimum
            'max_amount' => 1000, // 1B maximum
            'is_active' => true,
        ]);

        Currency::create([
            'user_id' => $seller1->id,
            'game_id' => $rs3->id,
            'name' => 'RS3 Gold',
            'slug' => 'rs3-gold',
            'description' => 'RuneScape 3 gold for all your needs. Bulk orders welcome. Fast delivery guaranteed.',
            'rate' => 0.15,
            'price_per_unit' => 0.15,
            'stock' => 2500, // 2500 units available
            'min_amount' => 100, // 100M minimum
            'max_amount' => 999999, // Large max within int range
            'is_active' => true,
        ]);

        // Create Items
        Item::create([
            'user_id' => $seller1->id,
            'game_id' => $osrs->id,
            'name' => 'Twisted Bow',
            'slug' => 'twisted-bow',
            'description' => 'The most powerful ranged weapon in OSRS. Perfect for end-game PvM content.',
            'content' => 'Comes with full authentication and safety guarantee. Price negotiable for bulk orders.',
            'images' => ['twisted-bow.png'],
            'price' => 1200.00,
            'discount' => 50.00,
            'stock' => 3,
            'is_active' => true,
            'is_featured' => true,
            'delivery_time' => '1-2 hours',
        ]);

        Item::create([
            'user_id' => $seller2->id,
            'game_id' => $osrs->id,
            'name' => 'Scythe of Vitur',
            'slug' => 'scythe-of-vitur',
            'description' => 'Best in slot melee weapon for most bosses. Extremely rare drop from Theatre of Blood.',
            'content' => 'Fully charged with 100% durability. Immediate delivery available.',
            'images' => null,
            'price' => 850.00,
            'discount' => null,
            'stock' => 2,
            'is_active' => true,
            'delivery_time' => '30 minutes',
        ]);

        Item::create([
            'user_id' => $seller1->id,
            'game_id' => $osrs->id,
            'name' => 'Elysian Spirit Shield',
            'slug' => 'elysian-spirit-shield',
            'description' => 'The best defensive shield in the game with damage reduction effect.',
            'content' => 'Rare drop from Corporeal Beast. Perfect for tanking.',
            'images' => null,
            'price' => 650.00,
            'stock' => 5,
            'is_active' => true,
            'delivery_time' => '1 hour',
        ]);

        Item::create([
            'user_id' => $seller2->id,
            'game_id' => $osrs->id,
            'name' => 'Third Age Platebody',
            'slug' => 'third-age-platebody',
            'description' => 'Ultra rare cosmetic armor piece from Treasure Trails.',
            'content' => 'One of the rarest items in OSRS. Perfect for collectors and fashionscape.',
            'images' => null,
            'price' => 2100.00,
            'discount' => 100.00,
            'stock' => 1,
            'is_active' => true,
            'is_featured' => true,
            'delivery_time' => '2-4 hours',
        ]);

        Item::create([
            'user_id' => $seller1->id,
            'game_id' => $rs3->id,
            'name' => 'Party Hat Set',
            'slug' => 'party-hat-set-rs3',
            'description' => 'Complete set of all 6 party hats (Red, Yellow, Green, Blue, White, Purple).',
            'content' => 'The most iconic discontinued items in RuneScape history. Ultimate status symbol.',
            'images' => null,
            'price' => 45000.00,
            'stock' => 1,
            'is_active' => true,
            'is_featured' => true,
            'delivery_time' => '24 hours',
        ]);

        // Create Accounts
        Account::create([
            'user_id' => $seller1->id,
            'game_id' => $osrs->id,
            'title' => 'Maxed Pure Account (75 Attack)',
            'slug' => 'maxed-pure-75-attack',
            'description' => 'Perfect pure account with 75 attack, 99 strength, 99 ranged, 99 magic. Ready for PvP.',
            'content' => 'Full void, fire cape, dragon defender. 1850+ total level. Excellent stats for hybrid PKing.',
            'images' => null,
            'price' => 350.00,
            'discount' => 30.00,
            'account_level' => '98',
            'account_stats' => json_encode([
                'combat_level' => 98,
                'total_level' => 1850,
                'attack' => 75,
                'strength' => 99,
                'defence' => 1,
                'ranged' => 99,
                'magic' => 99,
                'prayer' => 52,
            ]),
            'stock' => 1,
            'is_active' => true,
            'is_featured' => true,
        ]);

        Account::create([
            'user_id' => $seller2->id,
            'game_id' => $osrs->id,
            'title' => 'Max Main Account - 2277 Total',
            'slug' => 'max-main-2277',
            'description' => 'Fully maxed account with all 99s. Quest cape, achievement diary cape, and more.',
            'content' => '2277 total level, all quests completed, extensive bank with 5B+ worth. Full access guaranteed.',
            'images' => null,
            'price' => 1800.00,
            'stock' => 1,
            'account_level' => '126',
            'account_stats' => json_encode([
                'combat_level' => 126,
                'total_level' => 2277,
                'quests_completed' => 156,
                'achievement_diaries' => 'All Elite',
            ]),
            'is_active' => true,
            'is_featured' => true,
        ]);

        Account::create([
            'user_id' => $seller1->id,
            'game_id' => $osrs->id,
            'title' => 'Zerker Account - 45 Def Pure',
            'slug' => 'zerker-45-def',
            'description' => 'Berserker pure with 45 defence, 94 magic, 99 strength. Perfect for PvP.',
            'content' => 'Barrows gloves, fire cape, vengeance unlocked. Great for edge PKing and bounty hunter.',
            'images' => null,
            'price' => 280.00,
            'stock' => 2,
            'account_level' => '88',
            'account_stats' => json_encode([
                'combat_level' => 88,
                'total_level' => 1456,
                'attack' => 75,
                'strength' => 99,
                'defence' => 45,
                'ranged' => 92,
                'magic' => 94,
            ]),
            'is_active' => true,
        ]);

        Account::create([
            'user_id' => $seller2->id,
            'game_id' => $rs3->id,
            'title' => 'RS3 Maxed Account with Comp Cape',
            'slug' => 'rs3-maxed-comp-cape',
            'description' => 'Completionist cape account with 120s in combat stats. End-game ready.',
            'content' => '3B+ bank value, tier 92 weapons, full elite gear. Perfect for high level PvM.',
            'images' => null,
            'price' => 950.00,
            'stock' => 1,
            'account_level' => 'Maxed (Comp)',
            'account_stats' => json_encode([
                'combat_level' => 138,
                'total_level' => 2772,
                'bank_value' => '3B+',
                'comp_cape' => true,
            ]),
            'is_active' => true,
        ]);

        // Create Services
        Service::create([
            'user_id' => $seller1->id,
            'game_id' => $osrs->id,
            'title' => 'Fire Cape Service',
            'slug' => 'fire-cape-service',
            'description' => 'Professional Jad killing service. Get your fire cape with guaranteed success.',
            'content' => 'We will complete Fight Caves on your account. Average completion time: 1-2 hours. 100% success rate.',
            'images' => null,
            'price' => 25.00,
            'discount' => 5.00,
            'estimated_time' => '1-2 hours',
            'is_active' => true,
            'is_featured' => true,
        ]);

        Service::create([
            'user_id' => $seller2->id,
            'game_id' => $osrs->id,
            'title' => 'Inferno Cape Service',
            'slug' => 'inferno-cape-service',
            'description' => 'Expert Zuk killers. Get the best cape in the game on your account.',
            'content' => 'Professional completion of The Inferno. Requires 75+ range and rigour. Completion time: 3-8 hours depending on gear.',
            'images' => null,
            'price' => 450.00,
            'discount' => 0,
            'estimated_time' => '3-8 hours',
            'is_active' => true,
            'is_featured' => true,
        ]);

        Service::create([
            'user_id' => $seller1->id,
            'game_id' => $osrs->id,
            'title' => 'Quest Cape Service',
            'slug' => 'quest-cape-service',
            'description' => 'Complete all quests for the prestigious Quest Cape. Save hundreds of hours.',
            'content' => 'We will complete all 156+ quests on your account. Estimated time: 40-60 hours depending on current progress.',
            'images' => null,
            'price' => 180.00,
            'discount' => 0,
            'estimated_time' => '40-60 hours',
            'is_active' => true,
        ]);

        Service::create([
            'user_id' => $seller2->id,
            'game_id' => $osrs->id,
            'title' => 'Power Leveling - Combat 1-99',
            'slug' => 'powerleveling-combat-99',
            'description' => 'Fast and safe combat training service. Choose your preferred combat style.',
            'content' => 'Professional training at Nightmare Zone or slayer. Includes imbued rings and points. Price per 99: starting from $80.',
            'images' => null,
            'price' => 80.00,
            'discount' => 0,
            'estimated_time' => '5-10 days',
            'is_active' => true,
        ]);

        Service::create([
            'user_id' => $seller1->id,
            'game_id' => $osrs->id,
            'title' => 'TOB Carry - Scythe Split',
            'slug' => 'tob-carry-scythe',
            'description' => 'Theatre of Blood carry service. Learn the raid or AFK for drops.',
            'content' => '4 experienced players will carry you through TOB. Purple splits available. 10 KC minimum package: $150.',
            'images' => null,
            'price' => 150.00,
            'discount' => 20.00,
            'estimated_time' => '3-5 hours',
            'is_active' => true,
        ]);

        Service::create([
            'user_id' => $seller2->id,
            'game_id' => $rs3->id,
            'title' => 'Telos 0-4000% Enrage',
            'slug' => 'telos-high-enrage',
            'description' => 'Professional Telos farming service. Get rare drops and learn mechanics.',
            'content' => 'Expert Telos player will farm kills on your account. Loot stays on account. Split rare drops optional.',
            'images' => null,
            'price' => 200.00,
            'discount' => 0,
            'estimated_time' => '10-20 hours',
            'is_active' => true,
        ]);

        // Create Providers
        Provider::create([
            'user_id' => $seller1->id,
            'game_id' => $osrs->id,
            'vouches' => 247,
            'rating' => 4.9,
            'total_sales' => 1450,
            'is_verified' => true,
        ]);

        Provider::create([
            'user_id' => $seller2->id,
            'game_id' => $osrs->id,
            'vouches' => 189,
            'rating' => 4.8,
            'total_sales' => 982,
            'is_verified' => true,
        ]);

        Provider::create([
            'user_id' => $seller1->id,
            'game_id' => $rs3->id,
            'vouches' => 156,
            'rating' => 4.7,
            'total_sales' => 743,
            'is_verified' => true,
        ]);

        Provider::create([
            'user_id' => $seller2->id,
            'game_id' => $rs3->id,
            'vouches' => 203,
            'rating' => 4.9,
            'total_sales' => 1124,
            'is_verified' => true,
        ]);

        // Seed spin wheels and prizes
        $this->call(SpinWheelSeeder::class);

        $this->command->info('Database seeded with RuneScape data!');
    }
}
