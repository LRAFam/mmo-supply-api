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
            'stock' => 'required|integer|min:1',
            'amount' => 'nullable|string',
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

        $currency = Currency::create([
            'user_id' => $request->user()->id,
            'game_id' => $validated['game_id'],
            'name' => $validated['title'],
            'slug' => \Illuminate\Support\Str::slug($validated['title']) . '-' . uniqid(),
            'description' => $validated['description'] . ($validated['amount'] ? "\n\nAmount: " . $validated['amount'] : ''),
            'price_per_unit' => $validated['price'],
            'stock' => $validated['stock'],
            'images' => $images,
            'is_active' => true,
        ]);

        return response()->json($currency, 201);
    }
}
