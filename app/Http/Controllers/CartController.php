<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Cart;

class CartController extends Controller
{
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_type' => 'required|string',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

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
            if ($item['product_type'] === $request->product_type &&
                $item['product_id'] === $request->product_id) {
                $items[$key]['quantity'] += $request->quantity;
                $itemExists = true;
                break;
            }
        }

        // Add new item if it doesn't exist
        if (!$itemExists) {
            $items[] = [
                'product_type' => $request->product_type,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ];
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
            $productType = $item['product_type'];
            $productId = $item['product_id'];

            // Get the model class based on product type (handle both singular and plural)
            $modelClass = match($productType) {
                'currency', 'currencies' => \App\Models\Currency::class,
                'item', 'items' => \App\Models\Item::class,
                'service', 'services' => \App\Models\Service::class,
                'account', 'accounts' => \App\Models\Account::class,
                default => null,
            };

            if (!$modelClass) {
                return null;
            }

            $product = $modelClass::with('game')->find($productId);

            if (!$product) {
                return null;
            }

            // Get price based on product type (handle both singular and plural)
            $price = match($productType) {
                'currency', 'currencies' => floatval($product->price_per_unit ?? 0),
                default => floatval($product->price ?? 0),
            };

            $discount = floatval($product->discount ?? 0);
            $discountPrice = $product->discount_price ? floatval($product->discount_price) : null;

            // Calculate final price: use discount_price if set, otherwise price - discount
            $finalPrice = $discountPrice ?? ($price - $discount);

            return [
                'id' => $productId . '-' . $productType,
                'product_type' => $productType,
                'product_id' => $productId,
                'quantity' => $item['quantity'],
                'item' => [
                    'id' => $product->id,
                    'name' => $product->name ?? $product->title ?? 'Unknown',
                    'title' => $product->title ?? $product->name ?? 'Unknown',
                    'description' => $product->description ?? '',
                    'price' => $price,
                    'discount' => $discount,
                    'discount_price' => $discountPrice,
                    'images' => $product->images ?? [],
                    'game' => $product->game ? [
                        'id' => $product->game->id,
                        'title' => $product->game->title,
                    ] : null,
                ],
                'finalPrice' => $finalPrice,
            ];
        })->filter()->values();

        return response()->json($items);
    }

    public function remove(Request $request): JsonResponse
    {
        $user = $request->user();
        $itemId = $request->input('itemId');

        $cart = Cart::where('user_id', $user->id)->first();
        if ($cart) {
            $items = collect($cart->items)->filter(fn($item) => $item['id'] != $itemId)->values()->toArray();
            $cart->items = $items;
            $cart->save();
            return response()->json($items);
        }

        return response()->json([]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'product_type' => 'required|string',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:0',
        ]);

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['error' => 'Cart not found'], 404);
        }

        $items = $cart->items ?? [];
        $updated = false;

        // Find and update the item
        foreach ($items as $key => $item) {
            if ($item['product_type'] === $request->product_type &&
                $item['product_id'] === $request->product_id) {

                if ($request->quantity === 0) {
                    // Remove item if quantity is 0
                    unset($items[$key]);
                } else {
                    // Update quantity
                    $items[$key]['quantity'] = $request->quantity;
                }

                $updated = true;
                break;
            }
        }

        if (!$updated && $request->quantity > 0) {
            // Item not found, add it
            $items[] = [
                'product_type' => $request->product_type,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ];
        }

        $cart->items = array_values($items);
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'cart' => $cart,
        ]);
    }
}
