<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SellerSubscriptionController extends Controller
{
    /**
     * Get available subscription tiers
     */
    public function getTiers(): JsonResponse
    {
        $plans = [
            [
                'id' => 'free',
                'name' => 'Free',
                'price' => 0,
                'features' => [
                    'Unlimited basic listings',
                    'Basic analytics',
                    'Email support (48-72h)',
                    'Standard marketplace visibility',
                    'Keep 70% of earnings',
                ],
                'badge' => null,
                'stripe_price_id' => null,
            ],
            [
                'id' => 'premium',
                'name' => 'Premium',
                'price' => 9.99,
                'features' => [
                    'Verified seller badge',
                    '3 featured listing slots per month',
                    'Priority placement in search results',
                    'Advanced analytics dashboard',
                    'Custom storefront colors & branding',
                    'Email marketing tools',
                    'Priority support (24h response)',
                    '4 premium spin wheel spins per week',
                    'Access to exclusive events',
                    'Keep 80% of earnings (vs 70%)',
                ],
                'badge' => 'PREMIUM',
                'badge_color' => 'blue',
                'popular' => true,
                'stripe_price_id' => config('services.stripe.premium_price_id'),
            ],
            [
                'id' => 'elite',
                'name' => 'Elite',
                'price' => 29.99,
                'features' => [
                    'Verified seller badge',
                    'Unlimited featured listings',
                    'Top priority placement everywhere',
                    'Premium analytics & insights',
                    'Full custom storefront & white-label',
                    'Advanced marketing suite',
                    'Dedicated account manager',
                    '1-hour priority support',
                    '8 premium spin wheel spins per week',
                    'Exclusive events & early features',
                    'API access for automation',
                    'Keep 90% of earnings (vs 70%)',
                ],
                'badge' => 'ELITE',
                'badge_color' => 'gold',
                'stripe_price_id' => config('services.stripe.elite_price_id'),
            ],
        ];

        return response()->json($plans);
    }

    /**
     * Get current user's subscription
     */
    public function getCurrent(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check for active subscription
        $subscription = $user->subscription('premium') ?? $user->subscription('elite');

        if (!$subscription || !$subscription->active()) {
            return response()->json([
                'subscription' => null,
                'tier' => 'free',
            ]);
        }

        return response()->json([
            'subscription' => [
                'id' => $subscription->id,
                'tier' => $subscription->name,
                'status' => $subscription->stripe_status,
                'trial_ends_at' => $subscription->trial_ends_at,
                'ends_at' => $subscription->ends_at,
                'on_trial' => $subscription->onTrial(),
                'cancelled' => $subscription->cancelled(),
            ],
            'tier' => $subscription->name,
        ]);
    }

    /**
     * Subscribe to a tier (Cashier approach)
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'tier' => 'required|in:premium,elite',
            'payment_method' => 'required|string',
        ]);

        $user = $request->user();
        $tier = $request->tier;

        // Check if user already has an active subscription to this tier
        if ($user->subscribed($tier)) {
            return response()->json([
                'error' => 'You already have an active subscription to this plan.'
            ], 400);
        }

        try {
            // Ensure user is a Stripe customer
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer([
                    'name' => $user->name,
                    'email' => $user->email,
                ]);
            }

            // Get the Stripe price ID
            $priceId = $tier === 'elite'
                ? config('services.stripe.elite_price_id')
                : config('services.stripe.premium_price_id');

            // Create subscription using Cashier
            $subscription = $user->newSubscription($tier, $priceId)
                ->create($request->payment_method);

            return response()->json([
                'message' => 'Subscription created successfully!',
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->stripe_status,
                    'tier' => $subscription->name,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'ends_at' => $subscription->ends_at,
                ],
            ], 201);

        } catch (IncompletePayment $exception) {
            return response()->json([
                'message' => 'Payment requires additional confirmation',
                'payment_intent' => $exception->payment->id,
                'client_secret' => $exception->payment->client_secret,
            ], 402);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create setup intent for adding payment method
     */
    public function setupIntent(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            // Ensure user is a Stripe customer
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer([
                    'name' => $user->name,
                    'email' => $user->email,
                ]);
            }

            $intent = $user->createSetupIntent();

            return response()->json([
                'client_secret' => $intent->client_secret,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create setup intent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->subscription('premium') ?? $user->subscription('elite');

        if (!$subscription) {
            return response()->json(['error' => 'No active subscription found'], 404);
        }

        if ($subscription->cancelled()) {
            return response()->json(['error' => 'Subscription is already cancelled'], 400);
        }

        try {
            // Cancel at period end
            $subscription->cancel();

            return response()->json([
                'message' => 'Subscription cancelled successfully. You will retain access until the end of your billing period.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resume cancelled subscription
     */
    public function resume(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->subscription('premium') ?? $user->subscription('elite');

        if (!$subscription || !$subscription->cancelled()) {
            return response()->json(['error' => 'No cancelled subscription found'], 404);
        }

        try {
            $subscription->resume();

            return response()->json([
                'message' => 'Subscription resumed successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to resume subscription: ' . $e->getMessage()
            ], 500);
        }
    }
}
