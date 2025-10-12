<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Get user's wishlist
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $wishlists = Wishlist::where('user_id', $user->id)
            ->with('wishlistable')
            ->latest()
            ->get()
            ->map(function ($wishlist) {
                $item = $wishlist->wishlistable;

                if (!$item) {
                    return null;
                }

                return [
                    'id' => $wishlist->id,
                    'type' => class_basename($wishlist->wishlistable_type),
                    'item' => $item,
                    'added_at' => $wishlist->created_at,
                ];
            })
            ->filter(); // Remove null entries

        return response()->json([
            'success' => true,
            'wishlists' => $wishlists->values(),
        ]);
    }

    /**
     * Add item to wishlist
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:Item,Currency,Account,Service',
            'id' => 'required|integer',
        ]);

        $user = Auth::user();
        $type = 'App\\Models\\' . $request->type;
        $itemId = $request->id;

        // Check if item exists
        $item = $type::find($itemId);
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found',
            ], 404);
        }

        // Check if already in wishlist
        $existing = Wishlist::where('user_id', $user->id)
            ->where('wishlistable_type', $type)
            ->where('wishlistable_id', $itemId)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Item already in wishlist',
            ], 409);
        }

        // Add to wishlist
        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'wishlistable_type' => $type,
            'wishlistable_id' => $itemId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Added to wishlist',
            'wishlist' => $wishlist,
        ], 201);
    }

    /**
     * Remove item from wishlist
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();

        $wishlist = Wishlist::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$wishlist) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist item not found',
            ], 404);
        }

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed from wishlist',
        ]);
    }

    /**
     * Check if item is in user's wishlist
     */
    public function check(Request $request)
    {
        $request->validate([
            'type' => 'required|in:Item,Currency,Account,Service',
            'id' => 'required|integer',
        ]);

        $user = Auth::user();
        $type = 'App\\Models\\' . $request->type;
        $itemId = $request->id;

        $inWishlist = Wishlist::where('user_id', $user->id)
            ->where('wishlistable_type', $type)
            ->where('wishlistable_id', $itemId)
            ->exists();

        return response()->json([
            'success' => true,
            'in_wishlist' => $inWishlist,
        ]);
    }

    /**
     * Toggle item in wishlist (add if not present, remove if present)
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'type' => 'required|in:Item,Currency,Account,Service',
            'id' => 'required|integer',
        ]);

        $user = Auth::user();
        $type = 'App\\Models\\' . $request->type;
        $itemId = $request->id;

        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('wishlistable_type', $type)
            ->where('wishlistable_id', $itemId)
            ->first();

        if ($wishlist) {
            // Remove from wishlist
            $wishlist->delete();
            return response()->json([
                'success' => true,
                'action' => 'removed',
                'message' => 'Removed from wishlist',
                'in_wishlist' => false,
            ]);
        } else {
            // Check if item exists
            $item = $type::find($itemId);
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found',
                ], 404);
            }

            // Add to wishlist
            Wishlist::create([
                'user_id' => $user->id,
                'wishlistable_type' => $type,
                'wishlistable_id' => $itemId,
            ]);

            return response()->json([
                'success' => true,
                'action' => 'added',
                'message' => 'Added to wishlist',
                'in_wishlist' => true,
            ]);
        }
    }
}
