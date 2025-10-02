<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get the authenticated user's data.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('wallet');

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'is_seller' => (bool) $user->is_seller,
                'subscription_tier' => $user->getSubscriptionTier(),
                'provider_tier' => $user->auto_tier ?? 'standard',
                'provider_earnings_percentage' => $user->getCreatorEarningsPercentage(),
                'wallet' => [
                    'balance' => $user->wallet->balance ?? 0,
                ],
            ],
        ]);
    }

    /**
     * Get tier progress for the authenticated user
     */
    public function getTierProgress(Request $request): JsonResponse
    {
        $user = $request->user();
        $progress = $user->getTierProgress();

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }

    /**
     * Update the authenticated user's profile
     */
    public function edit(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'avatar' => $user->avatar,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'is_seller' => (bool) $user->is_seller,
            ],
        ]);
    }
}
