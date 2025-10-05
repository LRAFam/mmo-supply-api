<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\Customer;

class DeleteStripeCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:delete-customer {customer_id} {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a Stripe customer by ID (use with caution)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->argument('customer_id');

        if (!str_starts_with($customerId, 'cus_')) {
            $this->error('Invalid customer ID format. Must start with cus_');
            return 1;
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Retrieve customer first to show details
            $customer = Customer::retrieve($customerId);

            $this->info("Found customer:");
            $this->info("  ID: {$customer->id}");
            $this->info("  Email: {$customer->email}");
            $this->info("  Name: " . ($customer->name ?? 'N/A'));
            $this->info("  Created: " . date('Y-m-d H:i:s', $customer->created));

            // Check for subscriptions
            $subscriptions = \Stripe\Subscription::all(['customer' => $customerId, 'limit' => 10]);
            if (count($subscriptions->data) > 0) {
                $this->warn("⚠ This customer has " . count($subscriptions->data) . " subscription(s):");
                foreach ($subscriptions->data as $sub) {
                    $this->warn("  - {$sub->id} (Status: {$sub->status})");
                }
            }

            // Confirm deletion (skip if --force flag is used)
            if (!$this->option('force')) {
                if (!$this->confirm('Are you sure you want to DELETE this customer from Stripe?', false)) {
                    $this->info('Aborted.');
                    return 0;
                }
            } else {
                $this->warn('Skipping confirmation (--force flag used)');
            }

            // Delete customer
            $customer->delete();

            $this->info("✓ Customer {$customerId} has been deleted from Stripe");

            return 0;
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $this->error("Customer not found: " . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error("Failed to delete customer: " . $e->getMessage());
            return 1;
        }
    }
}
