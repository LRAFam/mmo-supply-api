<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Services\SimilarListingsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * BaseProductController - Abstract controller for all product types
 *
 * This controller consolidates common logic shared across Currency, Item, Service, and Account controllers.
 * It provides a standardized approach to CRUD operations, filtering, and business logic for all product types.
 *
 * Architecture:
 * - Child controllers must implement abstract methods to define type-specific behavior
 * - Common operations (index, show, store, update, destroy) are handled here
 * - Uses ProductType enum to dynamically determine model class, price field, and stock behavior
 * - Provides hooks for custom filtering and validation rules
 *
 * Child classes must implement:
 * - getProductType(): Returns the ProductType enum for this controller
 * - getValidationRules(): Returns validation rules specific to this product type
 */
abstract class BaseProductController extends Controller
{
    /**
     * Get the ProductType enum for this controller
     *
     * This method must be implemented by child classes to specify which product type
     * they handle (CURRENCY, ITEM, SERVICE, or ACCOUNT)
     *
     * @return ProductType
     */
    abstract protected function getProductType(): ProductType;

    /**
     * Get validation rules for store/update operations
     *
     * Child classes implement this to provide type-specific validation rules.
     * Common rules are merged in the base controller.
     *
     * @param bool $isUpdate Whether these rules are for an update operation (uses 'sometimes' instead of 'required')
     * @return array
     */
    abstract protected function getValidationRules(bool $isUpdate = false): array;

    /**
     * List products with filtering, sorting, and pagination
     *
     * Common filters:
     * - search: Search by title/name
     * - game_id: Filter by game
     * - min_price/max_price: Price range filter
     * - featured: Filter by featured status
     * - sort_by/sort_order: Sorting options
     * - per_page: Pagination size
     *
     * Additional filters can be added via applyCustomFilters() in child classes
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $productType = $this->getProductType();
        $modelClass = $productType->getModelClass();

        // Build base query with common relationships
        $query = $modelClass::with(['user', 'game'])
            ->where('is_active', true);

        // Apply stock filter for product types that have stock
        if ($productType->hasStock()) {
            $query->where('stock', '>', 0);
        }

        // Apply common filters (search, game, price)
        $this->applyCommonFilters($query, $request);

        // Apply custom filters from child classes
        $this->applyCustomFilters($query, $request);

        // Handle sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Show a single product with all relationships
     *
     * Returns the product with:
     * - user: The seller/owner
     * - game: The game this product belongs to
     * - reviews: All reviews with their users
     *
     * @param string|int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $modelClass = $this->getProductType()->getModelClass();
        $product = $modelClass::with(['user', 'game', 'reviews.user'])->findOrFail($id);

        return response()->json($product);
    }

    /**
     * Get similar listings using SimilarListingsService
     *
     * Uses the SimilarListingsService to find products similar to this one
     * based on game, tags, price, and other criteria.
     *
     * @param string|int $id
     * @return JsonResponse
     */
    public function similar($id): JsonResponse
    {
        $productType = $this->getProductType()->value;
        $similarService = new SimilarListingsService();
        $similar = $similarService->findSimilar($productType, $id);
        // Note: user and game relationships are already loaded by SimilarListingsService

        return response()->json([
            'similar_listings' => $similar
        ]);
    }

