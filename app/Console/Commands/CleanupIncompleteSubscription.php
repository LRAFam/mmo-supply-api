<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;

class CleanupIncompleteSubscription extends Command
{
    protected $signature = 'subscription:cleanup {email_or_customer_id}';
    protected $description = 'Delete incomplete subscriptions for a user';

    public function handle()
    {
        $identifier = $this->argument('email_or_customer_id');

        $user = User::where('email', $identifier)
            ->orWhere('stripe_id', $identifier)
            ->orWhere('stripe_customer_id', $identifier)
            ->first();

        if (!$user) {
            $this->error("User not found: {$identifier}");
            return 1;
        }

        $this->info("Cleaning up subscriptions for {$user->name} ({$user->email})...");

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $subscriptions = $user->subscriptions()->get();

            if ($subscriptions->isEmpty()) {
                $this->info('No subscriptions found in database.');
                return 0;
            }

            DB::beginTransaction();

            foreach ($subscriptions as $subscription) {
                $this->info("Found subscription ID: {$subscription->id} (Stripe: {$subscription->stripe_id}, Status: {$subscription->stripe_status})");

                // Delete from Stripe
                if ($subscription->stripe_id) {
                    try {
                        $stripeSubscription = \Stripe\Subscription::retrieve($subscription->stripe_id);
                        $stripeSubscription->cancel();
                        $this->info('✓ Canceled in Stripe');
                    } catch (\Exception $e) {
                        $this->warn("Couldn't delete from Stripe: " . $e->getMessage());
                    }
                }

                // Delete subscription items
                $subscription->items()->delete();
                // Delete subscription
                $subscription->delete();
                $this->info('✓ Deleted from database');
            }

            DB::commit();
            $this->info('✓ Cleanup complete!');
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed: ' . $e->getMessage());
            return 1;
        }
    }
}
