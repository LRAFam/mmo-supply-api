<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CryptoPaymentController extends Controller
{
    private $apiKey;
    private $apiUrl = 'https://api.nowpayments.io/v1';

    public function __construct()
    {
        $this->apiKey = config('services.nowpayments.api_key');
    }

    /**
     * Get available cryptocurrencies
     */
    public function getAvailableCurrencies()
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->apiUrl}/currencies");

            if ($response->successful()) {
                $currencies = $response->json()['currencies'];

                // Get currency info with logos
                $currenciesWithInfo = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                ])->get("{$this->apiUrl}/merchant/coins")->json()['selectedCurrencies'] ?? [];

                return response()->json([
                    'success' => true,
                    'currencies' => $currencies,
                    'currencies_info' => $currenciesWithInfo,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch currencies',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Crypto currencies fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get minimum payment amount for a currency
     */
    public function getMinimumAmount(Request $request)
    {
        $request->validate([
            'currency' => 'required|string',
        ]);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->apiUrl}/min-amount", [
                'currency_from' => 'usd',
                'currency_to' => strtolower($request->currency),
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'min_amount' => $response->json()['min_amount'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch minimum amount',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Crypto min amount error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a crypto deposit payment
     */
    public function createDeposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string',
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        $currency = strtolower($request->currency);

        try {
            DB::beginTransaction();

            // Create payment via NOWPayments
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/payment", [
                'price_amount' => $amount,
                'price_currency' => 'usd',
                'pay_currency' => $currency,
                'ipn_callback_url' => config('app.url') . '/api/crypto/webhook',
                'order_id' => 'deposit_' . $user->id . '_' . time(),
                'order_description' => "Wallet deposit for user {$user->name}",
            ]);

            if (!$response->successful()) {
                throw new \Exception($response->json()['message'] ?? 'Failed to create payment');
            }

            $paymentData = $response->json();

            // Store transaction record
            $transaction = DB::table('crypto_transactions')->insertGetId([
                'user_id' => $user->id,
                'type' => 'deposit',
                'payment_id' => $paymentData['payment_id'],
                'amount_usd' => $amount,
                'crypto_amount' => $paymentData['pay_amount'],
                'currency' => $currency,
                'status' => 'waiting',
                'payment_address' => $paymentData['pay_address'],
                'payin_extra_id' => $paymentData['payin_extra_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'payment' => [
                    'id' => $transaction,
                    'payment_id' => $paymentData['payment_id'],
                    'pay_address' => $paymentData['pay_address'],
                    'pay_amount' => $paymentData['pay_amount'],
                    'payin_extra_id' => $paymentData['payin_extra_id'] ?? null,
                    'currency' => $currency,
                    'amount_usd' => $amount,
                    'qr_code_url' => "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($paymentData['pay_address']),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Crypto deposit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a crypto payout/withdrawal
     */
    public function createPayout(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
            'currency' => 'required|string',
            'wallet_address' => 'required|string',
            'extra_id' => 'nullable|string', // For currencies like XRP, XLM
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        $currency = strtolower($request->currency);

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

            // Create payout via NOWPayments
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/payout", [
                'withdrawals' => [[
                    'address' => $request->wallet_address,
                    'currency' => $currency,
                    'amount' => $amount,
                    'extra_id' => $request->extra_id,
                    'ipn_callback_url' => config('app.url') . '/api/crypto/payout-webhook',
                ]],
            ]);

            if (!$response->successful()) {
                // Refund wallet if payout failed
                $wallet->balance += $amount;
                $wallet->save();

                throw new \Exception($response->json()['message'] ?? 'Failed to create payout');
            }

            $payoutData = $response->json()['withdrawals'][0];

            // Store transaction record
            $transaction = DB::table('crypto_transactions')->insertGetId([
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'payment_id' => $payoutData['id'],
                'amount_usd' => $amount,
                'crypto_amount' => $amount, // Will be updated by webhook
                'currency' => $currency,
                'status' => 'pending',
                'payment_address' => $request->wallet_address,
                'payin_extra_id' => $request->extra_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Record wallet transaction
            DB::table('wallet_transactions')->insert([
                'user_id' => $user->id,
                'type' => 'crypto_withdrawal',
                'amount' => -$amount,
                'description' => "Crypto withdrawal to {$currency} wallet",
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'payout' => [
                    'id' => $transaction,
                    'payment_id' => $payoutData['id'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => 'pending',
                    'wallet_address' => $request->wallet_address,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Crypto payout error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($paymentId)
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->apiUrl}/payment/{$paymentId}");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'payment' => $response->json(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Crypto payment status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's crypto transaction history
     */
    public function getTransactions()
    {
        $user = Auth::user();

        $transactions = DB::table('crypto_transactions')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Webhook handler for payment updates
     */
    public function handleWebhook(Request $request)
    {
        try {
            $paymentId = $request->input('payment_id');
            $paymentStatus = $request->input('payment_status');

            Log::info('Crypto webhook received', $request->all());

            // Find transaction
            $transaction = DB::table('crypto_transactions')
                ->where('payment_id', $paymentId)
                ->first();

            if (!$transaction) {
                return response()->json(['success' => false], 404);
            }

            // Update transaction status
            DB::table('crypto_transactions')
                ->where('payment_id', $paymentId)
                ->update([
                    'status' => $paymentStatus,
                    'updated_at' => now(),
                ]);

            // If deposit is confirmed, credit wallet
            if ($transaction->type === 'deposit' && $paymentStatus === 'finished') {
                $user = DB::table('users')->find($transaction->user_id);
                $wallet = DB::table('wallets')->where('user_id', $user->id)->first();

                if ($wallet) {
                    DB::table('wallets')
                        ->where('id', $wallet->id)
                        ->increment('balance', $transaction->amount_usd);

                    // Record wallet transaction
                    DB::table('wallet_transactions')->insert([
                        'user_id' => $user->id,
                        'type' => 'crypto_deposit',
                        'amount' => $transaction->amount_usd,
                        'description' => "Crypto deposit from {$transaction->currency}",
                        'status' => 'completed',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Crypto webhook error: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }
}
