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
                'provider_earnings_percentage' => $user->getSellerEarningsPercentage(),
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

    /**
     * Show public user profile (no authentication required)
     */
    public function showPublic($username)
    {
        $user = User::where('name', $username)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'role' => $user->role,
            'is_seller' => (bool) $user->is_seller,
            'seller_tier' => $user->seller_tier,
            'lifetime_sales' => $user->lifetime_sales ?? 0,
            'monthly_sales' => $user->monthly_sales ?? 0,
            'total_referrals' => $user->total_referrals ?? 0,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * Allow a user to become a seller/provider
     */
    public function becomeSeller(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is already a seller
        if ($user->is_seller) {
            return response()->json([
                'error' => 'You are already a seller'
            ], 400);
        }

        // Optional: validate requirements
        $validated = $request->validate([
            'agree_to_terms' => 'required|boolean|accepted',
        ]);

        // Update user to seller status
        $user->update([
            'is_seller' => true,
            'seller_tier' => 'standard',
        ]);

        // Log the action
        \Log::info('User became seller', [
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Congratulations! You are now a seller. Start creating your first listing.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'is_seller' => true,
                'seller_tier' => $user->seller_tier,
                'seller_earnings_percentage' => $user->getSellerEarningsPercentage(),
            ],
        ], 201);
    }
}
