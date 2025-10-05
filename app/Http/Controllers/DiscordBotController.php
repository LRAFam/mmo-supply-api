<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Item;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Service;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscordBotController extends Controller
{
    /**
     * Get leaderboard data for Discord bot
     */
    public function getLeaderboard(Request $request)
    {
        $type = $request->query('type', 'sellers');

        switch ($type) {
            case 'sellers':
                $users = User::where('is_seller', true)
                    ->where('lifetime_sales', '>', 0)
                    ->orderBy('lifetime_sales', 'desc')
                    ->limit(10)
                    ->get(['id', 'name', 'lifetime_sales', 'seller_tier', 'avatar']);

                return response()->json($users->map(function ($user) {
                    return [
                        'name' => $user->name,
                        'lifetime_sales' => $user->lifetime_sales ?? 0,
                        'total_orders' => 0, // Can add if tracking
                        'seller_tier' => $user->seller_tier,
                        'avatar' => $user->avatar,
                    ];
                }));

            case 'buyers':
                // Mock data - implement if you track buyer stats
                return response()->json([
                    ['name' => 'TopBuyer1', 'total_spent' => 5000, 'total_purchases' => 50],
                    ['name' => 'Buyer2', 'total_spent' => 3000, 'total_purchases' => 30],
                ]);

            case 'active':
                $users = User::withCount(['orders' as 'total_transactions'])
                    ->orderBy('total_transactions', 'desc')
                    ->limit(10)
                    ->get(['id', 'name']);

                return response()->json($users);

            case 'rated':
                // Mock data - implement if needed
                return response()->json([
                    ['name' => 'TopRatedSeller', 'average_rating' => 4.9, 'review_count' => 120],
                ]);

            default:
                return response()->json(['error' => 'Invalid leaderboard type'], 400);
        }
    }

    /**
     * Get recent listings since a specific time
     */
    public function getRecentListings(Request $request)
    {
        $since = $request->query('since');
        $limit = $request->query('limit', 5);

        $query = Item::where('is_active', true)
            ->with(['user:id,name', 'game:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($since) {
            $query->where('created_at', '>', $since);
        }

        $listings = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => $item->price,
                'stock' => $item->stock,
                'seller' => $item->user->name ?? 'Unknown',
                'game' => $item->game->name ?? 'Unknown',
                'type' => 'item',
                'created_at' => $item->created_at,
            ];
        });

        return response()->json($listings);
    }

    /**
     * Get user profile by username
     */
    public function getUserProfile($username)
    {
        $user = User::where('name', $username)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => null, // Don't expose email
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'role' => $user->role,
            'is_seller' => $user->is_seller,
            'seller_tier' => $user->seller_tier,
            'lifetime_sales' => $user->lifetime_sales ?? 0,
            'monthly_sales' => $user->monthly_sales ?? 0,
            'total_referrals' => $user->total_referrals ?? 0,
            'total_referral_earnings' => $user->total_referral_earnings ?? 0,
        ]);
    }

    /**
     * Search across all marketplace types
     */
    public function search(Request $request)
    {
        $query = $request->query('q');
        $type = $request->query('type', 'all');
        $limit = $request->query('limit', 10);

        if (!$query) {
            return response()->json(['error' => 'Query parameter required'], 400);
        }

        $results = [];

        if ($type === 'all' || $type === 'items') {
            $items = Item::where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%");
                })
                ->with(['user:id,name', 'game:id,name'])
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'price' => $item->price,
                        'stock' => $item->stock,
                        'type' => 'item',
                        'seller' => $item->user->name ?? 'Unknown',
                        'game' => $item->game->name ?? null,
                    ];
                });
            $results = array_merge($results, $items->toArray());
        }

        if ($type === 'all' || $type === 'accounts') {
            $accounts = Account::where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%");
                })
                ->with(['user:id,name', 'game:id,name'])
                ->limit($limit)
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'name' => $account->name,
                        'description' => $account->description,
                        'price' => $account->price,
                        'stock' => $account->stock,
                        'type' => 'account',
                        'seller' => $account->user->name ?? 'Unknown',
                        'game' => $account->game->name ?? null,
                    ];
                });
            $results = array_merge($results, $accounts->toArray());
        }

        return response()->json(array_slice($results, 0, $limit));
    }

    /**
     * Get platform statistics
     */
    public function getStats()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_sellers' => User::where('is_seller', true)->count(),
            'total_items' => Item::where('is_active', true)->count(),
            'total_accounts' => Account::where('is_active', true)->count(),
            'total_services' => Service::where('is_active', true)->count(),
            'total_listings' => Item::where('is_active', true)->count() +
                              Account::where('is_active', true)->count() +
                              Service::where('is_active', true)->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'total_volume' => Order::where('status', 'completed')->sum('total') ?? 0,
            'active_games' => DB::table('games')->count(),
            'active_sellers' => User::where('is_seller', true)->where('is_active', true)->count(),
        ]);
    }
}
