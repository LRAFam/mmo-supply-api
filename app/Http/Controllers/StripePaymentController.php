<?php

namespace App\Http\Controllers;

use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StripePaymentController extends Controller
{
    /**
     * Get payment intent details
     */
    public function getPaymentIntent(Request $request, string $paymentIntentId): JsonResponse
    {
        try {
            $stripeService = new StripePaymentService();
            $paymentIntent = $stripeService->getPaymentIntent($paymentIntentId);

            return response()->json([
                'id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'status' => $paymentIntent->status,
                'client_secret' => $paymentIntent->client_secret,
                'metadata' => $paymentIntent->metadata,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
