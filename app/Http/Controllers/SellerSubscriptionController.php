<?php

namespace App\Http\Controllers;

use App\Models\SellerSubscription;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SellerSubscriptionController extends Controller
{
    /**
     * Get available subscription tiers
     */
    public function getTiers(): JsonResponse
    {
        // Premium Features Plans
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
        $subscription = $user->getActiveSubscription();

        return response()->json([
            'subscription' => $subscription,
            'benefits' => $subscription->getBenefits(),
        ]);
    }

    /**
     * Subscribe to a tier
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'tier' => 'required|in:premium,elite',
            'payment_method' => 'required|in:wallet,stripe',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $tier = $request->tier;

            // Get pricing
            $prices = [
                'premium' => 9.99,
                'elite' => 29.99,
            ];

            $price = $prices[$tier];
            $creatorEarnings = match($tier) {
                'premium' => 80.0, // Creator gets 80%, platform gets 20%
                'elite' => 90.0,   // Creator gets 90%, platform gets 10%
            };
            $feePercentage = 100 - $creatorEarnings;

            // Deactivate any existing subscriptions
            SellerSubscription::where('user_id', $user->id)->update(['is_active' => false]);

            // Handle payment
            if ($request->payment_method === 'wallet') {
                $wallet = $user->getOrCreateWallet();
                if ($wallet->balance < $price) {
                    return response()->json(['error' => 'Insufficient wallet balance'], 400);
                }

                $wallet->purchase($price, null, "Seller subscription: {$tier}");

                // Create subscription
                $subscription = SellerSubscription::create([
                    'user_id' => $user->id,
                    'tier' => $tier,
                    'fee_percentage' => $feePercentage,
                    'monthly_price' => $price,
                    'started_at' => now(),
                    'expires_at' => now()->addMonth(),
                    'is_active' => true,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Subscription activated successfully',
                    'subscription' => $subscription,
                ], 201);

            } elseif ($request->payment_method === 'stripe') {
                // Create subscription first to get ID
                $subscription = SellerSubscription::create([
                    'user_id' => $user->id,
                    'tier' => $tier,
                    'fee_percentage' => $feePercentage,
                    'monthly_price' => $price,
                    'started_at' => now(),
                    'expires_at' => now()->addMonth(),
                    'is_active' => false, // Will be activated by webhook
                ]);

                // Create Stripe Checkout Session for subscription
                $stripeService = new StripePaymentService();
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

                $checkoutSession = \Stripe\Checkout\Session::create([
                    'customer' => $stripeService->getOrCreateCustomer($user),
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => ucfirst($tier) . ' Seller Subscription',
                                'description' => 'Monthly subscription with ' . $creatorEarnings . '% creator earnings',
                            ],
                            'unit_amount' => (int)($price * 100),
                            'recurring' => [
                                'interval' => 'month',
                            ],
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'subscription',
                    'success_url' => config('app.frontend_url', 'http://localhost:3000') . '/provider/subscription?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => config('app.frontend_url', 'http://localhost:3000') . '/provider/subscription',
                    'metadata' => [
                        'user_id' => $user->id,
                        'type' => 'seller_subscription',
                        'tier' => $tier,
                        'subscription_id' => $subscription->id,
                    ],
                ]);

                // Store checkout session ID
                $subscription->update(['stripe_subscription_id' => $checkoutSession->id]);

                DB::commit();

                return response()->json([
                    'message' => 'Checkout session created',
                    'subscription' => $subscription,
                    'requires_payment' => true,
                    'checkout_url' => $checkoutSession->url,
                ], 201);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create subscription: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->sellerSubscription()
            ->where('is_active', true)
            ->first();

        if (!$subscription) {
            return response()->json(['error' => 'No active subscription found'], 404);
        }

        $subscription->update(['is_active' => false]);

        return response()->json([
            'message' => 'Subscription cancelled successfully',
        ]);
    }
}
