<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Item::with(['user', 'game'])
            ->where('is_active', true)
            ->where('stock', '>', 0);

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
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

        // Filter by featured
        if ($request->has('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $items = $query->paginate($request->get('per_page', 20));

        return response()->json($items);
    }

    public function show($id): JsonResponse
    {
        $item = Item::with(['user', 'game', 'reviews.user'])->findOrFail($id);
        return response()->json($item);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => 'required|exists:games,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:1',
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'variants' => 'nullable|array',
            'delivery_method' => 'nullable|string',
            'delivery_time' => 'nullable|string',
            'requirements' => 'nullable|string',
            'warranty_days' => 'nullable|integer|min:0',
            'refund_policy' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'auto_deactivate' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'featured_until' => 'nullable|date',
        ]);

        $item = Item::create([
            'user_id' => $request->user()->id,
            'game_id' => $validated['game_id'],
            'title' => $validated['title'],
            'name' => $validated['title'], // Keep name for compatibility
            'slug' => \Illuminate\Support\Str::slug($validated['title']) . '-' . uniqid(),
            'description' => $validated['description'],
            'price' => $validated['price'],
            'discount_price' => $validated['discount_price'] ?? null,
            'stock' => $validated['stock'],
            'min_quantity' => $validated['min_quantity'] ?? 1,
            'max_quantity' => $validated['max_quantity'] ?? null,
            'images' => $validated['images'] ?? [],
            'tags' => $validated['tags'] ?? [],
            'variants' => $validated['variants'] ?? null,
            'delivery_method' => $validated['delivery_method'] ?? null,
            'delivery_time' => $validated['delivery_time'] ?? null,
            'requirements' => $validated['requirements'] ?? null,
            'warranty_days' => $validated['warranty_days'] ?? 0,
            'refund_policy' => $validated['refund_policy'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'auto_deactivate' => $validated['auto_deactivate'] ?? false,
            'is_featured' => $validated['is_featured'] ?? false,
            'featured_until' => $validated['featured_until'] ?? null,
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

        return response()->json($item, 201);
    }
}
