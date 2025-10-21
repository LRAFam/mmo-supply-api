<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Collection;

class CartGroupingService
{
    /**
     * Group cart items by seller and include seller payment methods
     */
    public function groupCartBySeller(Cart $cart): Collection
    {
        $groupedItems = $cart->items()
            ->with(['seller', 'product'])
            ->get()
            ->groupBy('seller_id');

        return $groupedItems->map(function ($items, $sellerId) {
            $seller = $items->first()->seller;

            return [
                'seller' => [
                    'id' => $seller->id,
                    'username' => $seller->username,
                    'payment_methods' => $seller->getAcceptedPaymentMethods(),
                ],
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_type' => $item->product_type,
                        'product_id' => $item->product_id,
                        'product' => $item->product,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                        'buyer_notes' => $item->buyer_notes,
                    ];
                }),
                'subtotal' => $items->sum('subtotal'),
            ];
        });
    }

    /**
     * Find common payment methods across all sellers in cart
     */
    public function getCommonPaymentMethods(Cart $cart): array
    {
        $sellerGroups = $this->groupCartBySeller($cart);

        if ($sellerGroups->isEmpty()) {
            return [];
        }

        // Get all sellers' payment methods
        $allSellerMethods = $sellerGroups->map(function ($group) {
            return collect($group['seller']['payment_methods'])
                ->pluck('provider')
                ->toArray();
        });

        // Find intersection (common methods)
        $commonMethods = $allSellerMethods->reduce(function ($carry, $methods) {
            if ($carry === null) {
                return $methods;
            }
            return array_intersect($carry, $methods);
        });

        // Return full method details for common methods
        $firstSeller = $sellerGroups->first()['seller'];
        return collect($firstSeller['payment_methods'])
            ->whereIn('provider', $commonMethods ?? [])
            ->values()
            ->toArray();
    }

    /**
     * Validate that all sellers accept a specific payment method
     */
    public function validatePaymentMethod(Cart $cart, string $paymentMethod): array
    {
        $sellerGroups = $this->groupCartBySeller($cart);
        $invalidSellers = [];

        foreach ($sellerGroups as $group) {
            $seller = User::find($group['seller']['id']);

            if (!$seller->acceptsPaymentMethod($paymentMethod)) {
                $invalidSellers[] = [
                    'seller_id' => $seller->id,
                    'username' => $seller->username,
                    'accepted_methods' => $seller->getAcceptedPaymentMethods(),
                ];
            }
        }

        return [
            'valid' => empty($invalidSellers),
            'invalid_sellers' => $invalidSellers,
        ];
    }

    /**
     * Calculate platform fees for each seller group
     */
    public function calculateFeesPerSeller(Cart $cart, float $platformFeePercentage = 10.0): Collection
    {
        $sellerGroups = $this->groupCartBySeller($cart);

        return $sellerGroups->map(function ($group) use ($platformFeePercentage) {
            $subtotal = $group['subtotal'];
            $platformFee = round($subtotal * ($platformFeePercentage / 100), 2);
            $sellerPayout = $subtotal - $platformFee;

            return array_merge($group, [
                'platform_fee_percentage' => $platformFeePercentage,
                'platform_fee' => $platformFee,
                'seller_payout' => $sellerPayout,
            ]);
        });
    }
}
