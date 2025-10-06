<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SpinWheel;
use App\Models\WheelPrize;
use App\Models\SpinResult;
use App\Models\UserWheelSpin;
use Illuminate\Support\Facades\DB;

class SpinWheelController extends Controller
{
    /**
     * Get all available spin wheels
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->refresh(); // Ensure we have fresh data from database
        $wheels = SpinWheel::with('prizes')->where('is_active', true)->get();

        $wheelData = $wheels->map(function ($wheel) use ($user) {
            $canSpin = $wheel->canUserSpin($user);
            $nextSpinTime = $wheel->getNextSpinTime($user);

            $data = [
                'id' => $wheel->id,
                'name' => $wheel->name,
                'type' => $wheel->type,
                'cost' => $wheel->cost,
                'cooldown_hours' => $wheel->cooldown_hours,
                'can_spin' => $canSpin,
                'next_spin_at' => $nextSpinTime,
                'prizes' => $wheel->prizes,
            ];

            // Add subscription info for premium wheels
            if ($wheel->type === 'premium') {
                $tier = $user->getSubscriptionTier();
                $data['requires_subscription'] = true;
                $data['has_subscription'] = in_array($tier, ['premium', 'elite']);
                $data['subscription_tier'] = $tier;
                $data['spins_remaining'] = $user->premium_spins_remaining ?? 0;
                $data['spins_reset_at'] = $user->premium_spins_reset_at;
            }

            return $data;
        });

        return response()->json($wheelData);
    }

    /**
     * Get a specific wheel with prizes
     */
    public function show(Request $request, string|int $wheelId): JsonResponse
    {
        $user = $request->user();
        $user->refresh(); // Ensure we have fresh data from database
        $wheel = SpinWheel::with('prizes')->find((int)$wheelId);

        if (!$wheel) {
            return response()->json(['message' => 'Wheel not found'], 404);
        }

        $canSpin = $wheel->canUserSpin($user);
        $nextSpinTime = $wheel->getNextSpinTime($user);

        $data = [
            'id' => $wheel->id,
            'name' => $wheel->name,
            'type' => $wheel->type,
            'cost' => $wheel->cost,
            'cooldown_hours' => $wheel->cooldown_hours,
            'can_spin' => $canSpin,
            'next_spin_at' => $nextSpinTime,
            'prizes' => $wheel->prizes,
        ];

        // Add subscription info for premium wheels
        if ($wheel->type === 'premium') {
            $tier = $user->getSubscriptionTier();
            $data['requires_subscription'] = true;
            $data['has_subscription'] = in_array($tier, ['premium', 'elite']);
            $data['subscription_tier'] = $tier;
            $data['spins_remaining'] = $user->premium_spins_remaining ?? 0;
            $data['spins_reset_at'] = $user->premium_spins_reset_at;
        }

        return response()->json($data);
    }

