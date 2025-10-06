<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::with(['user', 'game'])
            ->where('is_active', true);

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

        // Filter by featured
        if ($request->has('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $services = $query->paginate($request->get('per_page', 20));

        return response()->json($services);
    }

    public function show($id): JsonResponse
    {
        $service = Service::with(['user', 'game', 'reviews.user'])->findOrFail($id);
        return response()->json($service);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => 'required|exists:games,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'estimated_time' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'packages' => 'nullable|array',
            'addons' => 'nullable|array',
            'schedule' => 'nullable|array',
            'max_concurrent_orders' => 'nullable|integer|min:1',
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

        $service = Service::create([
            'user_id' => $request->user()->id,
            'game_id' => $validated['game_id'],
            'title' => $validated['title'],
            'slug' => \Illuminate\Support\Str::slug($validated['title']) . '-' . uniqid(),
            'description' => $validated['description'],
            'price' => $validated['price'],
            'discount_price' => $validated['discount_price'] ?? null,
            'estimated_time' => $validated['estimated_time'] ?? null,
            'images' => $validated['images'] ?? [],
            'tags' => $validated['tags'] ?? [],
            'packages' => $validated['packages'] ?? null,
            'addons' => $validated['addons'] ?? null,
            'schedule' => $validated['schedule'] ?? null,
            'max_concurrent_orders' => $validated['max_concurrent_orders'] ?? 5,
            'delivery_method' => $validated['delivery_method'] ?? null,
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

        return response()->json($service, 201);
    }
}
