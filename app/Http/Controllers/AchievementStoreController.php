<?php

namespace App\Http\Controllers;

use App\Models\AchievementStoreItem;
use App\Models\UserStorePurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AchievementStoreController extends Controller
{
    /**
     * Get all available store items
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $category = $request->query('category');
        $rarity = $request->query('rarity');

        $query = AchievementStoreItem::available();

        if ($category) {
            $query->where('category', $category);
        }

        if ($rarity) {
            $query->where('rarity', $rarity);
        }

        $items = $query->orderBy('category')
            ->orderBy('points_cost')
            ->get()
            ->map(function ($item) use ($user) {
                $canPurchase = $item->canPurchase($user);

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'description' => $item->description,
                    'category' => $item->category,
                    'icon' => $item->icon,
                    'points_cost' => $item->points_cost,
                    'rarity' => $item->rarity,
                    'rarity_color' => $item->getRarityColor(),
                    'is_limited' => $item->is_limited,
                    'available_until' => $item->available_until,
                    'max_uses' => $item->max_uses,
                    'cooldown_days' => $item->cooldown_days,
                    'can_purchase' => $canPurchase['can_purchase'],
                    'purchase_reason' => $canPurchase['reason'],
                    'owned' => $user->ownsCosmetic($item->slug),
                ];
            });

        return response()->json([
            'items' => $items,
            'user' => [
                'achievement_points' => $user->achievement_points,
                'owned_cosmetics' => $user->owned_cosmetics ?? [],
                'badge_inventory' => $user->badge_inventory ?? [],
            ],
            'categories' => [
                'profile_theme' => 'Profile Themes',
                'badge' => 'Badges',
                'title' => 'Titles',
                'frame' => 'Frames',
                'username_effect' => 'Username Effects',
                'marketplace_perk' => 'Marketplace Perks',
                'listing_boost' => 'Listing Boosts',
                'functional' => 'Functional',
                'social' => 'Social',
                'seasonal' => 'Seasonal',
            ],
        ]);
    }

    /**
     * Purchase a store item
     */
    public function purchase(Request $request, $itemId)
    {
        $user = $request->user();
        $item = AchievementStoreItem::findOrFail($itemId);

        // Check if user can purchase
        $canPurchase = $item->canPurchase($user);
        if (!$canPurchase['can_purchase']) {
            return response()->json([
                'message' => $canPurchase['reason'],
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Deduct points
            $user->decrement('achievement_points', $item->points_cost);

            // Record purchase
            $purchase = UserStorePurchase::create([
                'user_id' => $user->id,
                'store_item_id' => $item->id,
                'points_spent' => $item->points_cost,
                'purchased_at' => now(),
                'is_active' => true,
            ]);

            // Apply item effect
            $applied = $item->applyToUser($user);

            if (!$applied) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Failed to apply item effect',
                ], 500);
            }

            DB::commit();

            return response()->json([
                'message' => 'Item purchased successfully',
                'purchase' => $purchase,
                'remaining_points' => $user->achievement_points,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to purchase item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's purchase history
     */
    public function purchases(Request $request)
    {
        $user = $request->user();

        $purchases = $user->storePurchases()
            ->with('storeItem')
            ->orderBy('purchased_at', 'desc')
            ->get()
            ->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'item_name' => $purchase->storeItem->name,
                    'item_slug' => $purchase->storeItem->slug,
                    'item_category' => $purchase->storeItem->category,
                    'points_spent' => $purchase->points_spent,
                    'purchased_at' => $purchase->purchased_at,
                    'used_at' => $purchase->used_at,
                    'times_used' => $purchase->times_used,
                    'has_remaining_uses' => $purchase->hasRemainingUses(),
                    'is_on_cooldown' => $purchase->isOnCooldown(),
                    'cooldown_ends_at' => $purchase->getCooldownEndsAt(),
                ];
            });

        return response()->json([
            'purchases' => $purchases,
        ]);
    }

    /**
     * Get user's active perks
     */
    public function activePerks(Request $request)
    {
        $user = $request->user();

        $perks = $user->activePerks()
            ->active()
            ->with('storeItem')
            ->orderBy('expires_at')
            ->get()
            ->map(function ($perk) {
                return [
                    'id' => $perk->id,
                    'perk_type' => $perk->perk_type,
                    'item_name' => $perk->storeItem->name,
                    'perk_value' => $perk->getValue(),
                    'activated_at' => $perk->activated_at,
                    'expires_at' => $perk->expires_at,
                    'time_remaining' => $perk->getTimeRemaining(),
                    'uses_remaining' => $perk->uses_remaining,
                    'is_active' => $perk->isActive(),
                ];
            });

        return response()->json([
            'active_perks' => $perks,
        ]);
    }

    /**
     * Activate/apply a cosmetic item
     */
    public function activateCosmetic(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'type' => 'required|in:profile_theme,title',
        ]);

        $user = $request->user();
        $slug = $request->slug;
        $type = $request->type;

        // Check if user owns the cosmetic
        if (!$user->ownsCosmetic($slug)) {
            return response()->json([
                'message' => 'You do not own this cosmetic',
            ], 403);
        }

        // Activate the cosmetic
        $field = $type === 'profile_theme' ? 'active_profile_theme' : 'active_title';
        $user->update([$field => $slug]);

        return response()->json([
            'message' => 'Cosmetic activated successfully',
            'active_cosmetic' => [
                'type' => $type,
                'slug' => $slug,
            ],
        ]);
    }

    /**
     * Get user's cosmetics inventory
     */
    public function inventory(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'owned_cosmetics' => $user->owned_cosmetics ?? [],
            'badge_inventory' => $user->badge_inventory ?? [],
            'active_profile_theme' => $user->active_profile_theme,
            'active_title' => $user->active_title,
            'achievement_points' => $user->achievement_points,
        ]);
    }
}
