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

        // Validate requirements including game selection
        $validated = $request->validate([
            'agree_to_terms' => 'required|boolean|accepted',
            'game_ids' => 'required|array|min:1',
            'game_ids.*' => 'exists:games,id',
        ]);

        // Update user to seller status
        $user->update([
            'is_seller' => true,
            'seller_tier' => 'standard',
        ]);

        // Create provider records for each selected game
        foreach ($validated['game_ids'] as $gameId) {
            \App\Models\Provider::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'game_id' => $gameId,
                ],
                [
                    'vouches' => 0,
                    'rating' => null,
                ]
            );
        }

        // Log the action
        \Log::info('User became seller', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'games' => $validated['game_ids'],
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

    /**
     * Get seller's provider games
     */
    public function getProviderGames(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->is_seller) {
            return response()->json([
                'error' => 'Not a seller'
            ], 403);
        }

        $providers = \App\Models\Provider::where('user_id', $user->id)
            ->with('game')
            ->get();

        return response()->json([
            'success' => true,
            'providers' => $providers->map(fn($p) => [
                'id' => $p->id,
                'game_id' => $p->game_id,
                'game' => [
                    'id' => $p->game->id,
                    'title' => $p->game->title,
                ],
                'vouches' => $p->vouches,
                'rating' => $p->rating,
                'created_at' => $p->created_at,
            ])
        ]);
    }

    /**
     * Add a new provider game
     */
    public function addProviderGame(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->is_seller) {
            return response()->json([
                'error' => 'Not a seller'
            ], 403);
        }

        $validated = $request->validate([
            'game_id' => 'required|exists:games,id',
        ]);

        // Check if provider already exists
        $existing = \App\Models\Provider::where('user_id', $user->id)
            ->where('game_id', $validated['game_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'error' => 'You are already a provider for this game'
            ], 400);
        }

        $provider = \App\Models\Provider::create([
            'user_id' => $user->id,
            'game_id' => $validated['game_id'],
            'vouches' => 0,
            'rating' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully added as provider for this game',
            'provider' => $provider,
        ], 201);
    }

    /**
     * Remove a provider game
     */
    public function removeProviderGame(Request $request, $providerId): JsonResponse
    {
        $user = $request->user();

        if (!$user->is_seller) {
            return response()->json([
                'error' => 'Not a seller'
            ], 403);
        }

        $provider = \App\Models\Provider::where('id', $providerId)
            ->where('user_id', $user->id)
            ->first();

        if (!$provider) {
            return response()->json([
                'error' => 'Provider not found'
            ], 404);
        }

        // Check if seller has active listings for this game
        $hasActiveListings = \App\Models\Item::where('user_id', $user->id)
            ->where('game_id', $provider->game_id)
            ->where('is_active', true)
            ->exists()
            || \App\Models\Currency::where('user_id', $user->id)
            ->where('game_id', $provider->game_id)
            ->where('is_active', true)
            ->exists()
            || \App\Models\Account::where('user_id', $user->id)
            ->where('game_id', $provider->game_id)
            ->where('is_active', true)
            ->exists()
            || \App\Models\Service::where('user_id', $user->id)
            ->where('game_id', $provider->game_id)
            ->where('is_active', true)
            ->exists();

        if ($hasActiveListings) {
            return response()->json([
                'error' => 'Cannot remove game while you have active listings for it. Please deactivate all listings first.'
            ], 400);
        }

        $provider->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully removed as provider for this game',
        ]);
    }
}
