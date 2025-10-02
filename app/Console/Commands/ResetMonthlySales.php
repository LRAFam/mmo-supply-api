<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetMonthlySales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:reset-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset monthly sales for all providers and recalculate tiers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting monthly sales reset...');

        // Get all providers (sellers)
        $providers = User::where('is_seller', true)->get();

        $this->info("Found {$providers->count()} providers to process.");

        $progressBar = $this->output->createProgressBar($providers->count());
        $progressBar->start();

        $upgraded = 0;
        $downgraded = 0;
        $unchanged = 0;

        foreach ($providers as $provider) {
            $oldTier = $provider->auto_tier ?? 'standard';

            // Reset monthly sales
            $provider->resetMonthlySales();

            $newTier = $provider->fresh()->auto_tier ?? 'standard';

            if ($newTier !== $oldTier) {
                if ($this->tierRank($newTier) > $this->tierRank($oldTier)) {
                    $upgraded++;
                    $this->newLine();
                    $this->line("  ✓ {$provider->name} upgraded: {$oldTier} → {$newTier}");
                } else {
                    $downgraded++;
                    $this->newLine();
                    $this->line("  ↓ {$provider->name} downgraded: {$oldTier} → {$newTier}");
                }
            } else {
                $unchanged++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Monthly sales reset complete!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Upgraded', $upgraded],
                ['Downgraded', $downgraded],
                ['Unchanged', $unchanged],
                ['Total', $providers->count()],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Get tier rank for comparison
     */
    private function tierRank(string $tier): int
    {
        return match($tier) {
            'premium' => 3,
            'verified' => 2,
            default => 1,
        };
    }
}
