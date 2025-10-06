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
     * Create a payment intent for direct order payment
     */
    public function createOrderPaymentIntent(User $user, float $amount, int $orderId, string $currency = 'usd')
    {
        $amountInCents = (int) ($amount * 100);

        $paymentIntent = PaymentIntent::create([
            'amount' => $amountInCents,
            'currency' => strtolower($currency),
            'customer' => $this->getOrCreateCustomer($user),
            'metadata' => [
                'user_id' => $user->id,
                'order_id' => $orderId,
                'type' => 'order_payment',
            ],
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);

        return $paymentIntent;
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
}
