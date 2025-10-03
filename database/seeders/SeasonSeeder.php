<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Season::create([
            'season_number' => 1,
            'name' => 'The Pioneers',
            'description' => 'The inaugural season of MMO Supply - join the first wave of sellers and buyers building the future of gaming marketplaces.',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->addMonths(3)->endOfMonth(),
            'status' => 'active',
            'prize_pool' => 47.50,
            'features' => [
                'First season achievements',
                'Monthly leaderboard prizes',
                'Daily spin wheel rewards',
                'Pioneer badge for participants',
            ],
        ]);
    }
}
