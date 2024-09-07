<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Cart;

class CartController extends Controller
{
    public function add(Request $request): JsonResponse
    {
        $userId = $request->user()->id; // Get the authenticated user ID
        $item = $request->input('item');

        $cart = Cart::firstOrCreate(['user_id' => $userId]);
        $existingItem = $cart->items->firstWhere('id', $item['id']);

        if ($existingItem) {
            $existingItem->quantity += $item['quantity'];
        } else {
            $cart->items->push($item);
        }

        $cart->save();
        return response()->json($cart->items);
    }

    public function getCart(Request $request): JsonResponse
    {
        $userId = $request->user()->id; // Get the authenticated user ID
        $cart = Cart::with('items')->where('user_id', $userId)->first();
        return response()->json($cart ? $cart->items : []);
    }

    public function remove(Request $request): JsonResponse
    {
        $userId = $request->user()->id; // Get the authenticated user ID
        $itemId = $request->input('itemId');

        $cart = Cart::where('user_id', $userId)->first();
        if ($cart) {
            $cart->items = $cart->items->filter(fn($item) => $item['id'] != $itemId);
            $cart->save();
        }

        return response()->json($cart->items);
    }

    public function update(Request $request): JsonResponse
    {
        $userId = $request->user()->id; // Get the authenticated user ID
        $items = $request->input('items');

        $cart = Cart::where('user_id', $userId)->first();
        if ($cart) {
            $cart->items = collect($items)->filter(fn($item) => $item['quantity'] > 0);
            $cart->save();
        }

        return response()->json($cart->items);
    }
}
