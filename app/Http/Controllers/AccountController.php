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
            'image_url' => 'nullable|string',
            'images' => 'nullable|string',
            'level' => 'nullable|string',
            'rank' => 'nullable|string',
        ]);

        // Prepare images array
        $images = [];
        if (!empty($validated['image_url'])) {
            $images[] = $validated['image_url'];
        }
        if (!empty($validated['images'])) {
            $additionalImages = explode(',', $validated['images']);
            $images = array_merge($images, $additionalImages);
        }

        $account = Account::create([
            'user_id' => $request->user()->id,
            'game_id' => $validated['game_id'],
            'title' => $validated['title'],
            'slug' => \Illuminate\Support\Str::slug($validated['title']) . '-' . uniqid(),
            'description' => $validated['description'],
            'price' => $validated['price'],
            'images' => $images,
            'account_level' => $validated['level'] ?? null,
            'account_stats' => [
                'rank' => $validated['rank'] ?? null,
            ],
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
                'rating' => null,
            ]
        );

        return response()->json($account, 201);
    }
}
