<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\OAuth as StripeOAuth;
use Stripe\Stripe;

class PaymentProcessorController extends Controller
{
    /**
     * Initiate Stripe Connect using Account Links API (Express/Standard)
     */
    public function stripeConnect(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Stripe::setApiKey(config('services.stripe.secret'));

            // Check if user already has a Stripe Connect account
            if ($user->stripe_connect_id) {
                $account = \Stripe\Account::retrieve($user->stripe_connect_id);
            } else {
                // Create a new Stripe Connect account
                $account = \Stripe\Account::create([
                    'type' => 'express',
                    'email' => $user->email,
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers' => ['requested' => true],
                    ],
                    'business_type' => 'individual',
                ]);

                // Save the account ID
                $user->stripe_connect_id = $account->id;
                $user->save();
            }

            // Create an Account Link for onboarding
            $accountLink = \Stripe\AccountLink::create([
                'account' => $account->id,
                'refresh_url' => config('app.frontend_url') . '/become-seller?stripe_refresh=true',
                'return_url' => config('app.frontend_url') . '/settings/payment-processors/stripe/callback?account_id=' . $account->id,
                'type' => 'account_onboarding',
            ]);

            return response()->json([
                'success' => true,
                'url' => $accountLink->url,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Connect initiation error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate Stripe Connect. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle Stripe Connect callback (Account Links)
     */
    public function stripeCallback(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|string',
        ]);

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Retrieve the account to check its status
            $account = \Stripe\Account::retrieve($request->account_id);

            // Find the user with this Stripe account
            $user = \App\Models\User::where('stripe_connect_id', $request->account_id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found for this Stripe account',
                ], 404);
            }

            // Check if account is fully onboarded
            $isComplete = $account->details_submitted &&
                         $account->charges_enabled &&
                         $account->payouts_enabled;

            // Update user's Stripe Connect status
            $user->stripe_connect_enabled = $isComplete;
            $user->stripe_connect_data = [
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,
                'requirements' => $account->requirements->toArray(),
                'connected_at' => now()->toIso8601String(),
            ];
            $user->save();

            return response()->json([
                'success' => true,
                'message' => $isComplete
                    ? 'Stripe Connect account linked successfully!'
                    : 'Stripe account created. Please complete the onboarding process.',
                'stripe_connect_id' => $user->stripe_connect_id,
                'is_complete' => $isComplete,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Connect callback error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify Stripe account. Please try again.',
            ], 500);
        }
    }

    /**
     * Disconnect Stripe Connect account
     */
    public function stripeDisconnect(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->stripe_connect_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Stripe Connect account linked',
                ], 400);
            }

            // Revoke Stripe Connect access
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $connectData = $user->stripe_connect_data;

                if ($connectData && isset($connectData['access_token'])) {
                    StripeOAuth::deauthorize([
                        'client_id' => config('services.stripe.connect_client_id') ?: config('services.stripe.key'),
                        'stripe_user_id' => $user->stripe_connect_id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to revoke Stripe Connect access', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Clear Stripe Connect data from user
            $user->stripe_connect_id = null;
            $user->stripe_connect_enabled = false;
            $user->stripe_connect_data = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Stripe Connect account disconnected successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe disconnect error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect Stripe account',
            ], 500);
        }
    }

    /**
     * Initiate PayPal OAuth flow
     */
    public function paypalConnect(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Generate state parameter for CSRF protection
            $state = base64_encode(json_encode([
                'user_id' => $user->id,
                'timestamp' => time(),
                'nonce' => bin2hex(random_bytes(16))
            ]));

            // Store state in cache for verification
            cache()->put('paypal_oauth_state_' . $user->id, $state, now()->addMinutes(15));

            $clientId = config('services.paypal.client_id');
            $mode = config('services.paypal.mode');

            // PayPal OAuth URL (different for sandbox vs production)
            $baseUrl = $mode === 'live'
                ? 'https://www.paypal.com'
                : 'https://www.sandbox.paypal.com';

            $params = [
                'client_id' => $clientId,
                'response_type' => 'code',
                'scope' => 'openid profile email',
                'redirect_uri' => config('app.frontend_url') . '/settings/payment-processors/paypal/callback',
                'state' => $state,
            ];

            $url = $baseUrl . '/connect/?' . http_build_query($params);

            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal Connect initiation error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate PayPal connection. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle PayPal OAuth callback
     */
    public function paypalCallback(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
        ]);

        try {
            // Decode and verify state
            $stateData = json_decode(base64_decode($request->state), true);
            $userId = $stateData['user_id'] ?? null;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid state parameter',
                ], 400);
            }

            // Verify state matches what we stored
            $cachedState = cache()->get('paypal_oauth_state_' . $userId);
            if ($cachedState !== $request->state) {
                return response()->json([
                    'success' => false,
                    'message' => 'State verification failed',
                ], 400);
            }

            cache()->forget('paypal_oauth_state_' . $userId);

            // Exchange authorization code for access token
            $mode = config('services.paypal.mode');
            $baseUrl = $mode === 'live'
                ? 'https://api.paypal.com'
                : 'https://api.sandbox.paypal.com';

            $response = \Http::asForm()->withBasicAuth(
                config('services.paypal.client_id'),
                config('services.paypal.client_secret')
            )->post($baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'authorization_code',
                'code' => $request->code,
            ]);

            if (!$response->successful()) {
                throw new \Exception('PayPal token exchange failed: ' . $response->body());
            }

            $tokenData = $response->json();

            // Get user info from PayPal
            $userInfoResponse = \Http::withToken($tokenData['access_token'])
                ->get($baseUrl . '/v1/identity/oauth2/userinfo?schema=paypalv1.1');

            if (!$userInfoResponse->successful()) {
                throw new \Exception('PayPal user info fetch failed');
            }

            $paypalUser = $userInfoResponse->json();

            $user = \App\Models\User::findOrFail($userId);

            // Store PayPal account details
            $user->paypal_merchant_id = $paypalUser['user_id'] ?? $paypalUser['payer_id'] ?? null;
            $user->paypal_enabled = true;
            $user->paypal_data = [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_type' => $tokenData['token_type'],
                'expires_in' => $tokenData['expires_in'],
                'email' => $paypalUser['email'] ?? null,
                'verified_account' => $paypalUser['verified_account'] ?? false,
                'connected_at' => now()->toIso8601String(),
            ];
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'PayPal account linked successfully!',
                'paypal_merchant_id' => $user->paypal_merchant_id,
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal Connect callback error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect PayPal account. Please try again.',
            ], 500);
        }
    }

    /**
     * Disconnect PayPal account
     */
    public function paypalDisconnect(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->paypal_merchant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No PayPal account linked',
                ], 400);
            }

            // Clear PayPal data from user
            $user->paypal_merchant_id = null;
            $user->paypal_enabled = false;
            $user->paypal_data = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'PayPal account disconnected successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal disconnect error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect PayPal account',
            ], 500);
        }
    }

    /**
     * Get current payment processor connection status
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'stripe' => [
                'connected' => $user->stripe_connect_enabled ?? false,
                'account_id' => $user->stripe_connect_id,
            ],
            'paypal' => [
                'connected' => $user->paypal_enabled ?? false,
                'merchant_id' => $user->paypal_merchant_id,
            ],
            'payment_methods' => $user->payment_methods ?? [],
        ]);
    }
}
