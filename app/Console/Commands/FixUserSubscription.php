<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

class FixUserSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:fix {email} {tier=premium}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually create a subscription for a user who paid but subscription was not created';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $tier = $this->argument('tier');

        if (!in_array($tier, ['premium', 'elite'])) {
            $this->error('Invalid tier. Must be premium or elite.');
            return 1;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found with email: {$email}");
            return 1;
        }

        // Check if user already has active subscription
        if ($user->subscribed('default')) {
            $this->error('User already has an active subscription.');
            return 1;
        }

        $this->info("Creating {$tier} subscription for {$user->name} ({$user->email})...");

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Get the price ID
            $priceId = $tier === 'elite'
                ? config('services.stripe.elite_price_id')
                : config('services.stripe.premium_price_id');

            if (!$priceId || str_starts_with($priceId, 'price_premium') || str_starts_with($priceId, 'price_elite')) {
                $this->error('Invalid price ID configured. Please set real Stripe price IDs in .env');
                return 1;
            }

            $this->info("Using price ID: {$priceId}");

            DB::beginTransaction();

            // Create or get Stripe customer
            if (!$user->stripe_id) {
                $this->info('Creating Stripe customer...');
                $user->createAsStripeCustomer([
                    'email' => $user->email,
                    'name' => $user->name,
                ]);
                $this->info("Stripe customer created: {$user->stripe_id}");
            } else {
                $this->info("Using existing Stripe customer: {$user->stripe_id}");
            }

            // Try to find and attach an existing payment method
            $this->info('Looking for existing payment methods...');
            $paymentMethods = \Stripe\PaymentMethod::all([
                'customer' => $user->stripe_id,
                'type' => 'card',
            ]);

            $defaultPaymentMethod = null;
            if (!empty($paymentMethods->data)) {
                $defaultPaymentMethod = $paymentMethods->data[0]->id;
                $this->info("Found payment method: {$defaultPaymentMethod}");

                // Set as default payment method
                \Stripe\Customer::update($user->stripe_id, [
                    'invoice_settings' => [
                        'default_payment_method' => $defaultPaymentMethod,
                    ],
                ]);
                $this->info('Set as default payment method');
            } else {
                $this->warn('No payment method found. Creating subscription without immediate charge...');
                $this->warn('User will need to add payment method through billing portal.');
            }

            // Create subscription in Stripe
            $this->info('Creating subscription in Stripe...');
            $subscriptionData = [
                'customer' => $user->stripe_id,
                'items' => [
                    ['price' => $priceId],
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'tier' => $tier,
                ],
            ];

            // If no payment method, create with trial or payment pending
            if (!$defaultPaymentMethod) {
                $subscriptionData['payment_behavior'] = 'default_incomplete';
                $subscriptionData['payment_settings'] = [
                    'payment_method_types' => ['card'],
                    'save_default_payment_method' => 'on_subscription',
                ];
            }

            $stripeSubscription = StripeSubscription::create($subscriptionData);

            $this->info("Stripe subscription created: {$stripeSubscription->id}");

            // Create subscription in database using Cashier
            $subscription = $user->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => $stripeSubscription->id,
                'stripe_status' => $stripeSubscription->status,
                'stripe_price' => $priceId,
                'quantity' => 1,
                'trial_ends_at' => null,
                'ends_at' => null,
            ]);

            // Create subscription item
            $subscription->items()->create([
                'stripe_id' => $stripeSubscription->items->data[0]->id,
                'stripe_product' => $stripeSubscription->items->data[0]->price->product,
                'stripe_price' => $priceId,
                'quantity' => 1,
            ]);

            DB::commit();

            $this->info('✓ Subscription created successfully!');
            $this->info("Subscription ID: {$subscription->id}");
            $this->info("Stripe Subscription ID: {$stripeSubscription->id}");
            $this->info("Status: {$stripeSubscription->status}");
            $this->info("Current period: " . date('Y-m-d', $stripeSubscription->current_period_start) . ' to ' . date('Y-m-d', $stripeSubscription->current_period_end));

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to create subscription: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
