<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    /**
     * Get user's wallet with balance and recent transactions
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        $wallet->load([
            'transactions' => function ($query) {
                $query->latest()->limit(10);
            }
        ]);

        return response()->json([
            'wallet' => $wallet,
            'available_balance' => $wallet->available_balance,
            'wallet_balance' => floatval($wallet->balance),
            'pending_balance' => floatval($wallet->pending_balance),
            'bonus_balance' => floatval($wallet->bonus_balance ?? 0),
            'total_balance' => $wallet->total_balance,
        ]);
    }

    /**
     * Get all transactions with pagination
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        $query = $wallet->transactions()->with('order');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Date range
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $transactions = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($transactions);
    }

    /**
     * Create a deposit payment intent
     */
    public function deposit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:10000',
            'payment_method' => 'required|string|in:stripe,paypal,crypto,bank_transfer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        try {
            // Handle Stripe deposits
            if ($request->payment_method === 'stripe') {
                $stripeService = new StripePaymentService();
                $paymentIntent = $stripeService->createDepositIntent($user, $request->amount);

                return response()->json([
                    'message' => 'Payment intent created',
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id,
                ]);
            }

            // Handle other payment methods (to be implemented)
            // For now, auto-complete in local environment
            if (app()->environment('local')) {
                DB::beginTransaction();

                $transaction = $wallet->transactions()->create([
                    'user_id' => $user->id,
                    'type' => 'deposit',
                    'amount' => $request->amount,
                    'currency' => $wallet->currency,
                    'status' => 'completed',
                    'description' => 'Wallet deposit via ' . $request->payment_method,
                    'payment_method' => $request->payment_method,
                ]);

                $wallet->increment('balance', $request->amount);

                DB::commit();

                return response()->json([
                    'message' => 'Deposit completed successfully',
                    'transaction' => $transaction,
                    'wallet' => $wallet->fresh(),
                ]);
            }

            return response()->json([
                'message' => 'Payment method not yet implemented',
            ], 501);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process deposit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Request a withdrawal
     */
    public function requestWithdrawal(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10|max:10000',
            'method' => 'required|string|in:stripe,paypal,bank_transfer,crypto,other',
            'payment_details' => 'required|array',
            'payment_details.account_holder' => 'required|string|max:255',
            'payment_details.account_info' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        // SECURITY: Email verification required
        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'Please verify your email before withdrawing funds',
                'requires_verification' => true
            ], 403);
        }

        // SECURITY: Check if user can withdraw
        if (!$user->can_withdraw) {
            return response()->json([
                'message' => 'Withdrawals are not enabled for your account. Make at least one purchase to enable withdrawals.',
                'can_withdraw' => false
            ], 403);
        }

        // SECURITY: Check withdrawal cooldown
        if ($user->withdrawal_eligible_at && now()->lt($user->withdrawal_eligible_at)) {
            return response()->json([
                'message' => 'Withdrawal cooldown active. Please wait until the cooldown period ends.',
                'eligible_at' => $user->withdrawal_eligible_at
            ], 403);
        }

        // SECURITY: Require at least one purchase
        if ($user->total_purchases < 1) {
            return response()->json([
                'message' => 'You must make at least one purchase before withdrawing funds',
                'total_purchases' => $user->total_purchases
            ], 403);
        }

        // SECURITY: Minimum withdrawal amount
        $minWithdrawal = 10.00;
        if ($request->amount < $minWithdrawal) {
            return response()->json([
                'message' => "Minimum withdrawal amount is $" . number_format($minWithdrawal, 2),
                'minimum_amount' => $minWithdrawal
            ], 400);
        }

        // SECURITY: Only allow withdrawing from wallet_balance (NOT bonus_balance)
        if ($user->wallet_balance < $request->amount) {
            return response()->json([
                'message' => 'Insufficient withdrawable balance. Bonus credits cannot be withdrawn.',
                'wallet_balance' => $user->wallet_balance,
                'bonus_balance' => $user->bonus_balance,
                'requested_amount' => $request->amount
            ], 400);
        }

        // Check wallet available balance as well
        if ($wallet->available_balance < $request->amount) {
            return response()->json([
                'message' => 'Insufficient balance',
                'available_balance' => $wallet->available_balance,
            ], 400);
        }

        try {
            DB::beginTransaction();

            // SECURITY: Apply 5% withdrawal fee
            $withdrawalFeePercent = 0.05;
            $fee = $request->amount * $withdrawalFeePercent;
            $netAmount = $request->amount - $fee;

            // Create withdrawal request
            $withdrawalRequest = $wallet->withdrawalRequests()->create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'currency' => $wallet->currency,
                'method' => $request->method,
                'payment_details' => $request->payment_details,
                'status' => 'pending',
            ]);

            // Move funds from available balance to pending (single source of truth)
            $wallet->decrement('balance', $request->amount);
            $wallet->increment('pending_balance', $request->amount);

            // Sync user's wallet_balance cache to match authoritative wallet balance
            $user->wallet_balance = $wallet->balance;
            $user->save();

            DB::commit();

            return response()->json([
                'message' => 'Withdrawal request submitted successfully',
                'withdrawal_request' => $withdrawalRequest,
                'gross_amount' => $request->amount,
                'fee' => $fee,
                'fee_percent' => ($withdrawalFeePercent * 100) . '%',
                'net_amount' => $netAmount,
                'wallet' => $wallet->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process withdrawal request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get withdrawal requests
     */
    public function withdrawalRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = WithdrawalRequest::where('user_id', $user->id)
            ->with(['transaction', 'approver']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($requests);
    }

    /**
     * Cancel a pending withdrawal request
     */
    public function cancelWithdrawal(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $withdrawalRequest = WithdrawalRequest::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($withdrawalRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Can only cancel pending withdrawal requests'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $withdrawalRequest->update(['status' => 'cancelled']);

            // Release the held balance
            $withdrawalRequest->wallet->decrement('pending_balance', $withdrawalRequest->amount);

            DB::commit();

            return response()->json([
                'message' => 'Withdrawal request cancelled successfully',
                'withdrawal_request' => $withdrawalRequest,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to cancel withdrawal request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
