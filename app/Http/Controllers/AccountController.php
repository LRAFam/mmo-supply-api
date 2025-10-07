<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Account::with(['user', 'game'])
            ->where('is_active', true)
            ->where('stock', '>', 0);

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Filter by game
        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by account level
        if ($request->has('min_level')) {
            $query->where('account_level', '>=', $request->min_level);
        }

        // Filter by featured
        if ($request->has('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $accounts = $query->paginate($request->get('per_page', 20));

        return response()->json($accounts);
    }

    public function show($id): JsonResponse
    {
        $account = Account::with(['user', 'game', 'reviews.user'])->findOrFail($id);
        return response()->json($account);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => 'required|exists:games,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'account_level' => 'nullable|string',
            'rank' => 'nullable|string',
            'server_region' => 'nullable|string',
            'email_included' => 'nullable|boolean',
            'email_changeable' => 'nullable|boolean',
            'account_age_days' => 'nullable|integer|min:0',
            'included_items' => 'nullable|array',
            'included_items.*' => 'string',
            'account_stats' => 'nullable|array',
            'delivery_method' => 'nullable|string',
            'requirements' => 'nullable|string',
            'warranty_days' => 'nullable|integer|min:0',
            'refund_policy' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'auto_deactivate' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'featured_until' => 'nullable|date',
        ]);

        $account = Account::create([
            'user_id' => $request->user()->id,
            'game_id' => $validated['game_id'],
            'title' => $validated['title'],
            'slug' => \Illuminate\Support\Str::slug($validated['title']) . '-' . uniqid(),
            'description' => $validated['description'],
            'price' => $validated['price'],
            'discount_price' => $validated['discount_price'] ?? null,
            'images' => $validated['images'] ?? [],
            'tags' => $validated['tags'] ?? [],
            'account_level' => $validated['account_level'] ?? null,
            'rank' => $validated['rank'] ?? null,
            'server_region' => $validated['server_region'] ?? null,
            'email_included' => $validated['email_included'] ?? false,
            'email_changeable' => $validated['email_changeable'] ?? false,
            'account_age_days' => $validated['account_age_days'] ?? null,
            'included_items' => $validated['included_items'] ?? [],
            'account_stats' => $validated['account_stats'] ?? null,
            'warranty_days' => $validated['warranty_days'] ?? 0,
            'refund_policy' => $validated['refund_policy'] ?? null,
            'requirements' => $validated['requirements'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'auto_deactivate' => $validated['auto_deactivate'] ?? false,
            'is_featured' => $validated['is_featured'] ?? false,
            'featured_until' => $validated['featured_until'] ?? null,
            'stock' => 1, // Single account
            'is_active' => true,
        ]);

        // Create or update provider record for this user+game combination
        \App\Models\Provider::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'game_id' => $validated['game_id'],
            ],
            [
                'vouches' => 0,
                'rating' => 0,
            ]
        );

        return response()->json($account, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $account = \App\Models\Account::findOrFail($id);

        // Ensure user owns this account
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'game_id' => 'sometimes|exists:games,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'content' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'account_level' => 'nullable|string',
            'rank' => 'nullable|string',
            'server_region' => 'nullable|string',
            'email_included' => 'nullable|boolean',
            'email_changeable' => 'nullable|boolean',
            'account_age_days' => 'nullable|integer|min:0',
            'included_items' => 'nullable|array',
            'account_stats' => 'nullable|array',
            'delivery_method' => 'nullable|string',
            'delivery_time' => 'nullable|string',
            'requirements' => 'nullable|string',
            'warranty_days' => 'nullable|integer|min:0',
            'refund_policy' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'auto_deactivate' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'featured_until' => 'nullable|date',
        ]);

        $account->update($validated);

        return response()->json($account);
    }
}
