<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Transfer;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for wallet deposit
     */
    public function createDepositIntent(User $user, float $amount, string $currency = 'usd')
    {
        $amountInCents = (int) ($amount * 100);

        $paymentIntent = PaymentIntent::create([
            'amount' => $amountInCents,
            'currency' => strtolower($currency),
            'customer' => $this->getOrCreateCustomer($user),
            'metadata' => [
                'user_id' => $user->id,
                'type' => 'wallet_deposit',
            ],
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);

        return $paymentIntent;
    }

    /**
     * Create a payment intent for direct order payment with Stripe Connect escrow
     * Uses Destination Charges pattern - Stripe holds funds until release
     *
     * IMPORTANT: Currently supports single-seller orders only.
     * For multi-seller orders, see documentation in Obsidian vault:
     * /Projects/MMO Supply/03-Platform-Audit/Current-Payment-Flow-Analysis.md
     *
     * Two options for multi-seller:
     * 1. Split Payment Intents (create one PaymentIntent per seller) - RECOMMENDED
     * 2. Transfer Pattern (charge once, manual Transfers to each seller)
     */
    public function createOrderPaymentIntent(User $buyer, $order, string $currency = 'usd')
    {
        $totalInCents = (int) ($order->total * 100);
        $platformFeeInCents = (int) (($order->platform_fee ?? 0) * 100);

        // Check for multi-seller orders
        $uniqueSellers = $order->items->pluck('seller_id')->unique();
        if ($uniqueSellers->count() > 1) {
            throw new \Exception('Multi-seller orders are not yet supported with Stripe Connect escrow. Please separate items from different sellers into individual orders.');
        }

        // Get the seller from order items
        $firstItem = $order->items->first();

        if (!$firstItem) {
            throw new \Exception('Order has no items');
        }

        $seller = $firstItem->seller;

        if (!$seller || !$seller->stripe_account_id) {
            throw new \Exception('Seller must connect a Stripe account before receiving payments. Please ensure the seller has completed Stripe onboarding.');
        }

        // Create PaymentIntent with Stripe Connect Destination Charges
        // This means: Stripe holds the funds in escrow, not our platform
        $paymentIntent = PaymentIntent::create([
            'amount' => $totalInCents,
            'currency' => strtolower($currency),
            'customer' => $this->getOrCreateCustomer($buyer),

            // Stripe Connect escrow configuration
            'on_behalf_of' => $seller->stripe_account_id,
            'transfer_data' => [
                'destination' => $seller->stripe_account_id,
            ],
            'application_fee_amount' => $platformFeeInCents,

            'metadata' => [
                'buyer_id' => $buyer->id,
                'order_id' => $order->id,
                'seller_id' => $seller->id,
                'type' => 'order_payment',
            ],
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);

        return $paymentIntent;
    }

    /**
     * Create a payment intent for multi-seller orders using Transfer pattern
     * Creates one PaymentIntent for the total amount, then creates Transfers to each seller
     *
     * This is the "Hybrid" approach recommended in the architecture docs
     */
    public function createMultiSellerPaymentIntent(User $buyer, array $orders, float $totalAmount, string $currency = 'usd')
    {
        $totalInCents = (int) ($totalAmount * 100);

        // Validate all sellers have Stripe accounts
        foreach ($orders as $order) {
            $sellerId = $order->seller_id;
            $seller = User::find($sellerId);

            if (!$seller || !$seller->stripe_account_id) {
                throw new \Exception("Seller {$sellerId} must connect a Stripe account before receiving payments. Please ensure all sellers have completed Stripe onboarding.");
            }
        }

        // Create PaymentIntent for total amount (charged to buyer)
        // No destination specified - funds go to platform, then we Transfer to sellers
        $paymentIntent = PaymentIntent::create([
            'amount' => $totalInCents,
            'currency' => strtolower($currency),
            'customer' => $this->getOrCreateCustomer($buyer),

            'metadata' => [
                'buyer_id' => $buyer->id,
                'type' => 'multi_seller_order_payment',
                'order_group_id' => $orders[0]->order_group_id ?? null,
                'seller_count' => count($orders),
            ],
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);

        return $paymentIntent;
    }

    /**
     * Create Transfers to sellers after payment is confirmed
     * Called after PaymentIntent succeeds (webhook or frontend confirmation)
     */
    public function createTransfersToSellers(string $paymentIntentId): array
    {
        // Get all orders associated with this PaymentIntent
        $orders = \App\Models\Order::where('stripe_payment_intent_id', $paymentIntentId)->get();

        if ($orders->isEmpty()) {
            throw new \Exception("No orders found for PaymentIntent {$paymentIntentId}");
        }

        $transfers = [];

        foreach ($orders as $order) {
            $seller = User::find($order->seller_id);

            if (!$seller || !$seller->stripe_account_id) {
                // Log error but continue with other transfers
                \Log::error("Cannot create transfer for order {$order->id}: Seller {$order->seller_id} missing Stripe account");
                continue;
            }

            $sellerPayoutInCents = (int) ($order->seller_payout * 100);

            // Create Transfer to seller's connected account
            try {
                $transfer = Transfer::create([
                    'amount' => $sellerPayoutInCents,
                    'currency' => 'usd',
                    'destination' => $seller->stripe_account_id,
                    'transfer_group' => $order->order_group_id,
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'seller_id' => $seller->id,
                        'platform_fee' => $order->platform_fee,
                    ],
                ]);

                // Update order with transfer ID
                $order->update([
                    'stripe_transfer_id' => $transfer->id,
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);

                $transfers[] = $transfer;
            } catch (\Exception $e) {
                \Log::error("Failed to create transfer for order {$order->id}: " . $e->getMessage());
                throw $e;
            }
        }

        return $transfers;
    }

    /**
     * Get or create Stripe customer for user
     */
    public function getOrCreateCustomer(User $user)
    {
        // Use Cashier's method to ensure consistency
        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer([
                'name' => $user->name,
                'email' => $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);
        }

        return $user->stripe_id;
    }

    /**
     * Create Stripe Connect account for seller
     */
    public function createConnectAccount(User $user, array $accountData = [])
    {
        if ($user->stripe_account_id) {
            return $user->stripe_account_id;
        }

        $account = Account::create([
            'type' => 'express',
            'country' => $accountData['country'] ?? 'US',
            'email' => $user->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'business_type' => 'individual',
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update(['stripe_account_id' => $account->id]);

        return $account->id;
    }

    /**
     * Create account link for Stripe Connect onboarding
     */
    public function createAccountLink(User $user, string $returnUrl, string $refreshUrl)
    {
        $accountId = $user->stripe_account_id;

        if (!$accountId) {
            throw new \Exception('User does not have a Stripe Connect account');
        }

        $accountLink = AccountLink::create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);

        return $accountLink->url;
    }

    /**
     * Transfer funds to seller's connect account
     */
    public function transferToSeller(User $seller, float $amount, string $description = null)
    {
        if (!$seller->stripe_account_id) {
            throw new \Exception('Seller does not have a Stripe Connect account');
        }

        $amountInCents = (int) ($amount * 100);

        $transfer = Transfer::create([
            'amount' => $amountInCents,
            'currency' => 'usd',
            'destination' => $seller->stripe_account_id,
            'description' => $description,
            'metadata' => [
                'seller_id' => $seller->id,
            ],
        ]);

        return $transfer;
    }

    /**
     * Check if payment intent was successful
     */
    public function verifyPaymentIntent(string $paymentIntentId)
    {
        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

        return $paymentIntent->status === 'succeeded';
    }

    /**
     * Get Stripe Connect account balance
     */
    public function getAccountBalance(string $stripeAccountId)
    {
        try {
            $balance = \Stripe\Balance::retrieve([], ['stripe_account' => $stripeAccountId]);
            return $balance;
        } catch (\Exception $e) {
            throw new \Exception('Failed to retrieve account balance: ' . $e->getMessage());
        }
    }

    /**
     * Create payout to seller's bank account
     */
    public function createPayout(string $stripeAccountId, float $amount, string $currency = 'usd')
    {
        $amountInCents = (int) ($amount * 100);

        try {
            $payout = \Stripe\Payout::create(
                [
                    'amount' => $amountInCents,
                    'currency' => strtolower($currency),
                ],
                ['stripe_account' => $stripeAccountId]
            );

            return $payout;
        } catch (\Exception $e) {
            throw new \Exception('Failed to create payout: ' . $e->getMessage());
        }
    }

    /**
     * Get payment intent details
     */
    public function getPaymentIntent(string $paymentIntentId)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            return $paymentIntent;
        } catch (\Exception $e) {
            throw new \Exception('Failed to retrieve payment intent: ' . $e->getMessage());
        }
    }

    /**
     * Release escrow to seller (capture the payment intent)
     * For Stripe Connect Destination Charges, capturing the payment releases funds to seller
     */
    public function releaseEscrowToSeller($order)
    {
        $paymentIntentId = $order->stripe_payment_intent_id;

        if (!$paymentIntentId) {
            throw new \Exception('No payment intent found for this order');
        }

        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            // If payment intent requires capture, capture it to release funds
            if ($paymentIntent->status === 'requires_capture') {
                $paymentIntent->capture();
            }

            // If already succeeded, funds are automatically transferred via transfer_data
            // No additional action needed - Stripe handles the transfer automatically

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to release escrow: ' . $e->getMessage());
        }
    }
}
