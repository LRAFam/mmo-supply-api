<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Services\StripePaymentService;
use App\Services\NotificationService;
use App\Services\AchievementCheckService;
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

        // If accessing via seller view (indicated by view=seller query param), enforce seller-only access
        if ($request->query('view') === 'seller' && !$isSeller) {
            return response()->json(['error' => 'You are not a seller on this order'], 403);
        }

        // If accessing via buyer view (indicated by view=buyer query param), enforce buyer-only access
        if ($request->query('view') === 'buyer' && !$isBuyer) {
            return response()->json(['error' => 'You are not the buyer of this order'], 403);
        }

        // General authorization: must be either buyer or seller
        if (!$isBuyer && !$isSeller) {
            return response()->json(['error' => 'Unauthorized access to this order'], 403);
        }

        // Load relationships including conversation for the seller
        // For orders with multiple sellers, we'll find the conversation between buyer and current user (seller)
        $order->load(['items.seller', 'items.reviews', 'buyer']);

        // Find the conversation for this order between the current user and the buyer
        // First try to find conversation with this specific order_id
        $conversation = \App\Models\Conversation::where('order_id', $order->id)
            ->where(function ($query) use ($userId) {
                $query->where('user_one_id', $userId)
                      ->orWhere('user_two_id', $userId);
            })
            ->first();

        // If not found and this is a seller viewing, try to find conversation between buyer and current seller
        // This handles legacy conversations created before order_id was properly set
        if (!$conversation && $isSeller) {
            $buyerId = $order->user_id;
            $conversation = \App\Models\Conversation::where(function ($query) use ($userId, $buyerId) {
                $query->where(function ($q) use ($userId, $buyerId) {
                    $q->where('user_one_id', $userId)->where('user_two_id', $buyerId);
                })->orWhere(function ($q) use ($userId, $buyerId) {
                    $q->where('user_one_id', $buyerId)->where('user_two_id', $userId);
                });
            })
            ->whereNull('order_id') // Only match conversations without order_id (legacy)
            ->first();

            // If we found a legacy conversation, update it with the order_id
            if ($conversation) {
                $conversation->update(['order_id' => $order->id]);
            }
        }

        // Add conversation_id to the order response
        $orderData = $order->toArray();
        $orderData['conversation_id'] = $conversation ? $conversation->id : null;

        return response()->json($orderData);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required|string|in:wallet,stripe,paypal', // Sellers choose which methods they accept
            'buyer_notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();

            // SECURITY: Require email verification before purchases
            if (!$user->email_verified_at) {
                return response()->json([
                    'error' => 'Email verification required',
                    'message' => 'Please verify your email address before making purchases',
                    'requires_verification' => true
                ], 403);
            }

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

                // Get the model class using ProductType enum
                $productTypeEnum = ProductType::tryFromString($productType);
                if (!$productTypeEnum) {
                    continue;
                }
                $modelClass = $productTypeEnum->getModelClass();

                // Normalize product_type to singular form
                $normalizedProductType = $productTypeEnum->value;

                $product = $modelClass::with('game')->find($productId);

                if (!$product) {
                    continue;
                }

                // Use stored unit_price from cart (calculated when added to cart)
                // This prevents price manipulation and ensures consistent pricing
                $unitPrice = isset($cartItem['unit_price']) ? floatval($cartItem['unit_price']) : 0;

                // Fallback: calculate price if not stored (legacy carts)
                if ($unitPrice === 0) {
                    $priceField = $productTypeEnum->getPriceField();
                    $price = floatval($product->{$priceField} ?? 0);
                    $discount = floatval($product->discount ?? 0);
                    $unitPrice = $price - $discount;

                    // For OSRS currency, recalculate based on metadata
                    if ($productType === 'currency' && isset($product->price_per_million) && $product->price_per_million > 0 && isset($cartItem['metadata']['gold_amount'])) {
                        $goldInMillions = floatval($cartItem['metadata']['gold_amount']) / 1000000;
                        $unitPrice = floatval($product->price_per_million) * $goldInMillions;
                    }
                    // For package-based services
                    elseif ($productType === 'service' && isset($cartItem['metadata']['package_price'])) {
                        $unitPrice = floatval($cartItem['metadata']['package_price']);
                    }
                }

                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                $orderItem = [
                    'seller_id' => $product->user_id,
                    'product_type' => $normalizedProductType,
                    'product_id' => $product->id,
                    'product_name' => $product->name ?? $product->title ?? 'Unknown',
                    'product_description' => $product->description ?? '',
                    'product_images' => $product->images ?? [],
                    'game_name' => $product->game ? $product->game->title : null,
                    'quantity' => $quantity,
                    'price' => $unitPrice, // Store unit price (already calculated including OSRS/package pricing)
                    'discount' => 0, // Discount already factored into unit price
                    'total' => $itemTotal,
                    'status' => 'pending',
                ];

                // Include metadata if present (e.g., selected service package)
                if (isset($cartItem['metadata'])) {
                    $orderItem['metadata'] = $cartItem['metadata'];
                }

                $orderItems[] = $orderItem;

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

            // Create order items and notify sellers
            $notificationService = app(NotificationService::class);
            foreach ($orderItems as $item) {
                $orderItem = OrderItem::create(array_merge(['order_id' => $order->id], $item));

                // Notify seller of new order
                $notificationService->newOrderForSeller(
                    sellerId: $item['seller_id'],
                    orderId: $order->id,
                    productTitle: $item['product_name'],
                    amount: $item['total']
                );
            }

            // Send initial order message to each seller
            $frontendUrl = config('app.frontend_url');
            foreach ($orderItems as $item) {
                $sellerOrderUrl = "{$frontendUrl}/seller/orders/{$order->id}";

                // Get product type for URL (pluralize for route)
                $productTypePlural = $item['product_type'] . 's'; // service -> services, account -> accounts, etc.
                $productUrl = "{$frontendUrl}/{$productTypePlural}/{$item['product_id']}";

                $orderDetails = "📦 **New Order #{$order->order_number}**\n\n";
                $orderDetails .= "**Product:** [{$item['product_name']}]({$productUrl})\n";
                $orderDetails .= "**Quantity:** {$item['quantity']}\n";
                $orderDetails .= "**Total:** $" . number_format($item['total'], 2) . "\n\n";

                if ($request->buyer_notes) {
                    $orderDetails .= "**Buyer Notes:**\n{$request->buyer_notes}\n\n";
                }

                $orderDetails .= "➡️ [View Order Details]({$sellerOrderUrl})\n\n";
                $orderDetails .= "Please review the order details and start processing this order.";

                MessageController::sendOrderSystemMessage(
                    $user->id,
                    $item['seller_id'],
                    $order->id,
                    $orderDetails,
                    'order_created'
                );
            }

            // Process payment based on method
            if ($request->payment_method === 'wallet') {
                $wallet = $user->getOrCreateWallet();
                $wallet->purchase($total, $order->id);
                $order->update(['payment_status' => 'completed']);

                // Clear cart after successful payment
                $cart->delete();

                // Check for buyer achievements after successful purchase
                $achievementService = app(AchievementCheckService::class);
                $achievementService->checkAndAutoClaimAchievements($user);
            } elseif ($request->payment_method === 'stripe') {
                // Create Stripe payment intent with Connect escrow
                // Funds go to Stripe escrow, not our platform account
                $stripeService = new StripePaymentService();

                try {
                    // Load order items with seller relationship for escrow setup
                    $order->load('items.seller');

                    $paymentIntent = $stripeService->createOrderPaymentIntent($user, $order);

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
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'Failed to create payment: ' . $e->getMessage(),
                        'hint' => 'Seller may need to complete Stripe account setup'
                    ], 400);
                }
            } elseif ($request->payment_method === 'paypal') {
                // PayPal Commerce Platform implementation coming soon
                // For now, guide users to use alternative payment methods
                DB::rollBack();
                return response()->json([
                    'error' => 'PayPal payments are being implemented. Please use Stripe or Wallet payment for now.',
                    'available_methods' => ['wallet', 'stripe'],
                ], 400);
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

            // Determine if seller is trusted for instant fund release
            $seller = $request->user();
            $isTrustedSeller = $this->isSellerTrusted($seller);

            if ($isTrustedSeller) {
                // Trusted sellers get instant fund release
                $this->releaseFundsToSeller($seller, $orderItem, $order->id);
            } else {
                // Untrusted sellers: require buyer confirmation or auto-release after 72 hours
                $orderItem->auto_release_at = now()->addHours(72);
            }

            $orderItem->save();

            // Check if all items are delivered and update order status
            $allDelivered = $order->items()->where('status', '!=', 'delivered')->count() === 0;
            if ($allDelivered) {
                $order->status = 'delivered';
                $order->save();

                // Process referral commission if buyer was referred
                $buyer = $order->buyer;
                if ($buyer->referred_by) {
                    $referral = \App\Models\Referral::where('referrer_id', $buyer->referred_by)
                        ->where('referred_id', $buyer->id)
                        ->first();

                    if ($referral) {
                        $referral->recordPurchase($order);
                    }
                }
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

    public function confirmDelivery(Request $request, $orderId, $itemId): JsonResponse
    {
        $request->validate([
            'confirmed' => 'required|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::findOrFail($orderId);
            $orderItem = OrderItem::where('id', $itemId)
                ->where('order_id', $orderId)
                ->firstOrFail();

            // Check if user is the buyer
            if ($order->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Check if item is delivered
            if ($orderItem->status !== 'delivered') {
                return response()->json(['error' => 'Item not yet delivered'], 400);
            }

            // Check if already confirmed
            if ($orderItem->buyer_confirmed) {
                return response()->json(['error' => 'Item already confirmed'], 400);
            }

            // Update confirmation status
            $orderItem->buyer_confirmed = $request->confirmed;
            $orderItem->buyer_confirmed_at = now();
            $orderItem->buyer_confirmation_notes = $request->notes;

            // If confirmed, release funds to seller
            if ($request->confirmed && !$orderItem->funds_released) {
                $seller = $orderItem->seller;
                $this->releaseFundsToSeller($seller, $orderItem, $order->id);
            }

            $orderItem->save();

            DB::commit();

            return response()->json([
                'message' => 'Delivery confirmation recorded successfully',
                'order_item' => $orderItem,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to confirm delivery: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check if seller is trusted for instant fund release
     */
    private function isSellerTrusted($seller): bool
    {
        // Trusted criteria:
        // 1. Premium or verified seller tier
        // 2. OR has completed 50+ orders
        // 3. OR lifetime sales > $5000
        // 4. OR partner/elite subscription tier

        $trustedTiers = ['premium', 'verified', 'partner', 'elite'];

        if (in_array($seller->seller_tier, $trustedTiers)) {
            return true;
        }

        if ($seller->subscription_tier && in_array($seller->subscription_tier, ['premium', 'elite'])) {
            return true;
        }

        // Check completed orders count
        $completedOrders = \DB::table('order_items')
            ->where('seller_id', $seller->id)
            ->whereIn('status', ['completed', 'delivered'])
            ->count();

        if ($completedOrders >= 50) {
            return true;
        }

        // Check lifetime sales
        if ($seller->total_sales >= 5000) {
            return true;
        }

        return false;
    }

    /**
     * Release funds to seller
     */
    private function releaseFundsToSeller($seller, $orderItem, $orderId): void
    {
        // Prevent double release
        if ($orderItem->funds_released) {
            return;
        }

        // Get the order to check payment method
        $order = Order::findOrFail($orderId);

        // If payment was made via Stripe, release escrow via Stripe Connect
        if ($order->payment_method === 'stripe' && $order->stripe_payment_intent_id) {
            try {
                $stripeService = new StripePaymentService();
                $stripeService->releaseEscrowToSeller($order);

                // Stripe automatically transfers funds to seller's connected account
                // Platform fee was already collected via application_fee_amount
                // No need to manually credit wallets - Stripe handles it
            } catch (\Exception $e) {
                // Log error but don't fail the entire process
                \Log::error('Failed to release Stripe escrow', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
        // For wallet payments, use the old flow (credit seller wallet)
        elseif ($order->payment_method === 'wallet') {
            // Get effective platform fee with perks applied
            $baseFeePercentage = $seller->getPlatformFeePercentage();
            $feePercentage = $seller->getEffectivePlatformFee($baseFeePercentage);
            $platformFee = $orderItem->total * ($feePercentage / 100);
            $sellerEarnings = $orderItem->total - $platformFee;

            // Credit seller wallet with earnings after platform fee
            $sellerWallet = $seller->getOrCreateWallet();
            $sellerWallet->receiveSale($sellerEarnings, $orderId);
        }

        // Track sale for tier progression (for both payment methods)
        $seller->addSale($orderItem->total);

        // Update provider stats for this game
        $gameId = null;
        if ($orderItem->product_type && $orderItem->product_id) {
            $productTypeEnum = ProductType::tryFromString($orderItem->product_type);
            if ($productTypeEnum) {
                $modelClass = $productTypeEnum->getModelClass();
                $product = $modelClass::find($orderItem->product_id);
                $gameId = $product->game_id ?? null;
            }
        }
        $this->updateProviderStats($seller->id, $gameId, $orderItem);

        // Check for seller achievements after funds released
        $achievementService = app(AchievementCheckService::class);
        $achievementService->checkAndAutoClaimAchievements($seller);

        // Mark funds as released
        $orderItem->funds_released = true;
        $orderItem->funds_released_at = now();
        $orderItem->save();
    }

    /**
     * Update provider stats (total_sales, rating calculation)
     */
    private function updateProviderStats(int $sellerId, ?int $gameId, $orderItem): void
    {
        if (!$gameId) {
            return;
        }

        // Find or create provider record for this seller-game combination
        $provider = \App\Models\Provider::firstOrCreate(
            [
                'user_id' => $sellerId,
                'game_id' => $gameId,
            ],
            [
                'vouches' => 0,
                'rating' => 0,
                'total_sales' => 0,
                'is_verified' => false,
            ]
        );

        // Increment total_sales
        $provider->increment('total_sales');

        // Recalculate rating from reviews
        $averageRating = \App\Models\Review::whereHas('orderItem', function ($query) use ($sellerId, $gameId) {
            $query->where('seller_id', $sellerId)
                  ->whereHas('product', function ($q) use ($gameId) {
                      $q->where('game_id', $gameId);
                  });
        })->avg('rating');

        if ($averageRating !== null) {
            $provider->update(['rating' => round($averageRating, 2)]);
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
                    $productTypeEnum = ProductType::tryFromString($item->product_type);
                    if ($productTypeEnum) {
                        $modelClass = $productTypeEnum->getModelClass();
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

                            // Get game_id from product
                            $gameId = null;
                            if ($item->product_type && $item->product_id) {
                                $productTypeEnum = ProductType::tryFromString($item->product_type);
                                if ($productTypeEnum) {
                                    $modelClass = $productTypeEnum->getModelClass();
                                    $product = $modelClass::find($item->product_id);
                                    $gameId = $product->game_id ?? null;
                                }
                            }

                            // Update provider stats for this game
                            $this->updateProviderStats($seller->id, $gameId, $item);

                            // Check for seller achievements after successful sale
                            $achievementService = app(AchievementCheckService::class);
                            $achievementService->checkAndAutoClaimAchievements($seller);
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

                // Send notification to buyer via AI Agent
                $notificationService = app(NotificationService::class);
                $firstItem = $order->items->first();
                $orderTitle = $firstItem ? $firstItem->product_name : "Order #{$order->id}";

                $notificationService->orderStatusUpdated(
                    userId: $order->user_id,
                    orderId: $order->id,
                    status: $request->status,
                    orderTitle: $orderTitle
                );

                // Send status update message to conversation with each seller
                $frontendUrl = config('app.frontend_url');
                foreach ($order->items as $item) {
                    $statusEmoji = match($request->status) {
                        'pending' => '⏳',
                        'processing' => '⚙️',
                        'delivered' => '📦',
                        'completed' => '✅',
                        'cancelled' => '❌',
                        default => '📋'
                    };

                    // Determine if the user updating is the seller or buyer
                    $isSeller = $item->seller_id === $user->id;
                    $orderUrl = $isSeller
                        ? "{$frontendUrl}/seller/orders/{$order->id}"
                        : "{$frontendUrl}/orders/{$order->id}";

                    // Get product URL
                    $productTypePlural = $item->product_type . 's';
                    $productUrl = "{$frontendUrl}/{$productTypePlural}/{$item->product_id}";

                    $statusMessage = "{$statusEmoji} **Order Status Updated**\n\n";
                    $statusMessage .= "**Order:** #{$order->order_number}\n";
                    $statusMessage .= "**Product:** [{$item->product_name}]({$productUrl})\n";
                    $statusMessage .= "**New Status:** " . ucfirst($request->status) . "\n";

                    if ($request->seller_notes) {
                        $statusMessage .= "\n**Seller Notes:**\n{$request->seller_notes}\n";
                    }

                    $statusMessage .= "\n➡️ [View Order Details]({$orderUrl})";

                    MessageController::sendOrderSystemMessage(
                        $order->user_id,
                        $item->seller_id,
                        $order->id,
                        $statusMessage,
                        'order_status_updated'
                    );
                }
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
