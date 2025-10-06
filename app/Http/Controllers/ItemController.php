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
            'stock' => 'required|integer|min:1',
            'image_url' => 'nullable|string',
            'images' => 'nullable|string',
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

        $item = Item::create([
            'user_id' => $request->user()->id,
            'game_id' => $validated['game_id'],
            'name' => $validated['title'],
            'slug' => \Illuminate\Support\Str::slug($validated['title']) . '-' . uniqid(),
            'description' => $validated['description'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'images' => $images,
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

        return response()->json($item, 201);
    }
}
