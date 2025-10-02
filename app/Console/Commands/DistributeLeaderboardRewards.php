<?php

namespace App\Console\Commands;

use App\Models\LeaderboardReward;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DistributeLeaderboardRewards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaderboard:distribute-rewards {period=monthly}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Distribute rewards to top sellers for completed period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $period = $this->argument('period'); // 'weekly' or 'monthly'

        if (!in_array($period, ['weekly', 'monthly'])) {
            $this->error('Period must be "weekly" or "monthly"');
            return Command::FAILURE;
        }

        $this->info("Starting {$period} leaderboard reward distribution...");

        // Get the previous period's date range
        $dates = $this->getPreviousPeriodDates($period);
        $periodStart = $dates['start'];
        $periodEnd = $dates['end'];

        $this->info("Period: {$periodStart->toDateString()} to {$periodEnd->toDateString()}");

        // Check if rewards already distributed for this period
        $existingRewards = LeaderboardReward::where('period', $period)
            ->where('period_start', $periodStart)
            ->exists();

        if ($existingRewards) {
            $this->warn("Rewards already distributed for this period. Skipping.");
            return Command::SUCCESS;
        }

        // Get top sellers (we'll use a snapshot approach - monthly_sales before reset)
        // For proper implementation, you'd track period sales separately
        $topSellers = User::where('is_seller', true)
            ->where('monthly_sales', '>', 0)
            ->orderBy('monthly_sales', 'desc')
            ->limit(50)
            ->get();

        if ($topSellers->isEmpty()) {
            $this->info('No sellers with sales to reward.');
            return Command::SUCCESS;
        }

        $this->info("Processing {$topSellers->count()} top sellers...");

        $progressBar = $this->output->createProgressBar($topSellers->count());
        $progressBar->start();

        $totalRewarded = 0;
        $rewardsDistributed = 0;

        foreach ($topSellers as $index => $seller) {
            $rank = $index + 1;
            $reward = $this->getRewardForRank($rank, $period);

            if ($reward['amount'] > 0) {
                // Create reward record
                $leaderboardReward = LeaderboardReward::create([
                    'user_id' => $seller->id,
                    'period' => $period,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'rank' => $rank,
                    'sales_amount' => $seller->monthly_sales,
                    'reward_amount' => $reward['amount'],
                    'badge' => $reward['badge'],
                    'credited' => false,
                ]);

                // Credit reward to wallet
                $wallet = $seller->getOrCreateWallet();
                $wallet->credit($reward['amount'], null, "Leaderboard reward - {$period} #{$rank}");

                // Mark as credited
                $leaderboardReward->update([
                    'credited' => true,
                    'credited_at' => now(),
                ]);

                $totalRewarded += $reward['amount'];
                $rewardsDistributed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Reward distribution complete!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Period', ucfirst($period)],
                ['Date Range', "{$periodStart->toDateString()} to {$periodEnd->toDateString()}"],
                ['Sellers Rewarded', $rewardsDistributed],
                ['Total Amount Distributed', '$' . number_format($totalRewarded, 2)],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Get previous period date ranges
     */
    private function getPreviousPeriodDates(string $period): array
    {
        if ($period === 'weekly') {
            $start = now()->subWeek()->startOfWeek();
            $end = now()->subWeek()->endOfWeek();
        } else {
            // Monthly
            $start = now()->subMonth()->startOfMonth();
            $end = now()->subMonth()->endOfMonth();
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Get reward amount and badge for a given rank
     */
    private function getRewardForRank(int $rank, string $period): array
    {
        $rewards = $this->getRewardStructure($period);

        foreach ($rewards as $reward) {
            if ($rank >= $reward['rank_start'] && $rank <= $reward['rank_end']) {
                return [
                    'amount' => $reward['amount'],
                    'badge' => $reward['badge'],
                ];
            }
        }

        return ['amount' => 0, 'badge' => null];
    }

    /**
     * Get reward structure for period
     */
    private function getRewardStructure(string $period): array
    {
        if ($period === 'weekly') {
            return [
                ['rank_start' => 1, 'rank_end' => 1, 'amount' => 5.00, 'badge' => 'gold'],
                ['rank_start' => 2, 'rank_end' => 2, 'amount' => 2.50, 'badge' => 'silver'],
                ['rank_start' => 3, 'rank_end' => 3, 'amount' => 1.00, 'badge' => 'bronze'],
                ['rank_start' => 4, 'rank_end' => 10, 'amount' => 0.50, 'badge' => null],
            ];
        }

        // Monthly rewards (bigger prizes)
        return [
            ['rank_start' => 1, 'rank_end' => 1, 'amount' => 25.00, 'badge' => 'gold'],
            ['rank_start' => 2, 'rank_end' => 2, 'amount' => 15.00, 'badge' => 'silver'],
            ['rank_start' => 3, 'rank_end' => 3, 'amount' => 7.50, 'badge' => 'bronze'],
            ['rank_start' => 4, 'rank_end' => 5, 'amount' => 3.00, 'badge' => null],
            ['rank_start' => 6, 'rank_end' => 10, 'amount' => 2.00, 'badge' => null],
            ['rank_start' => 11, 'rank_end' => 20, 'amount' => 1.00, 'badge' => null],
        ];
    }
}
