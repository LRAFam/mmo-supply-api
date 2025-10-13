<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PayPalCheckoutController extends Controller
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
     * Create PayPal order for wallet deposit
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5|max:10000',
        ]);

        $user = Auth::user();
        $amount = $request->amount;

        try {
            $accessToken = $this->getAccessToken();

            // Create PayPal order
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post("{$this->apiUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                    'description' => 'MMO.SUPPLY Wallet Deposit',
                    'custom_id' => 'user_' . $user->id . '_' . time(),
                ]],
                'application_context' => [
                    'brand_name' => 'MMO.SUPPLY',
                    'landing_page' => 'NO_PREFERENCE',
                    'user_action' => 'PAY_NOW',
                    'return_url' => config('app.frontend_url') . '/wallet/deposit/success',
                    'cancel_url' => config('app.frontend_url') . '/wallet/deposit',
                ],
            ]);

            if (!$response->successful()) {
                $errorMessage = $response->json()['message'] ?? 'Failed to create PayPal order';
                throw new \Exception($errorMessage);
            }

            $orderData = $response->json();
            $orderId = $orderData['id'];

            // Store pending transaction
            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'wallet_id' => $user->wallet->id,
                'type' => 'deposit',
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'pending',
                'description' => 'PayPal wallet deposit (pending)',
                'payment_method' => 'paypal',
                'metadata' => json_encode([
                    'paypal_order_id' => $orderId,
                    'amount' => $amount,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('PayPal order created', [
                'user_id' => $user->id,
                'order_id' => $orderId,
                'amount' => $amount,
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $orderId,
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal order creation error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create PayPal order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Capture PayPal order and credit user's wallet
     */
    public function captureOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
        ]);

        $user = Auth::user();
        $orderId = $request->order_id;

        try {
            DB::beginTransaction();

            $accessToken = $this->getAccessToken();

            // Capture the order
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post("{$this->apiUrl}/v2/checkout/orders/{$orderId}/capture");

            if (!$response->successful()) {
                $errorMessage = $response->json()['message'] ?? 'Failed to capture PayPal order';
                throw new \Exception($errorMessage);
            }

            $captureData = $response->json();
            $status = $captureData['status'];

            if ($status !== 'COMPLETED') {
                throw new \Exception('PayPal order capture failed: ' . $status);
            }

            // Get the captured amount
            $capturedAmount = floatval($captureData['purchase_units'][0]['payments']['captures'][0]['amount']['value']);
            $captureId = $captureData['purchase_units'][0]['payments']['captures'][0]['id'];

            // Credit user's wallet
            $wallet = $user->wallet;
            $wallet->balance += $capturedAmount;
            $wallet->save();

            // Update user's wallet_balance
            $user->increment('wallet_balance', $capturedAmount);

            // Update transaction to completed
            DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('payment_method', 'paypal')
                ->whereRaw("JSON_EXTRACT(metadata, '$.paypal_order_id') = ?", [$orderId])
                ->update([
                    'status' => 'completed',
                    'description' => 'PayPal wallet deposit',
                    'metadata' => DB::raw("JSON_SET(metadata, '$.capture_id', '$captureId', '$.captured_amount', $capturedAmount)"),
                    'updated_at' => now(),
                ]);

            DB::commit();

            Log::info('PayPal order captured successfully', [
                'user_id' => $user->id,
                'order_id' => $orderId,
                'capture_id' => $captureId,
                'amount' => $capturedAmount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Deposit successful',
                'amount' => $capturedAmount,
                'new_balance' => $wallet->balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('PayPal order capture error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'order_id' => $orderId,
            ]);

            // Update transaction to failed
            DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('payment_method', 'paypal')
                ->whereRaw("JSON_EXTRACT(metadata, '$.paypal_order_id') = ?", [$orderId])
                ->update([
                    'status' => 'failed',
                    'description' => 'PayPal wallet deposit (failed)',
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process PayPal payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order details (for verification)
     */
    public function getOrderDetails($orderId)
    {
        $user = Auth::user();

        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get("{$this->apiUrl}/v2/checkout/orders/{$orderId}");

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'order' => $response->json(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('PayPal order details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
