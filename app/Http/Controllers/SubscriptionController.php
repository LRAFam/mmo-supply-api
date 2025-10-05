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

    /**
     * Get all user subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            // Get all Cashier subscriptions
            $subscriptions = $user->subscriptions()->get()->map(function ($subscription) {
                // Determine tier from stripe_price
                $tier = 'free';
                if ($subscription->stripe_price === env('STRIPE_PRICE_PREMIUM')) {
                    $tier = 'premium';
                } elseif ($subscription->stripe_price === env('STRIPE_PRICE_ELITE')) {
                    $tier = 'elite';
                }

                // Get price
                $price = match($tier) {
                    'premium' => 9.99,
                    'elite' => 29.99,
                    default => 0
                };

                // Calculate next billing date
                $nextBillingDate = null;
                try {
                    $stripeSubscription = $subscription->asStripeSubscription();
                    $nextBillingDate = $stripeSubscription->current_period_end
                        ? date('Y-m-d H:i:s', $stripeSubscription->current_period_end)
                        : null;
                } catch (\Exception $e) {
                    // Fallback calculation
                    if ($subscription->ends_at) {
                        $nextBillingDate = $subscription->ends_at;
                    } else {
                        $createdDate = \Carbon\Carbon::parse($subscription->created_at);
                        $now = \Carbon\Carbon::now();
                        $nextBilling = $createdDate->copy();
                        while ($nextBilling->lessThan($now)) {
                            $nextBilling->addMonth();
                        }
                        $nextBillingDate = $nextBilling->format('Y-m-d H:i:s');
                    }
                }

                return [
                    'id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'type' => $subscription->type ?? 'user_subscription',
                    'tier' => $tier,
                    'price' => $price,
                    'status' => $subscription->stripe_status,
                    'auto_renew' => $subscription->ends_at === null,
                    'next_billing_date' => $nextBillingDate,
                    'created_at' => $subscription->created_at,
                    'cancelled_at' => $subscription->ends_at,
                    'expires_at' => $subscription->ends_at,
                ];
            });

            return response()->json(['subscriptions' => $subscriptions]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch subscriptions: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch subscriptions'], 500);
        }
    }

    /**
     * Update auto-renew setting
     */
    public function updateAutoRenew(Request $request, $subscriptionId): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'auto_renew' => 'required|boolean'
        ]);

        try {
            $subscription = $user->subscriptions()->findOrFail($subscriptionId);

            if ($request->auto_renew) {
                // Enable auto-renew by resuming
                if ($subscription->ends_at) {
                    $subscription->resume();
                }
            } else {
                // Disable auto-renew by canceling at period end
                if (!$subscription->ends_at) {
                    $subscription->cancel();
                }
            }

            return response()->json([
                'success' => true,
                'message' => $request->auto_renew ? 'Auto-renew enabled' : 'Auto-renew disabled'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to update auto-renew: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update auto-renew setting'], 500);
        }
    }

    /**
     * Cancel specific subscription
     */
    public function destroy(Request $request, $subscriptionId): JsonResponse
    {
        $user = $request->user();

        try {
            $subscription = $user->subscriptions()->findOrFail($subscriptionId);

            $subscription->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Subscription will be canceled at the end of the billing period'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to cancel subscription: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to cancel subscription'], 500);
        }
    }
}
