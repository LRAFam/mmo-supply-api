<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Order;
use Illuminate\Console\Command;

class BackfillOrderConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:backfill-conversations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create conversations for orders that don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Finding orders without conversations...');

        // Get all orders that don't have a conversation
        $ordersWithoutConversation = Order::whereDoesntHave('conversation')
            ->where('seller_id', '!=', null)  // Only orders with a seller
            ->get();

        if ($ordersWithoutConversation->isEmpty()) {
            $this->info('✅ All orders already have conversations!');
            return 0;
        }

        $this->info("Found {$ordersWithoutConversation->count()} orders without conversations");

        $progressBar = $this->output->createProgressBar($ordersWithoutConversation->count());
        $progressBar->start();

        $created = 0;
        $skipped = 0;

        foreach ($ordersWithoutConversation as $order) {
            try {
                // Create conversation for this order
                Conversation::findOrCreate(
                    $order->user_id,
                    $order->seller_id,
                    $order->id
                );

                $created++;
            } catch (\Exception $e) {
                $this->error("\n❌ Failed to create conversation for order #{$order->order_number}: {$e->getMessage()}");
                $skipped++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->newLine(2);
        $this->info("✅ Successfully created {$created} conversations");

        if ($skipped > 0) {
            $this->warn("⚠️  Skipped {$skipped} orders due to errors");
        }

        return 0;
    }
}
