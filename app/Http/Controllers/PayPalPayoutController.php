<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PayPalPayoutController extends Controller
{
    private $apiUrl;
    private $clientId;
    private $clientSecret;
    private $mode;

    public function __construct()
    {
        $this->mode = config('services.paypal.mode', 'sandbox');
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
        $this->apiUrl = $this->mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Get PayPal OAuth access token
     */
    private function getAccessToken()
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->apiUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get PayPal access token');
        }

        return $response->json()['access_token'];
    }

    /**
     * Create a PayPal payout
     */
    public function createPayout(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10|max:10000',
            'paypal_email' => 'required|email',
            'account_holder' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        $paypalEmail = $request->paypal_email;

        // SECURITY: Email verification required
        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email before withdrawing funds',
                'requires_verification' => true
            ], 403);
        }

        // SECURITY: Check if user can withdraw
        if (!$user->can_withdraw) {
            return response()->json([
                'success' => false,
                'message' => 'Withdrawals are not enabled for your account. Make at least one purchase to enable withdrawals.',
                'can_withdraw' => false
            ], 403);
        }

        // Check wallet balance
        $wallet = $user->wallet;
        if (!$wallet || $wallet->balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance',
                'available_balance' => $wallet ? $wallet->balance : 0,
            ], 400);
        }

        // SECURITY: Only allow withdrawing from wallet_balance (NOT bonus_balance)
        if ($user->wallet_balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient withdrawable balance. Bonus credits cannot be withdrawn.',
                'wallet_balance' => $user->wallet_balance,
                'bonus_balance' => $user->bonus_balance,
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Calculate PayPal fee (2%)
            $feePercent = 0.02;
            $fee = $amount * $feePercent;
            $netAmount = $amount - $fee;

            // Minimum fee is $0.25
            if ($fee < 0.25) {
                $fee = 0.25;
                $netAmount = $amount - $fee;
            }

            // Deduct from wallet
            $wallet->balance -= $amount;
            $wallet->save();

            // Deduct from user's wallet_balance
            $user->decrement('wallet_balance', $amount);

            // Get PayPal access token
            $accessToken = $this->getAccessToken();

            // Generate unique sender batch ID
            $senderBatchId = 'payout_' . $user->id . '_' . time() . '_' . uniqid();

            // Create payout via PayPal Payouts API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post("{$this->apiUrl}/v1/payments/payouts", [
                'sender_batch_header' => [
                    'sender_batch_id' => $senderBatchId,
                    'email_subject' => 'You have a payout from MMO.SUPPLY!',
                    'email_message' => 'You have received a payout from MMO.SUPPLY. Thank you for using our platform!',
                ],
                'items' => [[
                    'recipient_type' => 'EMAIL',
                    'amount' => [
                        'value' => number_format($netAmount, 2, '.', ''),
                        'currency' => 'USD',
                    ],
                    'receiver' => $paypalEmail,
                    'note' => 'Payout from MMO.SUPPLY',
                    'sender_item_id' => 'user_' . $user->id . '_' . time(),
                ]],
            ]);

            if (!$response->successful()) {
                // Refund wallet if payout failed
                $wallet->balance += $amount;
                $wallet->save();
                $user->increment('wallet_balance', $amount);

                $errorMessage = $response->json()['message'] ?? 'Failed to create PayPal payout';
                throw new \Exception($errorMessage);
            }

            $payoutData = $response->json();
            $payoutBatchId = $payoutData['batch_header']['payout_batch_id'];

            // Record wallet transaction
            $transaction = DB::table('transactions')->insertGetId([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal',
                'amount' => -$amount,
                'currency' => $wallet->currency,
                'status' => 'completed',
                'description' => "PayPal payout to {$paypalEmail}",
                'payment_method' => 'paypal',
                'metadata' => json_encode([
                    'payout_batch_id' => $payoutBatchId,
                    'sender_batch_id' => $senderBatchId,
                    'paypal_email' => $paypalEmail,
                    'gross_amount' => $amount,
                    'fee' => $fee,
                    'net_amount' => $netAmount,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Record PayPal payout
            DB::table('paypal_payouts')->insert([
                'user_id' => $user->id,
                'transaction_id' => $transaction,
                'payout_batch_id' => $payoutBatchId,
                'sender_batch_id' => $senderBatchId,
                'paypal_email' => $paypalEmail,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Log::info('PayPal payout created', [
                'user_id' => $user->id,
                'amount' => $amount,
                'payout_batch_id' => $payoutBatchId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PayPal payout processed successfully',
                'payout' => [
                    'payout_batch_id' => $payoutBatchId,
                    'amount' => $amount,
                    'fee' => $fee,
                    'net_amount' => $netAmount,
                    'paypal_email' => $paypalEmail,
                    'status' => 'pending',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayPal payout error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process PayPal payout: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payout status from PayPal
     */
    public function getPayoutStatus($payoutBatchId)
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get("{$this->apiUrl}/v1/payments/payouts/{$payoutBatchId}");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'payout' => $response->json(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payout not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('PayPal payout status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's PayPal payout history
     */
    public function getPayouts()
    {
        $user = Auth::user();

        $payouts = DB::table('paypal_payouts')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'payouts' => $payouts,
        ]);
    }

    /**
     * Webhook handler for PayPal payout status updates
     */
    public function handleWebhook(Request $request)
    {
        try {
            // Verify webhook signature (recommended in production)
            // https://developer.paypal.com/api/rest/webhooks/

            $eventType = $request->input('event_type');
            $resource = $request->input('resource');

            Log::info('PayPal webhook received', [
                'event_type' => $eventType,
                'resource' => $resource,
            ]);

            // Handle different webhook events
            switch ($eventType) {
                case 'PAYMENT.PAYOUTS-ITEM.SUCCEEDED':
                    $this->handlePayoutSuccess($resource);
                    break;
                case 'PAYMENT.PAYOUTS-ITEM.FAILED':
                    $this->handlePayoutFailure($resource);
                    break;
                case 'PAYMENT.PAYOUTS-ITEM.BLOCKED':
                case 'PAYMENT.PAYOUTS-ITEM.CANCELED':
                case 'PAYMENT.PAYOUTS-ITEM.DENIED':
                case 'PAYMENT.PAYOUTS-ITEM.REFUNDED':
                    $this->handlePayoutFailure($resource);
                    break;
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('PayPal webhook error: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Handle successful payout
     */
    private function handlePayoutSuccess($resource)
    {
        $payoutItemId = $resource['payout_item_id'];
        $payoutBatchId = $resource['payout_batch_id'];

        DB::table('paypal_payouts')
            ->where('payout_batch_id', $payoutBatchId)
            ->update([
                'status' => 'success',
                'payout_item_id' => $payoutItemId,
                'updated_at' => now(),
            ]);

        Log::info('PayPal payout succeeded', [
            'payout_batch_id' => $payoutBatchId,
            'payout_item_id' => $payoutItemId,
        ]);
    }

    /**
     * Handle failed payout
     */
    private function handlePayoutFailure($resource)
    {
        $payoutItemId = $resource['payout_item_id'] ?? null;
        $payoutBatchId = $resource['payout_batch_id'];
        $errorMessage = $resource['errors'][0]['message'] ?? 'Unknown error';

        // Find the payout
        $payout = DB::table('paypal_payouts')
            ->where('payout_batch_id', $payoutBatchId)
            ->first();

        if ($payout) {
            // Refund the user's wallet
            $user = DB::table('users')->find($payout->user_id);
            if ($user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->increment('wallet_balance', $payout->amount);

                $wallet = DB::table('wallets')->where('user_id', $user->id)->first();
                if ($wallet) {
                    DB::table('wallets')
                        ->where('id', $wallet->id)
                        ->increment('balance', $payout->amount);
                }
            }

            // Update payout status
            DB::table('paypal_payouts')
                ->where('payout_batch_id', $payoutBatchId)
                ->update([
                    'status' => 'failed',
                    'payout_item_id' => $payoutItemId,
                    'error_message' => $errorMessage,
                    'updated_at' => now(),
                ]);

            Log::warning('PayPal payout failed - refunded user', [
                'payout_batch_id' => $payoutBatchId,
                'user_id' => $payout->user_id,
                'amount' => $payout->amount,
                'error' => $errorMessage,
            ]);
        }
    }
}
