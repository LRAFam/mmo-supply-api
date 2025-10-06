<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Create a Stripe Checkout Session for subscription
     */
    public function createSubscriptionCheckoutSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tier' => 'required|string|in:premium,elite',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Check if user already has an active subscription
        if ($user->subscribed('default')) {
            return response()->json([
                'message' => 'You already have an active subscription. Please cancel it first.'
            ], 400);
        }

        try {
            // Get price ID based on tier
            $priceId = $request->tier === 'elite'
                ? config('services.stripe.elite_price_id')
                : config('services.stripe.premium_price_id');

            if (!$priceId || str_starts_with($priceId, 'price_premium') || str_starts_with($priceId, 'price_elite')) {
                \Log::error('Invalid subscription price ID configured', [
                    'tier' => $request->tier,
                    'price_id' => $priceId
                ]);
                return response()->json([
                    'message' => 'Subscription price not configured properly. Please contact support.'
                ], 500);
            }

            // Ensure user has Stripe customer ID
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer([
                    'email' => $user->email,
                    'name' => $user->name,
                ]);
            }

            \Log::info('Creating subscription checkout session', [
                'user_id' => $user->id,
                'tier' => $request->tier,
                'price_id' => $priceId,
                'stripe_customer_id' => $user->stripe_id
            ]);

            // Create Stripe Checkout Session for recurring subscription
            $checkoutSession = $user->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => config('app.frontend_url') . '/subscription/success?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => config('app.frontend_url') . '/subscription',
                    'metadata' => [
                        'user_id' => $user->id,
                        'tier' => $request->tier,
                    ],
                ]);

            \Log::info('Checkout session created successfully', [
                'session_id' => $checkoutSession->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'checkout_url' => $checkoutSession->url,
                'session_id' => $checkoutSession->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Subscription checkout error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'tier' => $request->tier,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create checkout session',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred. Please try again or contact support.'
            ], 500);
        }
    }

    /**
     * Create payment intent for one-time payment
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'type' => 'required|string|in:wallet_deposit,order_payment,featured_listing',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        try {
            $stripeService = new \App\Services\StripePaymentService();

            // Create payment intent based on type
            if ($request->type === 'wallet_deposit') {
                $paymentIntent = $stripeService->createDepositIntent($user, $request->amount);
            } elseif ($request->type === 'order_payment') {
                $paymentIntent = $stripeService->createOrderPaymentIntent(
                    $user,
                    $request->amount,
                    $request->order_id
                );
            } else {
                return response()->json(['message' => 'Payment type not fully implemented'], 501);
            }

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Payment intent error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to create payment intent',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