    /**
     * Create a new product
     *
     * Handles:
     * - Validation using child class rules
     * - Slug generation
     * - Setting user_id from authenticated user
     * - Provider record creation/update
     * - Type-specific field mapping (price vs price_per_unit)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate request using child class rules
        $validated = $request->validate($this->getValidationRules(false));

        $productType = $this->getProductType();
        $modelClass = $productType->getModelClass();
        $priceField = $productType->getPriceField();

        // Build common attributes
        $attributes = [
            'user_id' => $request->user()->id,
            'game_id' => $validated['game_id'],
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . uniqid(),
            'description' => $validated['description'],
            'discount_price' => $validated['discount_price'] ?? null,
            'images' => $validated['images'] ?? [],
            'tags' => $validated['tags'] ?? [],
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
        ];

        // Handle price field based on product type (price vs price_per_unit)
        $attributes[$priceField] = $validated['price'] ?? 0;

        // Add name field for compatibility (some models have both name and title)
        if (in_array($productType, [ProductType::CURRENCY, ProductType::ITEM])) {
            $attributes['name'] = $validated['title'];
        }

        // Merge in any additional validated fields not handled above
        // This allows child-specific fields to be included automatically
        $commonFields = array_keys($attributes);
        foreach ($validated as $key => $value) {
            if (!in_array($key, $commonFields) && $key !== 'price') {
                $attributes[$key] = $value;
            }
        }

        // Create the product
        $product = $modelClass::create($attributes);

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

        // Send Discord webhook notification
        try {
            $webhookService = new \App\Services\DiscordWebhookService();
            $webhookService->sendNewListing($product->load(['user', 'game']), $productType->value);
        } catch (\Exception $e) {
            // Log but don't fail the request if webhook fails
            \Log::error('Failed to send Discord webhook: ' . $e->getMessage());
        }

        return response()->json($product, 201);
    }

    /**
     * Update an existing product
     *
     * Handles:
     * - Ownership verification
     * - Validation using child class rules
     * - Selective updates (only provided fields)
     *
     * @param Request $request
     * @param string|int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $modelClass = $this->getProductType()->getModelClass();
        $product = $modelClass::findOrFail($id);

        // Verify ownership
        if (!$this->checkOwnership($product, $request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate using update rules (with 'sometimes' instead of 'required')
        $validated = $request->validate($this->getValidationRules(true));

        // Update the product
        $product->update($validated);

        return response()->json($product);
    }

    /**
     * Delete a product
     *
     * Handles:
     * - Ownership verification
     * - Soft or hard delete (depends on model configuration)
     *
     * @param Request $request
     * @param string|int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $productType = $this->getProductType();
        $modelClass = $productType->getModelClass();
        $product = $modelClass::findOrFail($id);

        // Verify ownership
        if (!$this->checkOwnership($product, $request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->delete();

        $productName = ucfirst($productType->value);
        return response()->json(['message' => "{$productName} deleted successfully"]);
    }

    /**
     * Apply common filters to the query
     *
     * Common filters applied:
     * - search: Search in title/name fields
     * - game_id: Filter by game
     * - min_price/max_price: Price range filtering (uses correct price field per type)
     * - featured: Filter by featured status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    protected function applyCommonFilters($query, Request $request): void
    {
        $productType = $this->getProductType();
        $priceField = $productType->getPriceField();

        // Search filter - searches both title and name fields
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%");

                // Some models have both title and name fields
                if (\Schema::hasColumn($q->getModel()->getTable(), 'name')) {
                    $q->orWhere('name', 'like', "%{$searchTerm}%");
                }
            });
        }

        // Game filter
        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        // Price range filters - use the correct price field for this product type
        if ($request->has('min_price')) {
            $query->where($priceField, '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where($priceField, '<=', $request->max_price);
        }

        // Featured filter
        if ($request->has('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }
    }

    /**
     * Apply custom filters specific to the product type
     *
     * Override this method in child classes to add type-specific filters.
     * For example:
     * - AccountController can filter by min_level
     * - CurrencyController can filter by min_amount/max_amount
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    protected function applyCustomFilters($query, Request $request): void
    {
        // Override in child classes to add custom filters
    }

    /**
     * Check if the user owns the product
     *
     * @param mixed $product The product model instance
     * @param mixed $user The authenticated user
     * @return bool
     */
    protected function checkOwnership($product, $user): bool
    {
        return $product->user_id === $user->id;
    }

    /**
     * Get the model instance for this product type
     *
     * @return string The fully qualified model class name
     */
    protected function getModelClass(): string
    {
        return $this->getProductType()->getModelClass();
    }

    /**
     * Get common validation rules shared across all product types
     *
     * These rules are merged with child-specific rules.
     *
     * @param bool $isUpdate Whether these rules are for an update operation
     * @return array
     */
    protected function getCommonValidationRules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';
        $nullable = 'nullable';

        return [
            'game_id' => "{$required}|exists:games,id",
            'title' => "{$required}|string|max:255",
            'description' => "{$required}|string",
            'price' => ($isUpdate ? 'nullable' : 'required') . '|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'delivery_method' => 'nullable|string',
            'requirements' => 'nullable|string',
            'warranty_days' => 'nullable|integer|min:0',
            'refund_policy' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'auto_deactivate' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'featured_until' => 'nullable|date',
        ];
    }
}
