<?php

namespace App\Http\Controllers;

use App\Models\User;
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
                'email_visible' => (bool) $user->email_visible,
                'bio' => $user->bio,
                'avatar' => $user->avatar,
                'banner' => $user->banner,
                'active_title' => $user->active_title,
                'active_profile_theme' => $user->active_profile_theme,
                'owned_cosmetics' => $user->owned_cosmetics ?? [],
                'badge_inventory' => $user->badge_inventory ?? [],
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'is_seller' => (bool) $user->is_seller,
                'subscription_tier' => $user->getSubscriptionTier(),
                'provider_tier' => $user->auto_tier ?? 'standard',
                'provider_earnings_percentage' => $user->getSellerEarningsPercentage(),

                // Discord OAuth fields
                'discord_id' => $user->discord_id,
                'discord_username' => $user->discord_username,
                'discord_avatar' => $user->discord_avatar,
                'discord_banner' => $user->discord_banner,
                'discord_accent_color' => $user->discord_accent_color,

                // Custom S3 uploads
                'custom_avatar' => $user->custom_avatar,
                'custom_banner' => $user->custom_banner,

                // Computed URLs (prioritized)
                'avatar_url' => $user->getAvatarUrl(),
                'banner_url' => $user->getBannerUrl(),
                'accent_color' => $user->getAccentColor(),

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
            'email_visible' => 'sometimes|boolean',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|string|max:500',
            'banner' => 'nullable|string|max:500',
            'active_title' => 'nullable|string|max:100',
            'active_profile_theme' => 'nullable|string|max:100',
        ]);

        $user->update($validated);

        // Refresh user to get updated data
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_visible' => (bool) $user->email_visible,
                'bio' => $user->bio,
                'avatar' => $user->avatar,
                'banner' => $user->banner,
                'active_title' => $user->active_title,
                'active_profile_theme' => $user->active_profile_theme,
                'owned_cosmetics' => $user->owned_cosmetics ?? [],
                'badge_inventory' => $user->badge_inventory ?? [],
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'is_seller' => (bool) $user->is_seller,
            ],
        ]);
    }

    /**
     * Show user profile (public - comprehensive view)
     */
    public function show($userId): JsonResponse
    {
        $user = User::with([
            'achievements' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('user_achievements.unlocked_at', 'desc')
                      ->limit(20);
            }
        ])->find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if viewing own profile or is admin
        $authUser = auth('sanctum')->user();
        $canSeeEmail = $authUser && ($authUser->id === $user->id || $authUser->role === 'admin') || $user->email_visible;

        // Get seller stats
        $sellerStats = null;
        if ($user->is_seller) {
            $totalSales = $user->sellerOrders()->where('payment_status', 'completed')->count();
            $averageRating = $user->receivedReviews()->avg('rating') ?? 0;
            $totalReviews = $user->receivedReviews()->count();

            $sellerStats = [
                'total_sales' => $totalSales,
                'monthly_sales' => $user->monthly_sales ?? 0,
                'lifetime_sales' => $user->lifetime_sales ?? 0,
                'average_rating' => round($averageRating, 2),
                'total_reviews' => $totalReviews,
                'seller_tier' => $user->seller_tier,
                'auto_tier' => $user->auto_tier,
            ];
        }

        // Get recent reviews
        $recentReviews = $user->receivedReviews()
            ->with(['user:id,name,avatar,discord_id,discord_avatar,custom_avatar'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'reviewer' => [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                        'avatar' => $review->user->avatar,
                        'avatar_url' => $review->user->getAvatarUrl(),
                    ],
                ];
            });

        // Get unlocked achievements
        $achievements = $user->achievements->map(function ($achievement) {
            return [
                'id' => $achievement->id,
                'name' => $achievement->name,
                'slug' => $achievement->slug,
                'icon' => $achievement->icon,
                'tier' => $achievement->tier,
                'unlocked_at' => $achievement->pivot->unlocked_at,
            ];
        });

        // Get active products if seller
        $products = [];
        if ($user->is_seller) {
            // Get items
            $items = \App\Models\Item::where('user_id', $user->id)
                ->where('is_active', true)
                ->with('game')
                ->limit(12)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'item',
                        'title' => $item->title,
                        'description' => $item->description,
                        'price' => $item->price,
                        'stock_quantity' => $item->stock_quantity,
                        'image' => $item->image,
                        'game' => [
                            'id' => $item->game->id,
                            'title' => $item->game->title,
                        ],
                        'created_at' => $item->created_at,
                    ];
                });

            // Get currencies
            $currencies = \App\Models\Currency::where('user_id', $user->id)
                ->where('is_active', true)
                ->with('game')
                ->limit(12)
                ->get()
                ->map(function ($currency) {
                    return [
                        'id' => $currency->id,
                        'type' => 'currency',
                        'title' => $currency->currency_type,
                        'description' => $currency->description,
                        'price' => $currency->price_per_unit,
                        'stock_quantity' => $currency->quantity_available,
                        'image' => $currency->image,
                        'game' => [
                            'id' => $currency->game->id,
                            'title' => $currency->game->title,
                        ],
                        'created_at' => $currency->created_at,
                    ];
                });

            // Get accounts
            $accounts = \App\Models\Account::where('user_id', $user->id)
                ->where('is_active', true)
                ->with('game')
                ->limit(12)
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'type' => 'account',
                        'title' => $account->title,
                        'description' => $account->description,
                        'price' => $account->price,
                        'stock_quantity' => 1,
                        'image' => $account->image,
                        'game' => [
                            'id' => $account->game->id,
                            'title' => $account->game->title,
                        ],
                        'created_at' => $account->created_at,
                    ];
                });

            // Get services
            $services = \App\Models\Service::where('user_id', $user->id)
                ->where('is_active', true)
                ->with('game')
                ->limit(12)
                ->get()
                ->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'type' => 'service',
                        'title' => $service->title,
                        'description' => $service->description,
                        'price' => $service->price,
                        'stock_quantity' => null,
                        'image' => $service->image,
                        'game' => [
                            'id' => $service->game->id,
                            'title' => $service->game->title,
                        ],
                        'created_at' => $service->created_at,
                    ];
                });

            // Merge all products and sort by created_at
            $products = $items->concat($currencies)
                ->concat($accounts)
                ->concat($services)
                ->sortByDesc('created_at')
                ->take(12)
                ->values();
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'banner' => $user->banner,
            'bio' => $user->bio,
            'is_seller' => (bool) $user->is_seller,
            'subscription_tier' => $user->getSubscriptionTier(),
            'created_at' => $user->created_at,

            // Discord OAuth fields
            'discord_id' => $user->discord_id,
            'discord_username' => $user->discord_username,
            'discord_avatar' => $user->discord_avatar,
            'discord_banner' => $user->discord_banner,
            'discord_accent_color' => $user->discord_accent_color,

            // Custom S3 uploads
            'custom_avatar' => $user->custom_avatar,
            'custom_banner' => $user->custom_banner,

            // Computed URLs (prioritized)
            'avatar_url' => $user->getAvatarUrl(),
            'banner_url' => $user->getBannerUrl(),
            'accent_color' => $user->getAccentColor(),

            // Cosmetics
            'owned_cosmetics' => $user->owned_cosmetics ?? [],
            'badge_inventory' => $user->badge_inventory ?? [],
            'active_profile_theme' => $user->active_profile_theme,
            'active_title' => $user->active_title,

            // Stats
            'achievement_points' => $user->achievement_points ?? 0,
            'total_achievements' => $achievements->count(),
            'seller_stats' => $sellerStats,
        ];

        // Only include email if allowed
        if ($canSeeEmail) {
            $userData['email'] = $user->email;
        }

        return response()->json([
            'success' => true,
            'user' => $userData,
            'achievements' => $achievements,
            'reviews' => $recentReviews,
            'products' => $products,
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

        // Check if viewing own profile or is admin
        $authUser = auth('sanctum')->user();
        $canSeeEmail = $authUser && ($authUser->id === $user->id || $authUser->role === 'admin') || $user->email_visible;

        $userData = [
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
        ];

        // Only include email if allowed
        if ($canSeeEmail) {
            $userData['email'] = $user->email;
        }

        return response()->json($userData);
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
                    'rating' => 0,
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
            'rating' => 0,
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

    /**
     * Get a seller's accepted payment methods
     */
    public function getPaymentMethods(Request $request, $userId): JsonResponse
    {
        $seller = User::findOrFail($userId);

        if (!$seller->is_seller) {
            return response()->json([
                'error' => 'User is not a seller'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'payment_methods' => $seller->getAcceptedPaymentMethods(),
            'has_payment_methods' => $seller->hasAnyPaymentMethod(),
        ]);
    }
}
