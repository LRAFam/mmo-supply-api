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

        // PAYOUT RESTRICTIONS: Check daily payout limits
        $restrictionCheck = $this->checkPayoutRestrictions($user, $amount);
        if (!$restrictionCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $restrictionCheck['message'],
                'restriction' => $restrictionCheck['restriction'],
            ], 429); // Too Many Requests
        }

        // Check if payout requires manual review
        $requiresReview = $this->requiresManualReview($amount);
        if ($requiresReview) {
            return $this->createManualPayoutRequest($user, $amount, $paypalEmail, $request->account_holder);
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

            // Deduct from wallet (single source of truth)
            $wallet->balance -= $amount;
            $wallet->save();

            // Sync user's wallet_balance cache to match authoritative wallet balance
            $user->wallet_balance = $wallet->balance;
            $user->save();

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
                // Refund wallet if payout failed (single source of truth)
                $wallet->balance += $amount;
                $wallet->save();

                // Sync user's wallet_balance cache to match authoritative wallet balance
                $user->wallet_balance = $wallet->balance;
                $user->save();

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
     * Get pending manual review payouts (admin only)
     */
    public function getPendingReviews()
    {
        // This should be protected by admin middleware in routes
        $payouts = DB::table('paypal_payouts')
            ->join('users', 'paypal_payouts.user_id', '=', 'users.id')
            ->where('paypal_payouts.status', 'pending_review')
            ->select('paypal_payouts.*', 'users.username', 'users.email as user_email')
            ->orderBy('paypal_payouts.created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'pending_payouts' => $payouts,
        ]);
    }

    /**
     * Approve manual payout request (admin only)
     */
    public function approveManualPayout($payoutId)
    {
        try {
            DB::beginTransaction();

            // Get the payout request
            $payout = DB::table('paypal_payouts')->where('id', $payoutId)->first();

            if (!$payout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payout not found',
                ], 404);
            }

            if ($payout->status !== 'pending_review') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payout is not pending review',
                ], 400);
            }

            $user = DB::table('users')->find($payout->user_id);
            $wallet = DB::table('wallets')->where('user_id', $user->id)->first();

            // Deduct from wallet and pending balance (single source of truth)
            DB::table('wallets')
                ->where('id', $wallet->id)
                ->decrement('balance', $payout->amount);

            DB::table('wallets')
                ->where('id', $wallet->id)
                ->decrement('pending_balance', $payout->amount);

            // Sync user's wallet_balance cache to match wallet balance
            $updatedWallet = DB::table('wallets')->where('id', $wallet->id)->first();
            DB::table('users')
                ->where('id', $user->id)
                ->update(['wallet_balance' => $updatedWallet->balance]);

            // Process the payout via PayPal
            $accessToken = $this->getAccessToken();
            $senderBatchId = 'payout_approved_' . $payout->id . '_' . time() . '_' . uniqid();

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post("{$this->apiUrl}/v1/payments/payouts", [
                'sender_batch_header' => [
                    'sender_batch_id' => $senderBatchId,
                    'email_subject' => 'You have a payout from MMO.SUPPLY!',
                    'email_message' => 'Your payout request has been approved and processed. Thank you for using our platform!',
                ],
                'items' => [[
                    'recipient_type' => 'EMAIL',
                    'amount' => [
                        'value' => number_format($payout->net_amount, 2, '.', ''),
                        'currency' => 'USD',
                    ],
                    'receiver' => $payout->paypal_email,
                    'note' => 'Approved payout from MMO.SUPPLY',
                    'sender_item_id' => 'approved_' . $payout->id,
                ]],
            ]);

            if (!$response->successful()) {
                // Refund if payout failed (single source of truth)
                DB::table('wallets')
                    ->where('id', $wallet->id)
                    ->increment('balance', $payout->amount);

                DB::table('wallets')
                    ->where('id', $wallet->id)
                    ->increment('pending_balance', $payout->amount);

                // Sync user's wallet_balance cache to match wallet balance
                $refundedWallet = DB::table('wallets')->where('id', $wallet->id)->first();
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['wallet_balance' => $refundedWallet->balance]);

                throw new \Exception('Failed to process PayPal payout');
            }

            $payoutData = $response->json();
            $payoutBatchId = $payoutData['batch_header']['payout_batch_id'];

            // Update payout record
            DB::table('paypal_payouts')
                ->where('id', $payoutId)
                ->update([
                    'status' => 'pending',
                    'payout_batch_id' => $payoutBatchId,
                    'sender_batch_id' => $senderBatchId,
                    'updated_at' => now(),
                ]);

            // Create transaction record
            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal',
                'amount' => -$payout->amount,
                'currency' => 'USD',
                'status' => 'completed',
                'description' => "PayPal payout to {$payout->paypal_email} (approved)",
                'payment_method' => 'paypal',
                'metadata' => json_encode([
                    'payout_batch_id' => $payoutBatchId,
                    'sender_batch_id' => $senderBatchId,
                    'paypal_email' => $payout->paypal_email,
                    'gross_amount' => $payout->amount,
                    'fee' => $payout->fee,
                    'net_amount' => $payout->net_amount,
                    'manually_approved' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Log::info('Manual payout approved and processed', [
                'payout_id' => $payoutId,
                'user_id' => $user->id,
                'amount' => $payout->amount,
                'payout_batch_id' => $payoutBatchId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payout approved and processed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manual payout approval error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve payout: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject manual payout request (admin only)
     */
    public function rejectManualPayout(Request $request, $payoutId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $payout = DB::table('paypal_payouts')->where('id', $payoutId)->first();

            if (!$payout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payout not found',
                ], 404);
            }

            if ($payout->status !== 'pending_review') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payout is not pending review',
                ], 400);
            }

            // Release the held funds
            $wallet = DB::table('wallets')->where('user_id', $payout->user_id)->first();
            if ($wallet) {
                DB::table('wallets')
                    ->where('id', $wallet->id)
                    ->decrement('pending_balance', $payout->amount);
            }

            // Update payout status
            DB::table('paypal_payouts')
                ->where('id', $payoutId)
                ->update([
                    'status' => 'failed',
                    'error_message' => 'Rejected by admin: ' . $request->reason,
                    'updated_at' => now(),
                ]);

            DB::commit();

            Log::info('Manual payout rejected', [
                'payout_id' => $payoutId,
                'user_id' => $payout->user_id,
                'reason' => $request->reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payout rejected successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manual payout rejection error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject payout: ' . $e->getMessage(),
            ], 500);
        }
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
            // Refund the user's wallet (single source of truth)
            $user = DB::table('users')->find($payout->user_id);
            if ($user) {
                $wallet = DB::table('wallets')->where('user_id', $user->id)->first();
                if ($wallet) {
                    // Update wallet balance first (source of truth)
                    DB::table('wallets')
                        ->where('id', $wallet->id)
                        ->increment('balance', $payout->amount);

                    // Sync user's wallet_balance cache to match wallet balance
                    $updatedWallet = DB::table('wallets')->where('id', $wallet->id)->first();
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['wallet_balance' => $updatedWallet->balance]);
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

    /**
     * Check if payout meets restriction requirements
     */
    private function checkPayoutRestrictions($user, $amount)
    {
        $now = now();
        $today = $now->startOfDay();

        // Get config values (with defaults)
        $dailyLimit = config('services.paypal.daily_payout_limit', 3);
        $dailyAmountLimit = config('services.paypal.daily_amount_limit', 1000);
        $minHoursBetween = config('services.paypal.min_hours_between', 2);

        // Check 1: Daily payout count limit
        $todaysPayouts = DB::table('paypal_payouts')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $today)
            ->whereIn('status', ['pending', 'success'])
            ->count();

        if ($todaysPayouts >= $dailyLimit) {
            return [
                'allowed' => false,
                'message' => "Daily payout limit reached. You can request up to {$dailyLimit} payouts per day. Try again tomorrow.",
                'restriction' => 'daily_count_limit',
            ];
        }

        // Check 2: Daily amount limit
        $todaysTotal = DB::table('paypal_payouts')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $today)
            ->whereIn('status', ['pending', 'success'])
            ->sum('amount');

        if (($todaysTotal + $amount) > $dailyAmountLimit) {
            $remaining = $dailyAmountLimit - $todaysTotal;
            return [
                'allowed' => false,
                'message' => "Daily payout amount limit reached. You can withdraw up to \${$dailyAmountLimit} per day. Remaining today: \${$remaining}",
                'restriction' => 'daily_amount_limit',
            ];
        }

        // Check 3: Minimum time between payouts
        $lastPayout = DB::table('paypal_payouts')
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'success'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastPayout) {
            $lastPayoutTime = \Carbon\Carbon::parse($lastPayout->created_at);
            $hoursSinceLastPayout = $now->diffInHours($lastPayoutTime);

            if ($hoursSinceLastPayout < $minHoursBetween) {
                $hoursRemaining = $minHoursBetween - $hoursSinceLastPayout;
                $minutesRemaining = ceil($hoursRemaining * 60);
                return [
                    'allowed' => false,
                    'message' => "Please wait {$minutesRemaining} minutes before requesting another payout. Minimum time between payouts is {$minHoursBetween} hours.",
                    'restriction' => 'time_between_payouts',
                ];
            }
        }

        return [
            'allowed' => true,
        ];
    }

    /**
     * Check if payout requires manual review
     */
    private function requiresManualReview($amount)
    {
        $maxAutoAmount = config('services.paypal.auto_max_amount', 500);
        return $amount > $maxAutoAmount;
    }

    /**
     * Create a manual payout request (requires admin approval)
     */
    private function createManualPayoutRequest($user, $amount, $paypalEmail, $accountHolder)
    {
        try {
            DB::beginTransaction();

            // Calculate fee
            $feePercent = 0.02;
            $fee = max($amount * $feePercent, 0.25);
            $netAmount = $amount - $fee;

            // Hold the funds in wallet (don't deduct yet)
            $wallet = $user->wallet;

            // Create pending payout request
            $payoutId = DB::table('paypal_payouts')->insertGetId([
                'user_id' => $user->id,
                'transaction_id' => null, // Will be set when approved
                'payout_batch_id' => null,
                'sender_batch_id' => null,
                'paypal_email' => $paypalEmail,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'status' => 'pending_review',
                'metadata' => json_encode([
                    'account_holder' => $accountHolder,
                    'requires_manual_review' => true,
                    'reason' => 'Amount exceeds automatic payout limit',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update wallet pending balance
            $wallet->pending_balance += $amount;
            $wallet->save();

            DB::commit();

            Log::info('Manual payout request created', [
                'user_id' => $user->id,
                'payout_id' => $payoutId,
                'amount' => $amount,
            ]);

            return response()->json([
                'success' => true,
                'requires_review' => true,
                'message' => 'Your payout request has been submitted for manual review. Large payouts require admin approval for security. You will be notified once approved.',
                'payout' => [
                    'id' => $payoutId,
                    'amount' => $amount,
                    'fee' => $fee,
                    'net_amount' => $netAmount,
                    'paypal_email' => $paypalEmail,
                    'status' => 'pending_review',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manual payout request error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payout request: ' . $e->getMessage(),
            ], 500);
        }
    }
}
