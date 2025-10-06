<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    /**
     * Set custom seller earnings for a user (admin only)
     * Useful for special partnerships, high-volume sellers, etc.
     */
    public function setSellerEarnings(Request $request, $userId): JsonResponse
    {
        // TODO: Add admin middleware check

        $request->validate([
            'earnings_percentage' => 'required|numeric|min:0|max:100',
            'tier' => 'required|in:standard,partner,elite',
            'reason' => 'nullable|string',
        ]);

        $user = User::findOrFail($userId);

        if (!$user->is_seller) {
            return response()->json(['error' => 'User is not a seller'], 400);
        }

        $user->setSellerEarnings(
            $request->earnings_percentage,
            $request->tier
        );

        return response()->json([
            'message' => 'Seller earnings updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'seller_earnings_percentage' => $user->seller_earnings_percentage,
                'seller_tier' => $user->seller_tier,
                'platform_fee_percentage' => $user->getPlatformFeePercentage(),
            ],
        ]);
    }

    /**
     * Get all sellers with custom earnings
     */
    public function getCustomEarningsSellers(): JsonResponse
    {
        // TODO: Add admin middleware check

        $sellers = User::where('is_seller', true)
            ->whereNotNull('seller_earnings_percentage')
            ->select('id', 'name', 'email', 'seller_earnings_percentage', 'seller_tier')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'seller_earnings' => $user->seller_earnings_percentage,
                    'platform_fee' => 100 - $user->seller_earnings_percentage,
                    'tier' => $user->seller_tier,
                ];
            });

        return response()->json($sellers);
    }

    /**
     * Reset seller earnings to default (subscription-based)
     */
    public function resetSellerEarnings($userId): JsonResponse
    {
        // TODO: Add admin middleware check

        $user = User::findOrFail($userId);

        $user->update([
            'seller_earnings_percentage' => null,
            'seller_tier' => 'standard',
        ]);

        return response()->json([
            'message' => 'Seller earnings reset to subscription defaults',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'will_use_subscription' => true,
            ],
        ]);
    }

    /**
     * Grant a temporary subscription to a user (admin only)
     * Useful for promotions, support, testing, or special cases
     */
    public function grantSubscription(Request $request, $userId): JsonResponse
    {
        // TODO: Add admin middleware check

        $request->validate([
            'tier' => 'required|in:premium,elite',
            'duration_days' => 'required|integer|min:1|max:365',
            'reason' => 'nullable|string',
        ]);

        $user = User::findOrFail($userId);
        $tier = $request->tier;
        $durationDays = $request->duration_days;

        // Check if user already has an active paid subscription
        $existingSubscription = $user->subscriptions()->where('stripe_status', 'active')->first();
        if ($existingSubscription) {
            return response()->json([
                'error' => 'User already has an active subscription. Please cancel it first.'
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

            // Create subscription with trial period (no payment required)
            $subscription = $user->newSubscription('default', $priceId)
                ->trialDays($durationDays)
                ->create();

            // Log the admin action
            \Log::info('Admin granted subscription', [
                'admin_user' => $request->user()->id ?? 'unknown',
                'target_user' => $userId,
                'tier' => $tier,
                'duration_days' => $durationDays,
                'reason' => $request->reason,
            ]);

            return response()->json([
                'message' => "Successfully granted {$tier} subscription for {$durationDays} days",
                'subscription' => [
                    'id' => $subscription->id,
                    'user_id' => $user->id,
                    'tier' => $tier,
                    'status' => $subscription->stripe_status,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'created_at' => $subscription->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Failed to grant subscription', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tier' => $tier,
            ]);

            return response()->json([
                'error' => 'Failed to grant subscription: ' . $e->getMessage()
            ], 500);
        }
    }
}
