<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Enums\ProductType;

class CartController extends Controller
{
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_type' => 'required|string',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'metadata' => 'nullable|array',
        ]);

        // Normalize product type to singular form
        $productTypeEnum = ProductType::fromString($request->product_type);
        $productType = $productTypeEnum->singularize();

        $user = $request->user();

        // Get or create cart
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['items' => []]
        );

        $items = $cart->items ?? [];

        // Check if item already exists in cart
        $itemExists = false;
        foreach ($items as $key => $item) {
            if ($item['product_type'] === $productType &&
                $item['product_id'] === $request->product_id) {
                $items[$key]['quantity'] += $request->quantity;
                // Update metadata if provided
                if ($request->has('metadata')) {
                    $items[$key]['metadata'] = $request->metadata;
                }
                $itemExists = true;
                break;
            }
        }

        // Add new item if it doesn't exist
        if (!$itemExists) {
            // Load the product to calculate price
            $modelClass = $productTypeEnum->getModelClass();
            $product = $modelClass::find($request->product_id);

            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            // Calculate unit price based on product type and metadata
            $unitPrice = $this->calculateUnitPrice($product, $productTypeEnum, $request->metadata);

            $itemData = [
                'product_type' => $productType,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'unit_price' => $unitPrice, // Store calculated price
            ];

            // Add metadata if provided (for currencies: gold amount, character name, etc.)
            if ($request->has('metadata')) {
                $itemData['metadata'] = $request->metadata;
            }

            $items[] = $itemData;
        }

        $cart->items = $items;
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'cart' => $cart,
        ]);
    }

    public function getCart(Request $request): JsonResponse
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart || empty($cart->items)) {
            return response()->json([]);
        }

        // Load actual product data for each cart item
        $items = collect($cart->items)->map(function ($item) {
            $productTypeStr = $item['product_type'];
            $productId = $item['product_id'];

            // Use ProductType enum to get model class
            $productTypeEnum = ProductType::tryFromString($productTypeStr);
            if (!$productTypeEnum) {
                return null;
            }

            $modelClass = $productTypeEnum->getModelClass();
            $product = $modelClass::with('game')->find($productId);

            if (!$product) {
                return null;
            }

            // Use stored unit_price if available (calculated when added to cart)
            // Otherwise fall back to calculating it now
            if (isset($item['unit_price'])) {
                $finalPrice = floatval($item['unit_price']);
            } else {
                // Legacy: calculate price for old cart items without unit_price
                $finalPrice = $this->calculateUnitPrice($product, $productTypeEnum, $item['metadata'] ?? null);
            }

            // Get base price for display purposes
            $priceField = $productTypeEnum->getPriceField();
            $price = floatval($product->$priceField ?? 0);
            $discount = floatval($product->discount ?? 0);
            $discountPrice = $product->discount_price ? floatval($product->discount_price) : null;

            // For package-based services, append package name to title
            $itemName = $product->name ?? $product->title ?? 'Unknown';
            $itemTitle = $product->title ?? $product->name ?? 'Unknown';
            if ($productTypeEnum->singularize() === 'service' &&
                isset($item['metadata']['package_name'])) {
                $packageName = $item['metadata']['package_name'];
                $itemName .= ' - ' . $packageName;
                $itemTitle .= ' - ' . $packageName;
            }

            $itemData = [
                'id' => $product->id,
                'name' => $itemName,
                'title' => $itemTitle,
                'description' => $product->description ?? '',
                'price' => $price,
                'discount' => $discount,
                'discount_price' => $discountPrice,
                'images' => $product->images ?? [],
                'game' => $product->game ? [
                    'id' => $product->game->id,
                    'title' => $product->game->title,
                ] : null,
            ];

            // For OSRS currency, include price_per_million and stock info
            if ($productTypeEnum->singularize() === 'currency') {
                if (isset($product->price_per_million)) {
                    $itemData['price_per_million'] = floatval($product->price_per_million);
                }
                if (isset($product->currency_stock_amount)) {
                    $itemData['currency_stock_amount'] = $product->currency_stock_amount;
                }
                if (isset($product->listing_intent)) {
                    $itemData['listing_intent'] = $product->listing_intent;
                }
            }

            $cartItem = [
                'id' => $productId . '-' . $productTypeEnum->singularize(),
                'product_type' => $productTypeEnum->singularize(),
                'product_id' => $productId,
                'quantity' => $item['quantity'],
                'item' => $itemData,
                'finalPrice' => $finalPrice,
            ];

            // Include metadata if present (for currencies: gold amount, character name, etc.)
            if (isset($item['metadata'])) {
                $cartItem['metadata'] = $item['metadata'];
            }

            return $cartItem;
        })->filter()->values();

        return response()->json($items);
    }

    public function remove(Request $request): JsonResponse
    {
        $user = $request->user();
        $itemId = $request->input('itemId');

        $cart = Cart::where('user_id', $user->id)->first();
        if ($cart) {
            // Parse the composite ID (format: "productId-productType")
            $parts = explode('-', $itemId, 2);
            if (count($parts) === 2) {
                [$productId, $productTypeStr] = $parts;

                // Normalize product type to singular form
                $productTypeEnum = ProductType::tryFromString($productTypeStr);
                if (!$productTypeEnum) {
                    return response()->json(['error' => 'Invalid product type'], 400);
                }
                $productType = $productTypeEnum->singularize();

                $items = collect($cart->items)->filter(function($item) use ($productId, $productType) {
                    return !($item['product_id'] == $productId && $item['product_type'] == $productType);
                })->values()->toArray();

                $cart->items = $items;
                $cart->save();
            }

            return response()->json($cart->items ?? []);
        }

        return response()->json([]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'product_type' => 'required|string',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        // Normalize product type to singular form
        $productTypeEnum = ProductType::fromString($request->product_type);
        $productType = $productTypeEnum->singularize();

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['error' => 'Cart not found'], 404);
        }

        $items = $cart->items ?? [];
        $updated = false;

        // Find and update the item
        foreach ($items as $key => $item) {
            if ($item['product_type'] === $productType &&
                $item['product_id'] === $request->product_id) {

                if ($request->quantity === 0) {
                    // Remove item if quantity is 0
                    unset($items[$key]);
                } else {
                    // Update quantity
                    $items[$key]['quantity'] = $request->quantity;

                    // Update metadata if provided (for currencies: gold amount, character name, etc.)
                    if ($request->has('metadata')) {
                        $items[$key]['metadata'] = $request->metadata;
                    }
                }

                $updated = true;
                break;
            }
        }

        if (!$updated && $request->quantity > 0) {
            // Item not found, add it
            $itemData = [
                'product_type' => $productType,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ];

            // Add metadata if provided
            if ($request->has('metadata')) {
                $itemData['metadata'] = $request->metadata;
            }

            $items[] = $itemData;
        }

        $cart->items = array_values($items);
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'cart' => $cart,
        ]);
    }

    /**
     * Calculate the unit price for a product based on type and metadata
     */
    private function calculateUnitPrice($product, $productTypeEnum, $metadata = null): float
    {
        $productType = $productTypeEnum->singularize();

        // For OSRS currency with price_per_million
        if ($productType === 'currency' && isset($product->price_per_million) && $product->price_per_million > 0 && isset($metadata['gold_amount'])) {
            $goldInMillions = floatval($metadata['gold_amount']) / 1000000;
            return floatval($product->price_per_million) * $goldInMillions;
        }

        // For package-based services
        if ($productType === 'service' && isset($metadata['package_price'])) {
            return floatval($metadata['package_price']);
        }

        // Regular pricing
        $priceField = $productTypeEnum->getPriceField();
        $price = floatval($product->{$priceField} ?? 0);
        $discount = floatval($product->discount ?? 0);

        return $price - $discount;
    }
}
