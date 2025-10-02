<?php

namespace App\Http\Controllers;

use App\Models\FeaturedListing;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FeaturedListingController extends Controller
{
    /**
     * Get pricing for featured listings
     */
    public function getPricing(): JsonResponse
    {
        return response()->json(FeaturedListing::getPricing());
    }

    /**
     * Get user's featured listings
     */
    public function index(Request $request): JsonResponse
    {
        $listings = FeaturedListing::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($listings);
    }

    /**
     * Get all active featured listings (public)
     */
    public function getActive(): JsonResponse
    {
        $listings = FeaturedListing::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>', now())
            ->with('user')
            ->get();

        // Enhance with actual product details
        $listings = $listings->map(function($listing) {
            $productClass = $listing->product_type;
            if (class_exists($productClass)) {
                $product = $productClass::with('user')->find($listing->product_id);
                if ($product) {
                    $listing->product_name = $product->name ?? $product->title ?? 'Unknown';
                    $listing->price = $product->price ?? $product->price_per_unit ?? 0;
                    $listing->user = $product->user; // Include product owner with creator_tier
                }
            }
            return $listing;
        });

        return response()->json($listings);
    }

    /**
     * Create a featured listing
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_type' => 'required|in:Item,Currency,Account,Service',
            'product_id' => 'required|integer',
            'duration' => 'required|in:7,14,30',
            'payment_method' => 'required|in:wallet,stripe',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $productType = 'App\\Models\\' . $request->product_type;
            $productId = $request->product_id;
            $duration = $request->duration;

            // Check if product exists and belongs to user
            $product = $productType::find($productId);
            if (!$product || $product->user_id !== $user->id) {
                return response()->json(['error' => 'Product not found or unauthorized'], 404);
            }

            // Check if product is already featured
            $existing = FeaturedListing::where('product_type', $productType)
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if ($existing) {
                return response()->json(['error' => 'Product is already featured'], 400);
            }

            // Get pricing
            $pricing = FeaturedListing::getPricing();
            $price = $pricing[$duration];

            // Handle payment
            if ($request->payment_method === 'wallet') {
                $wallet = $user->getOrCreateWallet();
                if ($wallet->balance < $price) {
                    return response()->json(['error' => 'Insufficient wallet balance'], 400);
                }

                $wallet->purchase($price, null, "Featured listing for {$duration} days");

                // Create featured listing
                $listing = FeaturedListing::create([
                    'user_id' => $user->id,
                    'product_type' => $productType,
                    'product_id' => $productId,
                    'price' => $price,
                    'starts_at' => now(),
                    'expires_at' => now()->addDays($duration),
                    'is_active' => true,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Featured listing created successfully',
                    'listing' => $listing,
                ], 201);

            } elseif ($request->payment_method === 'stripe') {
                // Create listing first to get ID
                $listing = FeaturedListing::create([
                    'user_id' => $user->id,
                    'product_type' => $productType,
                    'product_id' => $productId,
                    'price' => $price,
                    'starts_at' => now(),
                    'expires_at' => now()->addDays($duration),
                    'is_active' => false, // Will be activated by webhook
                ]);

                // Create payment intent with metadata
                $stripeService = new StripePaymentService();
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => (int)($price * 100),
                    'currency' => 'usd',
                    'customer' => $stripeService->getOrCreateCustomer($user),
                    'metadata' => [
                        'user_id' => $user->id,
                        'type' => 'featured_listing',
                        'listing_id' => $listing->id,
                        'duration' => $duration,
                    ],
                    'automatic_payment_methods' => [
                        'enabled' => true,
                    ],
                ]);

                // Update listing with payment intent ID
                $listing->update(['stripe_payment_intent_id' => $paymentIntent->id]);

                DB::commit();

                return response()->json([
                    'message' => 'Featured listing created, payment required',
                    'listing' => $listing,
                    'requires_payment' => true,
                    'payment_intent_id' => $paymentIntent->id,
                    'payment_intent_client_secret' => $paymentIntent->client_secret,
                ], 201);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create featured listing: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel a featured listing
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $listing = FeaturedListing::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$listing) {
            return response()->json(['error' => 'Featured listing not found'], 404);
        }

        $listing->update(['is_active' => false]);

        return response()->json([
            'message' => 'Featured listing cancelled successfully',
        ]);
    }
}
