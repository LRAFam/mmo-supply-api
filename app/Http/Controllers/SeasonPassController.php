<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\UserSeasonPass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class SeasonPassController extends Controller
{
    /**
     * Get available season passes for the current season
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $season = Season::where('is_active', true)->first();

        if (!$season) {
            return response()->json([
                'message' => 'No active season available',
            ], 404);
        }

        // Get user's current pass for this season
        $userPass = $user->seasonPasses()
            ->where('season_id', $season->id)
            ->first();

        // Get pass pricing
        $pricing = config('achievements.pass_prices');
        $multipliers = config('achievements.reward_multipliers');

        return response()->json([
            'season' => [
                'id' => $season->id,
                'name' => $season->name,
                'description' => $season->description,
                'start_date' => $season->start_date,
                'end_date' => $season->end_date,
                'days_remaining' => $season->daysRemaining(),
            ],
            'user_pass' => $userPass ? [
                'tier' => $userPass->pass_tier,
                'is_active' => $userPass->isActive(),
                'purchased_at' => $userPass->purchased_at,
                'expires_at' => $userPass->expires_at,
            ] : null,
            'available_passes' => [
                [
                    'tier' => 'free',
                    'name' => 'Free Pass',
                    'price' => $pricing['free'],
                    'reward_multiplier' => $multipliers['free'],
                    'features' => [
                        'Earn achievement points',
                        'Track progress',
                        'Unlock seasonal badges',
                    ],
                ],
                [
                    'tier' => 'basic',
                    'name' => 'Basic Pass',
                    'price' => $pricing['basic'],
                    'reward_multiplier' => $multipliers['basic'],
                    'features' => [
                        'All Free Pass features',
                        '50% cash rewards on achievements',
                        'Exclusive basic-tier achievements',
                    ],
                ],
                [
                    'tier' => 'premium',
                    'name' => 'Premium Pass',
                    'price' => $pricing['premium'],
                    'reward_multiplier' => $multipliers['premium'],
                    'features' => [
                        'All Basic Pass features',
                        '100% cash rewards on achievements',
                        'Exclusive premium achievements',
                        'Priority support',
                    ],
                ],
                [
                    'tier' => 'elite',
                    'name' => 'Elite Pass',
                    'price' => $pricing['elite'],
                    'reward_multiplier' => $multipliers['elite'],
                    'features' => [
                        'All Premium Pass features',
                        '150% cash rewards on achievements',
                        'Exclusive elite achievements',
                        'Premium cosmetics',
                        'Custom profile features',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Purchase a season pass
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'tier' => 'required|in:basic,premium,elite',
            'payment_method_id' => 'required|string',
        ]);

        $user = $request->user();
        $season = Season::where('is_active', true)->first();

        if (!$season) {
            return response()->json([
                'message' => 'No active season available',
            ], 404);
        }

        // Check if user already has a pass for this season
        $existingPass = $user->seasonPasses()
            ->where('season_id', $season->id)
            ->first();

        if ($existingPass && $existingPass->pass_tier !== 'free') {
            return response()->json([
                'message' => 'You already have a season pass for this season',
            ], 400);
        }

        $tier = $request->tier;
        $pricing = config('achievements.pass_prices');
        $amount = $pricing[$tier];

        DB::beginTransaction();
        try {
            // Create Stripe payment intent
            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'usd',
                'payment_method' => $request->payment_method_id,
                'customer' => $user->stripe_id,
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'season_id' => $season->id,
                    'pass_tier' => $tier,
                ],
            ]);

            if ($paymentIntent->status !== 'succeeded') {
                DB::rollBack();
                return response()->json([
                    'message' => 'Payment failed',
                    'requires_action' => $paymentIntent->status === 'requires_action',
                    'payment_intent_client_secret' => $paymentIntent->client_secret,
                ], 402);
            }

            // Create or update season pass
            if ($existingPass) {
                $existingPass->update([
                    'pass_tier' => $tier,
                    'price_paid' => $amount,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'purchased_at' => now(),
                    'expires_at' => $season->end_date,
                    'is_active' => true,
                ]);
                $pass = $existingPass;
            } else {
                $pass = $user->seasonPasses()->create([
                    'season_id' => $season->id,
                    'pass_tier' => $tier,
                    'price_paid' => $amount,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'purchased_at' => now(),
                    'expires_at' => $season->end_date,
                    'is_active' => true,
                ]);
            }

            // Record revenue for the season
            $season->recordPassPurchase($amount);

            DB::commit();

            return response()->json([
                'message' => 'Season pass purchased successfully',
                'pass' => $pass,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to purchase season pass',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's season pass details
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $season = Season::where('is_active', true)->first();

        if (!$season) {
            return response()->json([
                'message' => 'No active season available',
            ], 404);
        }

        $pass = $user->seasonPasses()
            ->where('season_id', $season->id)
            ->first();

        if (!$pass) {
            return response()->json([
                'message' => 'No season pass found',
                'has_pass' => false,
            ]);
        }

        return response()->json([
            'pass' => [
                'id' => $pass->id,
                'tier' => $pass->pass_tier,
                'tier_display' => $pass->getTierDisplayName(),
                'price_paid' => $pass->price_paid,
                'purchased_at' => $pass->purchased_at,
                'expires_at' => $pass->expires_at,
                'is_active' => $pass->isActive(),
                'reward_multiplier' => $pass->getRewardMultiplier(),
                'can_claim_cash' => $pass->canClaimCashRewards(),
            ],
            'season' => [
                'id' => $season->id,
                'name' => $season->name,
                'days_remaining' => $season->daysRemaining(),
            ],
        ]);
    }
}
