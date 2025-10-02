<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    /**
     * Get current user's subscription details
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get subscription tier (free, premium, elite)
        $tier = $user->getSubscriptionTier();

        // Get active Cashier subscription if exists
        $activeSubscription = $user->subscriptions()->where('stripe_status', 'active')->first();

        if (!$activeSubscription) {
            // User is on free tier
            return response()->json([
                'subscription' => [
                    'tier' => 'free',
                    'price' => 0,
                    'status' => 'active',
                    'created_at' => $user->created_at,
                    'current_period_end' => null,
                    'cancel_at_period_end' => false,
                    'payment_method' => null,
                ]
            ]);
        }

        // Get billing period from Stripe
        $currentPeriodEnd = null;
        try {
            $stripeSubscription = $activeSubscription->asStripeSubscription();
            $currentPeriodEnd = $stripeSubscription->current_period_end
                ? date('Y-m-d H:i:s', $stripeSubscription->current_period_end)
                : null;
        } catch (\Exception $e) {
            // Fallback: calculate next billing date based on created_at + 1 month
            if ($activeSubscription->ends_at) {
                // Subscription is canceled, use ends_at
                $currentPeriodEnd = $activeSubscription->ends_at;
            } elseif ($activeSubscription->trial_ends_at) {
                // In trial period
                $currentPeriodEnd = $activeSubscription->trial_ends_at;
            } else {
                // Calculate based on creation date (monthly billing)
                $createdDate = \Carbon\Carbon::parse($activeSubscription->created_at);
                $now = \Carbon\Carbon::now();

                // Find next billing date
                $nextBilling = $createdDate->copy();
                while ($nextBilling->lessThan($now)) {
                    $nextBilling->addMonth();
                }
                $currentPeriodEnd = $nextBilling->format('Y-m-d H:i:s');
            }
        }

        // Premium or Elite tier
        return response()->json([
            'subscription' => [
                'tier' => $tier,
                'price' => $tier === 'elite' ? 29.99 : 9.99,
                'status' => $activeSubscription->stripe_status,
                'created_at' => $activeSubscription->created_at,
                'current_period_end' => $currentPeriodEnd,
                'cancel_at_period_end' => $activeSubscription->ends_at !== null,
                'payment_method' => $user->pm_type ? ucfirst($user->pm_type) . ' ending in ' . $user->pm_last_four : null,
            ]
        ]);
    }

    /**
     * Get user's billing invoices
     */
    public function invoices(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasStripeId()) {
            return response()->json(['invoices' => []]);
        }

        try {
            $invoices = $user->invoices();

            $formattedInvoices = collect($invoices)->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'created' => $invoice->created,
                    'description' => $invoice->lines->data[0]->description ?? 'Subscription payment',
                    'amount_paid' => $invoice->amount_paid,
                    'status' => $invoice->status,
                    'invoice_pdf' => $invoice->invoice_pdf,
                ];
            })->toArray();

            return response()->json(['invoices' => $formattedInvoices]);
        } catch (\Exception $e) {
            return response()->json(['invoices' => []]);
        }
    }

    /**
     * Create Stripe billing portal session
     */
    public function portal(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasStripeId()) {
            return response()->json(['error' => 'No Stripe customer found. Please subscribe through the checkout page.'], 400);
        }

        try {
            // Use environment URL or default to frontend URL
            $returnUrl = config('app.frontend_url', 'http://localhost:3000') . '/subscription/manage';

            $url = $user->billingPortalUrl($returnUrl);

            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            \Log::error('Stripe portal error: ' . $e->getMessage());

            // Check if it's a configuration error
            if (str_contains($e->getMessage(), 'configuration')) {
                return response()->json([
                    'error' => 'Stripe billing portal not configured. Please use the cancel/resume buttons below or contact support.'
                ], 503);
            }

            return response()->json(['error' => 'Unable to access billing portal. Please contact support.'], 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->subscription();

        if (!$subscription) {
            return response()->json(['error' => 'No active subscription found'], 404);
        }

        try {
            $subscription->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Subscription will be canceled at the end of the billing period'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to cancel subscription'], 500);
        }
    }

    /**
     * Resume canceled subscription
     */
    public function resume(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->subscription();

        if (!$subscription) {
            return response()->json(['error' => 'No subscription found'], 404);
        }

        try {
            $subscription->resume();

            return response()->json([
                'success' => true,
                'message' => 'Subscription has been resumed'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to resume subscription'], 500);
        }
    }
}
