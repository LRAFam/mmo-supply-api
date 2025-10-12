<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalize product_type values in order_items table (plural -> singular)
        DB::statement("
            UPDATE order_items
            SET product_type =
                CASE
                    WHEN product_type = 'currencies' THEN 'currency'
                    WHEN product_type = 'items' THEN 'item'
                    WHEN product_type = 'services' THEN 'service'
                    WHEN product_type = 'accounts' THEN 'account'
                    ELSE product_type
                END
        ");

        // Normalize product_type values in carts table (if items stored with product_type)
        // The cart items are stored as JSON, so we need to update the JSON structure
        DB::table('carts')->get()->each(function ($cart) {
            if (!$cart->items) return;

            $items = json_decode($cart->items, true);
            if (!is_array($items)) return;

            $updated = false;
            foreach ($items as &$item) {
                if (!isset($item['product_type'])) continue;

                $original = $item['product_type'];
                $item['product_type'] = match($original) {
                    'currencies' => 'currency',
                    'items' => 'item',
                    'services' => 'service',
                    'accounts' => 'account',
                    default => $original,
                };

                if ($item['product_type'] !== $original) {
                    $updated = true;
                }
            }

            if ($updated) {
                DB::table('carts')
                    ->where('id', $cart->id)
                    ->update(['items' => json_encode($items)]);
            }
        });

        // Normalize reviewable_type values in reviews table
        DB::statement("
            UPDATE reviews
            SET reviewable_type =
                CASE
                    WHEN reviewable_type = 'currencies' THEN 'currency'
                    WHEN reviewable_type = 'items' THEN 'item'
                    WHEN reviewable_type = 'services' THEN 'service'
                    WHEN reviewable_type = 'accounts' THEN 'account'
                    ELSE reviewable_type
                END
        ");

        // Normalize product_type values in featured_listings table
        DB::statement("
            UPDATE featured_listings
            SET product_type =
                CASE
                    WHEN product_type = 'currencies' THEN 'currency'
                    WHEN product_type = 'items' THEN 'item'
                    WHEN product_type = 'services' THEN 'service'
                    WHEN product_type = 'accounts' THEN 'account'
                    ELSE product_type
                END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to plural forms
        DB::statement("
            UPDATE order_items
            SET product_type =
                CASE
                    WHEN product_type = 'currency' THEN 'currencies'
                    WHEN product_type = 'item' THEN 'items'
                    WHEN product_type = 'service' THEN 'services'
                    WHEN product_type = 'account' THEN 'accounts'
                    ELSE product_type
                END
        ");

        // Revert carts
        DB::table('carts')->get()->each(function ($cart) {
            if (!$cart->items) return;

            $items = json_decode($cart->items, true);
            if (!is_array($items)) return;

            $updated = false;
            foreach ($items as &$item) {
                if (!isset($item['product_type'])) continue;

                $original = $item['product_type'];
                $item['product_type'] = match($original) {
                    'currency' => 'currencies',
                    'item' => 'items',
                    'service' => 'services',
                    'account' => 'accounts',
                    default => $original,
                };

                if ($item['product_type'] !== $original) {
                    $updated = true;
                }
            }

            if ($updated) {
                DB::table('carts')
                    ->where('id', $cart->id)
                    ->update(['items' => json_encode($items)]);
            }
        });

        // Revert reviews
        DB::statement("
            UPDATE reviews
            SET reviewable_type =
                CASE
                    WHEN reviewable_type = 'currency' THEN 'currencies'
                    WHEN reviewable_type = 'item' THEN 'items'
                    WHEN reviewable_type = 'service' THEN 'services'
                    WHEN reviewable_type = 'account' THEN 'accounts'
                    ELSE reviewable_type
                END
        ");

        // Revert featured_listings
        DB::statement("
            UPDATE featured_listings
            SET product_type =
                CASE
                    WHEN product_type = 'currency' THEN 'currencies'
                    WHEN product_type = 'item' THEN 'items'
                    WHEN product_type = 'service' THEN 'services'
                    WHEN product_type = 'account' THEN 'accounts'
                    ELSE product_type
                END
        ");
    }
};
