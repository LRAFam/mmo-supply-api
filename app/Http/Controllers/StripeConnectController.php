<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Transfer;

class StripeConnectController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Connect account link for onboarding
     */
    public function createAccountLink(Request $request)
    {
        $user = Auth::user();

        try {
            // Create Stripe Connect account if user doesn't have one
            if (!$user->stripe_account_id) {
                // Get country from request or user profile, default to GB (UK)
                $country = $request->input('country', $user->country ?? 'GB');

                // For US accounts, we need both card_payments and transfers capabilities
                // For other countries, transfers is usually sufficient
                $capabilities = ['transfers' => ['requested' => true]];

                if ($country === 'US') {
                    $capabilities['card_payments'] = ['requested' => true];
                }

                $account = Account::create([
                    'type' => 'express',
                    'country' => strtoupper($country),
                    'email' => $user->email,
                    'capabilities' => $capabilities,
                    'metadata' => [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                    ],
                ]);

                $user->stripe_account_id = $account->id;
                $user->save();
            }

            // Create account link for onboarding
            $accountLink = AccountLink::create([
                'account' => $user->stripe_account_id,
                'refresh_url' => $request->refresh_url ?? config('app.frontend_url') . '/seller/stripe/refresh',
                'return_url' => $request->return_url ?? config('app.frontend_url') . '/seller/stripe/return',
                'type' => 'account_onboarding',
            ]);

            return response()->json([
                'success' => true,
                'url' => $accountLink->url,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Connect account link error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create account link: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Stripe Connect account status
     */
    public function getAccountStatus()
    {
        $user = Auth::user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'success' => true,
                'connected' => false,
                'account_id' => null,
                'charges_enabled' => false,
                'payouts_enabled' => false,
                'details_submitted' => false,
                'requirements' => null,
            ]);
        }

        try {
            $account = Account::retrieve($user->stripe_account_id);

            // Update user record with latest status
            $user->stripe_account_verified = $account->details_submitted && $account->charges_enabled;
            $user->stripe_payouts_enabled = $account->payouts_enabled;
            $user->save();

            return response()->json([
                'success' => true,
                'connected' => true,
                'account_id' => $account->id,
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,
                'requirements' => [
                    'currently_due' => $account->requirements->currently_due ?? [],
                    'eventually_due' => $account->requirements->eventually_due ?? [],
                    'past_due' => $account->requirements->past_due ?? [],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Connect account status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve account status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a login link to Stripe Express Dashboard
     */
    public function createDashboardLink(Request $request)
    {
        $user = Auth::user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'success' => false,
                'message' => 'No Stripe account connected',
            ], 400);
        }

        try {
            // First, check if the account has completed onboarding
            $account = Account::retrieve($user->stripe_account_id);

            // If account hasn't completed onboarding, return an onboarding link instead
            if (!$account->details_submitted) {
                $accountLink = AccountLink::create([
                    'account' => $user->stripe_account_id,
                    'refresh_url' => $request->input('refresh_url', config('app.frontend_url') . '/seller/stripe/refresh'),
                    'return_url' => $request->input('return_url', config('app.frontend_url') . '/seller/stripe/return'),
                    'type' => 'account_onboarding',
                ]);

                return response()->json([
                    'success' => true,
                    'url' => $accountLink->url,
                    'onboarding_incomplete' => true,
                    'message' => 'Please complete your Stripe onboarding first',
                ]);
            }

            // Account is fully onboarded, create dashboard login link
            $loginLink = Account::createLoginLink($user->stripe_account_id);

            return response()->json([
                'success' => true,
                'url' => $loginLink->url,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Connect dashboard link error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create dashboard link: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Request payout from wallet to Stripe Connect account
     */
    public function requestPayout(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        $user = Auth::user();
        $amount = $request->amount;

        // Check if user has Stripe Connect account
        if (!$user->stripe_account_id) {
            return response()->json([
                'success' => false,
                'message' => 'Please connect your Stripe account first to receive payouts',
            ], 400);
        }

        // Check if account is verified
        if (!$user->stripe_payouts_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Your Stripe account is not yet verified for payouts. Please complete the onboarding process.',
            ], 400);
        }

        // Check wallet balance
        $wallet = $user->wallet;
        if (!$wallet || $wallet->balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Deduct from wallet
            $wallet->balance -= $amount;
            $wallet->save();

            // Create wallet transaction record
            DB::table('wallet_transactions')->insert([
                'user_id' => $user->id,
                'type' => 'payout',
                'amount' => -$amount,
                'description' => 'Payout to Stripe Connect account',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create Stripe Transfer to connected account
            $transfer = Transfer::create([
                'amount' => (int)($amount * 100), // Convert to cents
                'currency' => 'usd',
                'destination' => $user->stripe_account_id,
                'description' => "Payout to {$user->name}",
                'metadata' => [
                    'user_id' => $user->id,
                    'wallet_balance_before' => $wallet->balance + $amount,
                ],
            ]);

            // Record payout
            DB::table('stripe_payouts')->insert([
                'user_id' => $user->id,
                'stripe_transfer_id' => $transfer->id,
                'amount' => $amount,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payout processed successfully',
                'payout' => [
                    'amount' => $amount,
                    'transfer_id' => $transfer->id,
                    'status' => 'completed',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stripe payout error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payout: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payout history
     */
    public function getPayouts()
    {
        $user = Auth::user();

        $payouts = DB::table('stripe_payouts')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'payouts' => $payouts,
        ]);
    }

    /**
     * Disconnect Stripe Connect account
     */
    public function disconnect()
    {
        $user = Auth::user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'success' => false,
                'message' => 'No Stripe account connected',
            ], 400);
        }

        try {
            // Note: Deleting the account removes it from Stripe
            // Account::retrieve($user->stripe_account_id)->delete();

            // Instead, just remove the reference from user
            $user->stripe_account_id = null;
            $user->stripe_account_verified = false;
            $user->stripe_payouts_enabled = false;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Stripe account disconnected successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe disconnect error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect account: ' . $e->getMessage(),
            ], 500);
        }
    }
}
