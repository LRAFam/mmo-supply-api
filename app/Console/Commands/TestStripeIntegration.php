<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;
use Stripe\Customer;

class TestStripeIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:test {--user-email=test@example.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Stripe integration and configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Stripe Integration');
        $this->info('==============================');
        $this->newLine();

        // Test 1: Check Stripe API Key
        $this->info('1. Checking Stripe API Configuration...');
        Stripe::setApiKey(config('services.stripe.secret'));

        if (!config('services.stripe.secret')) {
            $this->error('❌ STRIPE_SECRET not configured');
            return 1;
        }

        if (!config('services.stripe.key')) {
            $this->error('❌ STRIPE_KEY not configured');
            return 1;
        }

        $mode = str_starts_with(config('services.stripe.secret'), 'sk_test') ? 'TEST MODE' : 'LIVE MODE';
        $this->info("✅ Stripe keys configured ({$mode})");
        $this->newLine();

        // Test 2: Check Price IDs
        $this->info('2. Checking Subscription Price IDs...');
        $premiumPriceId = config('services.stripe.premium_price_id');
        $elitePriceId = config('services.stripe.elite_price_id');

        if (!$premiumPriceId || !$elitePriceId) {
            $this->error('❌ Price IDs not configured');
            return 1;
        }

        $this->info("✅ Premium Price ID: {$premiumPriceId}");
        $this->info("✅ Elite Price ID: {$elitePriceId}");
        $this->newLine();

        // Test 3: Verify Prices exist in Stripe
        $this->info('3. Verifying Prices in Stripe...');
        try {
            $premiumPrice = Price::retrieve($premiumPriceId);
            $premiumAmount = $premiumPrice->unit_amount / 100;
            $this->info("✅ Premium Price: \${$premiumAmount} {$premiumPrice->currency}/{$premiumPrice->recurring->interval}");

            $elitePrice = Price::retrieve($elitePriceId);
            $eliteAmount = $elitePrice->unit_amount / 100;
            $this->info("✅ Elite Price: \${$eliteAmount} {$elitePrice->currency}/{$elitePrice->recurring->interval}");
        } catch (\Exception $e) {
            $this->error("❌ Failed to retrieve prices: {$e->getMessage()}");
            return 1;
        }
        $this->newLine();

        // Test 4: Verify Products exist
        $this->info('4. Verifying Products in Stripe...');
        try {
            $premiumProduct = Product::retrieve($premiumPrice->product);
            $this->info("✅ Premium Product: {$premiumProduct->name}");

            $eliteProduct = Product::retrieve($elitePrice->product);
            $this->info("✅ Elite Product: {$eliteProduct->name}");
        } catch (\Exception $e) {
            $this->error("❌ Failed to retrieve products: {$e->getMessage()}");
            return 1;
        }
        $this->newLine();

        // Test 5: Test creating a customer (optional)
        if ($this->confirm('Would you like to test creating a Stripe customer?', false)) {
            $this->info('5. Testing Customer Creation...');

            $userEmail = $this->option('user-email');
            $user = User::where('email', $userEmail)->first();

            if (!$user) {
                $this->warn("No user found with email: {$userEmail}");
                $this->info('Creating a test customer directly with Stripe...');

                try {
                    $customer = Customer::create([
                        'email' => 'test-' . time() . '@example.com',
                        'name' => 'Test Customer',
                        'description' => 'Test customer created by stripe:test command',
                    ]);
                    $this->info("✅ Test customer created: {$customer->id}");
                    $this->warn("⚠️  Remember to delete this test customer from Stripe dashboard");
                } catch (\Exception $e) {
                    $this->error("❌ Failed to create customer: {$e->getMessage()}");
                    return 1;
                }
            } else {
                try {
                    $stripeService = new \App\Services\StripePaymentService();
                    $customerId = $stripeService->getOrCreateCustomer($user);
                    $this->info("✅ Customer created/retrieved: {$customerId}");
                } catch (\Exception $e) {
                    $this->error("❌ Failed to create customer: {$e->getMessage()}");
                    return 1;
                }
            }
            $this->newLine();
        }

        // Test 6: Webhook Secret
        $this->info('6. Checking Webhook Configuration...');
        if (!config('services.stripe.webhook_secret')) {
            $this->warn('⚠️  STRIPE_WEBHOOK_SECRET not configured');
            $this->info('   Set this up in production for webhook verification');
        } else {
            $this->info('✅ Webhook secret configured');
        }
        $this->newLine();

        // Summary
        $this->info('==============================');
        $this->info('✅ All tests passed!');
        $this->info('==============================');
        $this->newLine();
        $this->info('Your Stripe integration is ready to use.');
        $this->info('');
        $this->info('Next steps:');
        $this->line('• Deploy your application');
        $this->line('• Test subscription checkout via frontend');
        $this->line('• Set up webhooks in Stripe dashboard');
        $this->line('• Configure webhook URL: https://your-domain.com/api/stripe/webhook');

        return 0;
    }
}
