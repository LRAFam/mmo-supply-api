<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Stripe\PaymentIntent;
use Stripe\Transfer;

class MultiOrderService
{
    protected CartGroupingService $cartGroupingService;

    public function __construct(CartGroupingService $cartGroupingService)
    {
        $this->cartGroupingService = $cartGroupingService;
    }

    /**
     * Create split orders from cart (one order per seller)
     */
    public function createOrdersFromCart(Cart $cart, float $platformFeePercentage = 10.0): array
    {
        $orderGroupId = 'OG-' . strtoupper(Str::random(12));
        $sellerGroups = $this->cartGroupingService->calculateFeesPerSeller($cart, $platformFeePercentage);
        $orders = [];

        foreach ($sellerGroups as $sellerId => $group) {
            $order = Order::create([
                'user_id' => $cart->user_id,
                'seller_id' => $sellerId,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'order_group_id' => $orderGroupId,
                'cart_id' => $cart->id,
                'status' => 'pending',
                'subtotal' => $group['subtotal'],
                'tax' => 0,
                'total' => $group['subtotal'],
                'platform_fee' => $group['platform_fee'],
                'seller_payout' => $group['seller_payout'],
                'payment_status' => 'pending',
            ]);

            // Create order items from cart items
            foreach ($group['items'] as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_type' => $cartItem['product_type'],
                    'product_id' => $cartItem['product_id'],
                    'quantity' => $cartItem['quantity'],
                    'price' => $cartItem['price'],
                    'total' => $cartItem['subtotal'],
                ]);
            }

            $orders[] = $order;
        }

        return [
            'order_group_id' => $orderGroupId,
            'orders' => $orders,
            'grand_total' => $sellerGroups->sum('subtotal'),
        ];
    }

    /**
     * Create Stripe PaymentIntent for entire cart (hybrid approach)
     */
    public function createPaymentIntent(array $orderData, string $buyerStripeCustomerId): PaymentIntent
    {
        $paymentIntent = PaymentIntent::create([
            'amount' => (int)($orderData['grand_total'] * 100), // Convert to cents
            'currency' => 'usd',
            'customer' => $buyerStripeCustomerId,
            'metadata' => [
                'order_group_id' => $orderData['order_group_id'],
                'order_count' => count($orderData['orders']),
            ],
        ]);

        // Update all orders with the PaymentIntent ID
        foreach ($orderData['orders'] as $order) {
            $order->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);
        }

        return $paymentIntent;
    }

    /**
     * Create Stripe Transfers to sellers after payment succeeds
     * This is called from webhook handler after payment_intent.succeeded
     */
    public function createTransfersToSellers(string $paymentIntentId): array
    {
        $orders = Order::where('stripe_payment_intent_id', $paymentIntentId)->get();
        $transfers = [];

        foreach ($orders as $order) {
            $seller = $order->seller;

            // Only transfer if seller has Stripe Connect enabled
            if (!$seller->stripe_connect_enabled || !$seller->stripe_connect_id) {
                continue;
            }

            $transfer = Transfer::create([
                'amount' => (int)($order->seller_payout * 100), // Convert to cents
                'currency' => 'usd',
                'destination' => $seller->stripe_connect_id,
                'transfer_group' => $order->order_group_id,
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'seller_id' => $seller->id,
                ],
            ]);

            $order->update([
                'stripe_transfer_id' => $transfer->id,
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);

            $transfers[] = $transfer;
        }

        return $transfers;
    }

    /**
     * Calculate grand total for all orders in a group
     */
    public function getOrderGroupTotal(string $orderGroupId): float
    {
        return Order::where('order_group_id', $orderGroupId)
            ->sum('total');
    }

    /**
     * Get all orders in a group with seller details
     */
    public function getOrderGroupDetails(string $orderGroupId): array
    {
        $orders = Order::where('order_group_id', $orderGroupId)
            ->with(['seller', 'items.product'])
            ->get();

        return [
            'order_group_id' => $orderGroupId,
            'orders' => $orders,
            'grand_total' => $orders->sum('total'),
            'platform_fee_total' => $orders->sum('platform_fee'),
            'seller_payout_total' => $orders->sum('seller_payout'),
        ];
    }
}
