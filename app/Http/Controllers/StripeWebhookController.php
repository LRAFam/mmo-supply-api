<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Order;
use App\Models\Cart;
use App\Models\FeaturedListing;
use App\Services\AchievementCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /**
     * Handle Stripe webhook events
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook invalid payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', ['type' => $event->type]);

        // Cashier handles all subscription events automatically
        $cashierEvents = [
            'checkout.session.completed',
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'customer.updated',
            'customer.deleted',
            'invoice.payment_action_required',
            'invoice.payment_succeeded',
            'invoice.payment_failed',
        ];

        if (in_array($event->type, $cashierEvents)) {
            Log::info('Delegating to Cashier webhook handler', ['type' => $event->type]);
            $cashierController = new \Laravel\Cashier\Http\Controllers\WebhookController();
            return $cashierController->handleWebhook($request);
        }

        // Handle custom payment events
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            case 'account.updated':
                $this->handleAccountUpdated($event->data->object);
                break;

            case 'transfer.created':
                $this->handleTransferCreated($event->data->object);
                break;

            case 'payout.paid':
                $this->handlePayoutPaid($event->data->object);
                break;

            case 'payout.failed':
                $this->handlePayoutFailed($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event type: ' . $event->type);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle successful payment intent
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        $userId = $paymentIntent->metadata->user_id ?? null;
        $type = $paymentIntent->metadata->type ?? null;

        if (!$userId) {
            Log::warning('Payment intent missing user_id', [
                'payment_intent_id' => $paymentIntent->id
            ]);
            return;
        }

        // Handle wallet deposit
        if ($type === 'wallet_deposit') {
            $this->handleWalletDeposit($paymentIntent, $userId);
            return;
        }

        // Handle order payment
        if ($type === 'order_payment') {
            $this->handleOrderPayment($paymentIntent, $userId);
            return;
        }

        // Handle featured listing payment
        if ($type === 'featured_listing') {
            $this->handleFeaturedListingPayment($paymentIntent, $userId);
            return;
        }

        Log::warning('Unknown payment intent type', [
            'payment_intent_id' => $paymentIntent->id,
            'type' => $type
        ]);
    }

    protected function handleWalletDeposit($paymentIntent, $userId)
    {
        DB::beginTransaction();
        try {
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception("User not found: {$userId}");
            }

            $wallet = $user->getOrCreateWallet();
            $amount = $paymentIntent->amount / 100; // Convert from cents

            // Check if transaction already exists
            $existingTransaction = Transaction::where('reference', $paymentIntent->id)->first();
            if ($existingTransaction) {
                Log::info('Transaction already processed', ['payment_intent_id' => $paymentIntent->id]);
                DB::rollBack();
                return;
            }

            // Create completed transaction
            $transaction = $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'currency' => strtoupper($paymentIntent->currency),
                'status' => 'completed',
                'description' => 'Wallet deposit via Stripe',
                'reference' => $paymentIntent->id,
                'payment_method' => 'stripe',
                'metadata' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'stripe_customer' => $paymentIntent->customer,
                ],
            ]);

            // Add funds to wallet
            $wallet->increment('balance', $amount);

            Log::info('Deposit processed successfully', [
                'user_id' => $userId,
                'amount' => $amount,
                'transaction_id' => $transaction->id
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process deposit: ' . $e->getMessage(), [
                'payment_intent_id' => $paymentIntent->id,
                'user_id' => $userId
            ]);
        }
    }

    protected function handleOrderPayment($paymentIntent, $userId)
    {
        DB::beginTransaction();
        try {
            $orderId = $paymentIntent->metadata->order_id ?? null;
            if (!$orderId) {
                throw new \Exception("Order ID missing from payment intent");
            }

            $order = Order::find($orderId);
            if (!$order) {
                throw new \Exception("Order not found: {$orderId}");
            }

            // Check if already processed
            if ($order->payment_status === 'completed') {
                Log::info('Order payment already processed', ['order_id' => $orderId]);
                DB::rollBack();
                return;
            }

            // Update order payment status
            $order->update(['payment_status' => 'completed']);

            // Clear user's cart since payment is successful
            Cart::where('user_id', $userId)->delete();

            // Create transaction record
            $user = User::find($userId);
            $wallet = $user->getOrCreateWallet();
            $amount = $paymentIntent->amount / 100;

            $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => 'payment',
                'amount' => -$amount, // Negative because it's a payment out
                'currency' => strtoupper($paymentIntent->currency),
                'status' => 'completed',
                'description' => "Payment for Order #{$order->id}",
                'reference' => $paymentIntent->id,
                'payment_method' => 'stripe',
                'metadata' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'order_id' => $order->id,
                ],
            ]);

            // Check for buyer achievements after successful Stripe payment
            $achievementService = app(AchievementCheckService::class);
            $achievementService->checkAndAutoClaimAchievements($user);

            Log::info('Order payment processed successfully', [
                'order_id' => $orderId,
                'user_id' => $userId,
                'amount' => $amount
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process order payment: ' . $e->getMessage(), [
                'payment_intent_id' => $paymentIntent->id,
                'user_id' => $userId
            ]);
        }
    }

    /**
     * Handle failed payment intent
     */
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        $userId = $paymentIntent->metadata->user_id ?? null;

        if (!$userId) {
            return;
        }

        DB::beginTransaction();
        try {
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception("User not found: {$userId}");
            }

            $wallet = $user->getOrCreateWallet();
            $amount = $paymentIntent->amount / 100;

            // Create failed transaction
            $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'currency' => strtoupper($paymentIntent->currency),
                'status' => 'failed',
                'description' => 'Failed wallet deposit via Stripe',
                'reference' => $paymentIntent->id,
                'payment_method' => 'stripe',
                'metadata' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'error' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
                ],
            ]);

            Log::warning('Payment intent failed', [
                'payment_intent_id' => $paymentIntent->id,
                'user_id' => $userId,
                'error' => $paymentIntent->last_payment_error->message ?? 'Unknown'
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to log payment failure: ' . $e->getMessage());
        }
    }

    /**
     * Handle Stripe Connect account updates
     */
    protected function handleAccountUpdated($account)
    {
        $user = User::where('stripe_account_id', $account->id)->first();

        if (!$user) {
            Log::warning('No user found for Stripe account', ['account_id' => $account->id]);
            return;
        }

        // Check if onboarding is complete
        $onboardingComplete = $account->details_submitted && $account->charges_enabled;

        $user->update([
            'stripe_onboarding_complete' => $onboardingComplete
        ]);

        Log::info('Stripe Connect account updated', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'onboarding_complete' => $onboardingComplete
        ]);
    }

    /**
     * Handle transfer created (funds sent to seller)
     */
    protected function handleTransferCreated($transfer)
    {
        Log::info('Transfer created', [
            'transfer_id' => $transfer->id,
            'amount' => $transfer->amount / 100,
            'destination' => $transfer->destination
        ]);

        // Additional logic for tracking transfers can be added here
    }

    /**
     * Handle payout paid to seller's bank
     */
    protected function handlePayoutPaid($payout)
    {
        Log::info('Payout successful', [
            'payout_id' => $payout->id,
            'amount' => $payout->amount / 100,
            'arrival_date' => $payout->arrival_date
        ]);

        // Update withdrawal request status if applicable
        $withdrawalRequest = \App\Models\WithdrawalRequest::where('reference', $payout->id)->first();
        if ($withdrawalRequest) {
            $withdrawalRequest->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }
    }

    /**
     * Handle payout failure
     */
    protected function handlePayoutFailed($payout)
    {
        Log::error('Payout failed', [
            'payout_id' => $payout->id,
            'amount' => $payout->amount / 100,
            'failure_code' => $payout->failure_code ?? 'unknown'
        ]);

        // Update withdrawal request and refund balance
        $withdrawalRequest = \App\Models\WithdrawalRequest::where('reference', $payout->id)->first();
        if ($withdrawalRequest) {
            DB::beginTransaction();
            try {
                $withdrawalRequest->update([
                    'status' => 'failed',
                    'rejection_reason' => 'Payout failed: ' . ($payout->failure_message ?? 'Unknown error')
                ]);

                // Refund the held balance
                $withdrawalRequest->wallet->decrement('pending_balance', $withdrawalRequest->amount);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to handle payout failure: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle featured listing payment
     */
    protected function handleFeaturedListingPayment($paymentIntent, $userId)
    {
        DB::beginTransaction();
        try {
            $listingId = $paymentIntent->metadata->listing_id ?? null;
            if (!$listingId) {
                throw new \Exception("Listing ID missing from payment intent");
            }

            $listing = FeaturedListing::find($listingId);
            if (!$listing) {
                throw new \Exception("Featured listing not found: {$listingId}");
            }

            // Check if already processed
            if ($listing->is_active && $listing->stripe_payment_intent_id === $paymentIntent->id) {
                Log::info('Featured listing payment already processed', ['listing_id' => $listingId]);
                DB::rollBack();
                return;
            }

            // Activate the featured listing
            $listing->update([
                'is_active' => true,
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            // Create transaction record
            $user = User::find($userId);
            $wallet = $user->getOrCreateWallet();
            $amount = $paymentIntent->amount / 100;

            $duration = $paymentIntent->metadata->duration ?? 0;
            $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => 'payment',
                'amount' => -$amount,
                'currency' => strtoupper($paymentIntent->currency),
                'status' => 'completed',
                'description' => "Featured listing payment for {$duration} days",
                'reference' => $paymentIntent->id,
                'payment_method' => 'stripe',
                'metadata' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'listing_id' => $listing->id,
                    'product_type' => $listing->product_type,
                    'product_id' => $listing->product_id,
                ],
            ]);

            Log::info('Featured listing payment processed successfully', [
                'listing_id' => $listingId,
                'user_id' => $userId,
                'amount' => $amount
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process featured listing payment: ' . $e->getMessage(), [
                'payment_intent_id' => $paymentIntent->id,
                'user_id' => $userId
            ]);
        }
    }

}
