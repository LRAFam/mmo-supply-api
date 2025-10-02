<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    /**
     * Set custom creator earnings for a user (admin only)
     * Useful for special partnerships, influencers, etc.
     */
    public function setCreatorEarnings(Request $request, $userId): JsonResponse
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

        $user->setCreatorEarnings(
            $request->earnings_percentage,
            $request->tier
        );

        return response()->json([
            'message' => 'Creator earnings updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'creator_earnings_percentage' => $user->creator_earnings_percentage,
                'creator_tier' => $user->creator_tier,
                'platform_fee_percentage' => $user->getPlatformFeePercentage(),
            ],
        ]);
    }

    /**
     * Get all creators with custom earnings
     */
    public function getCustomEarningsCreators(): JsonResponse
    {
        // TODO: Add admin middleware check

        $creators = User::where('is_seller', true)
            ->whereNotNull('creator_earnings_percentage')
            ->select('id', 'name', 'email', 'creator_earnings_percentage', 'creator_tier')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'creator_earnings' => $user->creator_earnings_percentage,
                    'platform_fee' => 100 - $user->creator_earnings_percentage,
                    'tier' => $user->creator_tier,
                ];
            });

        return response()->json($creators);
    }

    /**
     * Reset creator earnings to default (subscription-based)
     */
    public function resetCreatorEarnings(Request $request, $userId): JsonResponse
    {
        // TODO: Add admin middleware check

        $user = User::findOrFail($userId);

        $user->update([
            'creator_earnings_percentage' => null,
            'creator_tier' => 'standard',
        ]);

        return response()->json([
            'message' => 'Creator earnings reset to subscription defaults',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'will_use_subscription' => true,
            ],
        ]);
    }
}
