<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['items.seller'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    public function sellerOrders(Request $request): JsonResponse
    {
        // Get orders where the seller has items
        $orders = Order::with(['items.seller', 'buyer'])
            ->whereHas('items', function ($query) use ($request) {
                $query->where('seller_id', $request->user()->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Request $request, $id): JsonResponse
    {
        // First check if order exists at all
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $userId = $request->user()->id;

        // Check if user is either the buyer OR a seller in this order
        $isBuyer = $order->user_id === $userId;
        $isSeller = $order->items()->where('seller_id', $userId)->exists();

        if (!$isBuyer && !$isSeller) {
            return response()->json(['error' => 'Unauthorized access to this order'], 403);
        }

        // Load relationships
        $order->load(['items.seller', 'items.reviews', 'buyer']);

        return response()->json($order);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required|string',
            'buyer_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();

            // Get cart
            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart || empty($cart->items)) {
                return response()->json(['error' => 'Cart is empty'], 400);
            }

            $cartItems = $cart->items;

            // Load actual products and calculate totals
            $subtotal = 0;
            $orderItems = [];

            foreach ($cartItems as $cartItem) {
                $productType = $cartItem['product_type'];
                $productId = $cartItem['product_id'];
                $quantity = $cartItem['quantity'];

                // Get the model class (handle both singular and plural)
                $modelClass = match($productType) {
                    'currency', 'currencies' => \App\Models\Currency::class,
                    'item', 'items' => \App\Models\Item::class,
                    'service', 'services' => \App\Models\Service::class,
                    'account', 'accounts' => \App\Models\Account::class,
                    default => null,
                };

                if (!$modelClass) {
                    continue;
                }

                $product = $modelClass::with('game')->find($productId);

                if (!$product) {
                    continue;
                }

                // Get price based on product type (handle both singular and plural)
                $price = match($productType) {
                    'currency', 'currencies' => floatval($product->price_per_unit ?? 0),
                    default => floatval($product->price ?? 0),
                };

                $discount = floatval($product->discount ?? 0);
                $itemTotal = $quantity * ($price - $discount);
                $subtotal += $itemTotal;

                $orderItems[] = [
                    'seller_id' => $product->user_id,
                    'product_type' => $productType,
                    'product_id' => $product->id,
                    'product_name' => $product->name ?? $product->title ?? 'Unknown',
                    'product_description' => $product->description ?? '',
                    'product_images' => $product->images ?? [],
                    'game_name' => $product->game ? $product->game->title : null,
                    'quantity' => $quantity,
                    'price' => $price,
                    'discount' => $discount,
                    'total' => $itemTotal,
                    'status' => 'pending',
                ];

                // Decrease stock if applicable
                if (isset($product->stock)) {
                    $product->decrement('stock', $quantity);
                }
            }

            if (empty($orderItems)) {
                return response()->json(['error' => 'No valid items in cart'], 400);
            }

            $tax = $subtotal * 0.0; // 0% tax for now
            $total = $subtotal + $tax;

            // Calculate platform fee (default 10%, can be reduced with seller subscriptions)
            // Note: For multi-seller orders, we'll use an average or per-item calculation
            $platformFeePercentage = 10.00; // Default
            $platformFee = $total * ($platformFeePercentage / 100);
            $sellerEarnings = $total - $platformFee;

            // Check wallet balance if paying with wallet
            if ($request->payment_method === 'wallet') {
                $wallet = $user->getOrCreateWallet();
                if ($wallet->balance < $total) {
                    return response()->json(['error' => 'Insufficient wallet balance'], 400);
                }
            }

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'platform_fee_percentage' => $platformFeePercentage,
                'platform_fee' => $platformFee,
                'seller_earnings' => $sellerEarnings,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'buyer_notes' => $request->buyer_notes,
            ]);

            // Create order items
            foreach ($orderItems as $item) {
                OrderItem::create(array_merge(['order_id' => $order->id], $item));
            }

            // Process payment based on method
            if ($request->payment_method === 'wallet') {
                $wallet = $user->getOrCreateWallet();
                $wallet->purchase($total, $order->id);
                $order->update(['payment_status' => 'completed']);

                // Clear cart after successful payment
                $cart->delete();
            } elseif ($request->payment_method === 'stripe') {
                // Create Stripe payment intent
                $stripeService = new StripePaymentService();
                $paymentIntent = $stripeService->createOrderPaymentIntent($user, $total, $order->id);

                $order->update([
                    'payment_status' => 'pending',
                    'stripe_payment_intent_id' => $paymentIntent->id
                ]);

                DB::commit();

                // Return payment intent client secret for frontend
                return response()->json([
                    'message' => 'Order created successfully',
                    'order' => $order->load('items'),
                    'requires_payment' => true,
                    'payment_intent_client_secret' => $paymentIntent->client_secret,
                ], 201);
            }

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->load('items'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }

    public function deliverItem(Request $request, $orderId, $itemId): JsonResponse
    {
        $request->validate([
            'delivery_details' => 'required|string|min:10',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::with('buyer')->findOrFail($orderId);
            $orderItem = OrderItem::where('id', $itemId)
                ->where('order_id', $orderId)
                ->firstOrFail();

            // Check if user is the seller for this item
            if ($orderItem->seller_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Check if item is already delivered
            if (in_array($orderItem->status, ['delivered', 'completed'])) {
                return response()->json(['error' => 'Item already delivered'], 400);
            }

            // Check if payment is completed
            if ($order->payment_status !== 'completed') {
                return response()->json(['error' => 'Cannot deliver until payment is completed'], 400);
            }

            // Update item with delivery details
            $orderItem->delivery_details = $request->delivery_details;
            $orderItem->status = 'delivered';
            $orderItem->delivered_at = now();
            $orderItem->save();

            // Release payment to seller
            $seller = $request->user();
            $feePercentage = $seller->getPlatformFeePercentage();
            $platformFee = $orderItem->total * ($feePercentage / 100);
            $sellerEarnings = $orderItem->total - $platformFee;

            // Credit seller wallet with earnings after platform fee
            $sellerWallet = $seller->getOrCreateWallet();
            $sellerWallet->receiveSale($sellerEarnings, $order->id);

            // Track sale for tier progression
            $seller->addSale($orderItem->total);

            // Check if all items are delivered and update order status
            $allDelivered = $order->items()->where('status', '!=', 'delivered')->count() === 0;
            if ($allDelivered) {
                $order->status = 'delivered';
                $order->save();
            }

            // Send email notification to buyer
            \Mail::to($order->buyer->email)->send(
                new \App\Mail\OrderDeliveredMail($order, $orderItem, $request->delivery_details)
            );

            DB::commit();

            return response()->json([
                'message' => 'Item delivered successfully and buyer has been notified via email',
                'order_item' => $orderItem,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to deliver item: ' . $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,processing,delivered,completed,cancelled',
            'seller_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::with('buyer')->findOrFail($id);
            $user = $request->user();

            // Check if user is seller for this order
            $isSeller = $order->items()->where('seller_id', $user->id)->exists();

            if (!$isSeller && $order->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $oldStatus = $order->status;
            $order->status = $request->status;

            \Log::info('🔍 ORDER STATUS UPDATE - CHECKING CANCELLATION CONDITIONS', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'requested_status' => $request->status,
                'current_payment_status' => $order->payment_status,
                'will_process_refund' => ($request->status === 'cancelled' && $order->payment_status === 'completed')
            ]);

            // Handle order cancellation with refund
            if ($request->status === 'cancelled' && $order->payment_status === 'completed') {
                \Log::info('🔄 ENTERING CANCELLATION REFUND BLOCK', [
                    'order_id' => $order->id,
                    'payment_method' => $order->payment_method,
                    'total' => $order->total
                ]);
                // Process refund based on payment method
                if ($order->payment_method === 'wallet') {
                    // Refund to wallet
                    $buyerWallet = $order->buyer->getOrCreateWallet();
                    $buyerWallet->refund($order->total, $order->id);

                    \Log::info('💰 WALLET REFUND PROCESSED', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'buyer_email' => $order->buyer->email,
                        'amount' => $order->total,
                        'payment_method' => 'wallet'
                    ]);

                } elseif ($order->payment_method === 'stripe' && $order->stripe_payment_intent_id) {
                    // Process Stripe refund
                    try {
                        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                        $refund = $stripe->refunds->create([
                            'payment_intent' => $order->stripe_payment_intent_id,
                            'reason' => 'requested_by_customer',
                        ]);

                        \Log::info('💳 STRIPE REFUND PROCESSED', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'buyer_email' => $order->buyer->email,
                            'amount' => $order->total,
                            'payment_method' => 'stripe',
                            'refund_id' => $refund->id,
                            'refund_status' => $refund->status
                        ]);

                    } catch (\Exception $stripeError) {
                        \Log::error('❌ STRIPE REFUND FAILED', [
                            'order_id' => $order->id,
                            'error' => $stripeError->getMessage()
                        ]);
                        DB::rollBack();
                        return response()->json([
                            'error' => 'Failed to process Stripe refund: ' . $stripeError->getMessage()
                        ], 500);
                    }
                }

                // Update payment status to refunded
                $order->payment_status = 'refunded';

                \Log::info('✅ PAYMENT STATUS UPDATED TO REFUNDED', [
                    'order_id' => $order->id,
                    'new_payment_status' => $order->payment_status
                ]);

                // Restore stock for all items
                foreach ($order->items as $item) {
                    // Get the product and restore stock
                    $modelClass = match($item->product_type) {
                        'currency', 'currencies' => \App\Models\Currency::class,
                        'item', 'items' => \App\Models\Item::class,
                        'service', 'services' => \App\Models\Service::class,
                        'account', 'accounts' => \App\Models\Account::class,
                        default => null,
                    };

                    if ($modelClass) {
                        $product = $modelClass::find($item->product_id);
                        if ($product && isset($product->stock)) {
                            $product->increment('stock', $item->quantity);
                        }
                    }

                    // Update item status
                    $item->update(['status' => 'cancelled']);

                    \Log::info('📦 ITEM STATUS UPDATED', [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'product_name' => $item->product_name,
                        'new_status' => 'cancelled'
                    ]);
                }

                \Log::info('✅ CANCELLATION REFUND BLOCK COMPLETED', [
                    'order_id' => $order->id,
                    'items_updated' => $order->items->count()
                ]);
            }

            // If order is marked as delivered/completed, release payment to seller
            if (in_array($request->status, ['delivered', 'completed']) && $order->payment_status === 'completed') {
                foreach ($order->items as $item) {
                    if ($item->seller_id && $item->status === 'pending') {
                        // Get seller and their platform fee percentage
                        $seller = \App\Models\User::find($item->seller_id);
                        if ($seller) {
                            $feePercentage = $seller->getPlatformFeePercentage();
                            $platformFee = $item->total * ($feePercentage / 100);
                            $sellerEarnings = $item->total - $platformFee;

                            // Credit seller wallet with earnings after platform fee
                            $sellerWallet = $seller->getOrCreateWallet();
                            $sellerWallet->receiveSale($sellerEarnings, $order->id);

                            // Track sale for tier progression
                            $seller->addSale($item->total);
                        }

                        // Update item status
                        $item->update(['status' => $request->status]);
                    }
                }
            }

            if ($request->has('seller_notes')) {
                $order->seller_notes = $request->seller_notes;
            }

            $order->save();

            \Log::info('💾 ORDER SAVED TO DATABASE', [
                'order_id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'seller_notes' => $order->seller_notes ? 'present' : 'null'
            ]);

            // Send email notification to buyer about status change (only if status actually changed)
            if ($oldStatus !== $request->status) {
                \Mail::to($order->buyer->email)->send(
                    new \App\Mail\OrderStatusUpdatedMail($order->load('items'), $oldStatus, $request->status)
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Order status updated successfully and buyer has been notified',
                'order' => $order->load('items'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update order: ' . $e->getMessage()], 500);
        }
    }
}
