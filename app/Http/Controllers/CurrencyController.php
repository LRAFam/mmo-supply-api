<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Currency::with(['user', 'game'])
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
            $query->where('price_per_unit', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price_per_unit', '<=', $request->max_price);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $currencies = $query->paginate($request->get('per_page', 20));

        return response()->json($currencies);
    }

    public function show($id): JsonResponse
    {
        $currency = Currency::with(['user', 'game', 'reviews.user'])->findOrFail($id);
        return response()->json($currency);
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
            'min_amount' => 'nullable|integer|min:1',
            'max_amount' => 'nullable|integer|min:1',
            'amount' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'bulk_pricing' => 'nullable|array',
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

        $currency = Currency::create([
            'user_id' => $request->user()->id,
            'game_id' => $validated['game_id'],
            'title' => $validated['title'],
            'name' => $validated['title'], // Keep name for compatibility
            'slug' => \Illuminate\Support\Str::slug($validated['title']) . '-' . uniqid(),
            'description' => $validated['description'],
            'price_per_unit' => $validated['price'],
            'discount_price' => $validated['discount_price'] ?? null,
            'stock' => $validated['stock'],
            'min_amount' => $validated['min_amount'] ?? 1,
            'max_amount' => $validated['max_amount'] ?? null,
            'images' => $validated['images'] ?? [],
            'tags' => $validated['tags'] ?? [],
            'bulk_pricing' => $validated['bulk_pricing'] ?? null,
            'delivery_method' => $validated['delivery_method'] ?? null,
            'requirements' => $validated['requirements'] ?? null,
            'warranty_days' => $validated['warranty_days'] ?? 0,
            'refund_policy' => $validated['refund_policy'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'auto_deactivate' => $validated['auto_deactivate'] ?? false,
            'is_active' => true,
            'featured_until' => $validated['featured_until'] ?? null,
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

        return response()->json($currency, 201);
    }
}