    /**
     * Spin the wheel
     */
    public function spin(Request $request, int $wheelId): JsonResponse
    {
        $user = $request->user();
        $wheel = SpinWheel::with('prizes')->find($wheelId);

        if (!$wheel || !$wheel->is_active) {
            return response()->json(['message' => 'Wheel not found or inactive'], 404);
        }

        // SECURITY: Require email verification before spinning
        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'Please verify your email before spinning',
                'requires_verification' => true
            ], 403);
        }

        // Check if user can spin
        if (!$wheel->canUserSpin($user)) {
            $nextSpinTime = $wheel->getNextSpinTime($user);
            return response()->json([
                'message' => 'Cannot spin wheel at this time',
                'next_spin_at' => $nextSpinTime,
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Handle premium wheel (subscription-based)
            if ($wheel->type === 'premium') {
                // Check if user has premium subscription using Cashier
                $tier = $user->getSubscriptionTier();

                if (!in_array($tier, ['premium', 'elite'])) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Premium or Elite subscription required',
                        'requires_subscription' => true
                    ], 403);
                }

                // Reset spins if it's a new week
                if (!$user->premium_spins_reset_at || now()->gte($user->premium_spins_reset_at)) {
                    // Allocate weekly spins based on tier
                    $spinsPerWeek = $tier === 'elite' ? 2 : 1;
                    $user->premium_spins_remaining = $spinsPerWeek;
                    $user->premium_spins_reset_at = now()->addWeek();
                    $user->save();
                }

                // Check if user has spins remaining
                if ($user->premium_spins_remaining <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'No premium spins remaining this week',
                        'resets_at' => $user->premium_spins_reset_at
                    ], 403);
                }

                // Deduct one spin
                $user->decrement('premium_spins_remaining');
            }

            // Select a random prize based on probability weights
            $activePrizes = $wheel->prizes()->where('is_active', true)->get();

            if ($activePrizes->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'No active prizes available'], 500);
            }

            $totalWeight = $activePrizes->sum('probability_weight');
            $random = mt_rand(1, $totalWeight);

            $currentWeight = 0;
            $selectedPrize = null;

            foreach ($activePrizes as $prize) {
                $currentWeight += $prize->probability_weight;
                if ($random <= $currentWeight) {
                    $selectedPrize = $prize;
                    break;
                }
            }

            // Award prize if wallet_credit
            // SECURITY: Credit to bonus_balance (non-withdrawable) instead of wallet_balance
            if ($selectedPrize->type === 'wallet_credit' && $selectedPrize->value > 0) {
                $user->increment('bonus_balance', $selectedPrize->value);

                // Create transaction record in Wallet for tracking
                $wallet = $user->wallet;
                if ($wallet) {
                    $wallet->transactions()->create([
                        'user_id' => $user->id,
                        'type' => 'deposit',
                        'amount' => $selectedPrize->value,
                        'status' => 'completed',
                        'description' => "Spin Wheel Bonus: {$selectedPrize->name} (Platform Credit Only)",
                    ]);
                }
            }

            // Create spin result record with IP tracking
            $spinResult = SpinResult::create([
                'user_id' => $user->id,
                'spin_wheel_id' => $wheel->id,
                'wheel_prize_id' => $selectedPrize->id,
                'prize_name' => $selectedPrize->name,
                'prize_type' => $selectedPrize->type,
                'prize_value' => $selectedPrize->value,
                'spun_at' => now(),
                'ip_address' => $request->ip(),
            ]);

            // Update or create user spin tracking for cooldown
            if ($wheel->cost == 0) {
                UserWheelSpin::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'spin_wheel_id' => $wheel->id,
                    ],
                    [
                        'last_spin_at' => now(),
                        'next_available_at' => now()->addHours($wheel->cooldown_hours),
                    ]
                );
            }

            DB::commit();

            $user->refresh();

            $response = [
                'message' => 'Spin successful!',
                'prize' => [
                    'id' => $selectedPrize->id,
                    'name' => $selectedPrize->name,
                    'type' => $selectedPrize->type,
                    'value' => $selectedPrize->value,
                    'color' => $selectedPrize->color,
                    'icon' => $selectedPrize->icon,
                ],
                'wallet_balance' => $user->wallet_balance,
                'bonus_balance' => $user->bonus_balance,
                'total_balance' => $user->wallet_balance + $user->bonus_balance,
                'next_spin_at' => $wheel->cost == 0 ? now()->addHours($wheel->cooldown_hours) : null,
            ];

            // Add premium spin info if applicable
            if ($wheel->type === 'premium') {
                $response['spins_remaining'] = $user->premium_spins_remaining;
                $response['spins_reset_at'] = $user->premium_spins_reset_at;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Spin failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get user's spin history
     */
    public function history(Request $request, string|int|null $wheelId = null): JsonResponse
    {
        $user = $request->user();

        // If wheelId is provided, validate it exists
        if ($wheelId !== null) {
            $wheel = SpinWheel::find((int)$wheelId);
            if (!$wheel) {
                return response()->json(['message' => 'Wheel not found'], 404);
            }

            // Get history for specific wheel
            $history = SpinResult::where('user_id', $user->id)
                ->where('spin_wheel_id', (int)$wheelId)
                ->with(['spinWheel', 'wheelPrize'])
                ->orderBy('spun_at', 'desc')
                ->limit(50)
                ->get();
        } else {
            // Get all history
            $history = SpinResult::where('user_id', $user->id)
                ->with(['spinWheel', 'wheelPrize'])
                ->orderBy('spun_at', 'desc')
                ->limit(50)
                ->get();
        }

        return response()->json($history);
    }
}
