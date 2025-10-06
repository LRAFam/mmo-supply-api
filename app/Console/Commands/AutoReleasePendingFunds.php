<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use Illuminate\Console\Command;

class AutoReleasePendingFunds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-release-funds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically release funds to sellers after 72 hours if buyer has not confirmed delivery';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for orders eligible for auto-release...');

        // Get all delivered items where:
        // 1. Status is 'delivered'
        // 2. Funds not yet released
        // 3. Auto-release time has passed
        // 4. Buyer has not confirmed
        $eligibleItems = OrderItem::with(['seller', 'order.buyer'])
            ->where('status', 'delivered')
            ->where('funds_released', false)
            ->whereNotNull('auto_release_at')
            ->where('auto_release_at', '<=', now())
            ->where('buyer_confirmed', false)
            ->get();

        $this->info(sprintf('Found %d items eligible for auto-release', $eligibleItems->count()));

        foreach ($eligibleItems as $item) {
            try {
                $seller = $item->seller;

                if (!$seller) {
                    $this->warn(sprintf('Skipping item #%d - seller not found', $item->id));
                    continue;
                }

                // Calculate earnings
                $feePercentage = $seller->getPlatformFeePercentage();
                $platformFee = $item->total * ($feePercentage / 100);
                $sellerEarnings = $item->total - $platformFee;

                // Credit seller wallet
                $sellerWallet = $seller->getOrCreateWallet();
                $sellerWallet->receiveSale($sellerEarnings, $item->order_id);

                // Track sale for tier progression
                $seller->addSale($item->total);

                // Mark as released and auto-released
                $item->update([
                    'funds_released' => true,
                    'funds_released_at' => now(),
                    'auto_released' => true,
                    'buyer_confirmed' => true, // Auto-confirm
                    'buyer_confirmed_at' => now(),
                    'buyer_confirmation_notes' => 'Auto-confirmed after 72 hours',
                ]);

                $this->info(sprintf(
                    'Released $%.2f to seller #%d for order item #%d (auto-released)',
                    $sellerEarnings,
                    $seller->id,
                    $item->id
                ));

            } catch (\Exception $e) {
                $this->error(sprintf(
                    'Failed to auto-release funds for item #%d: %s',
                    $item->id,
                    $e->getMessage()
                ));
            }
        }

        $this->info('Auto-release complete!');

        return Command::SUCCESS;
    }
}
