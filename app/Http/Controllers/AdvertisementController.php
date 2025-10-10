<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdvertisementController extends Controller
{
    /**
     * Get active advertisements for a specific placement
     */
    public function getActiveAds(Request $request): JsonResponse
    {
        $placement = $request->input('placement');

        $ads = Advertisement::active()
            ->when($placement, fn($q) => $q->placement($placement))
            ->orderBy('position')
            ->get();

        return response()->json([
            'success' => true,
            'advertisements' => $ads,
        ]);
    }

    /**
     * Get advertisement pricing tiers
     */
    public function getPricing(): JsonResponse
    {
        $pricing = [
            [
                'placement' => 'homepage_top',
                'name' => 'Homepage Top Banner',
                'description' => 'Prime placement at the top of homepage - maximum visibility',
                'dimensions' => '728x90 or 970x90',
                'pricing' => [
                    ['duration' => 7, 'price' => 49.99],
                    ['duration' => 14, 'price' => 89.99],
                    ['duration' => 30, 'price' => 149.99],
                ],
            ],
            [
                'placement' => 'marketplace_top',
                'name' => 'Marketplace Top Banner',
                'description' => 'Featured banner on marketplace - target active buyers',
                'dimensions' => '728x90',
                'pricing' => [
                    ['duration' => 7, 'price' => 39.99],
                    ['duration' => 14, 'price' => 69.99],
                    ['duration' => 30, 'price' => 119.99],
                ],
            ],
            [
                'placement' => 'homepage_sidebar',
                'name' => 'Homepage Sidebar',
                'description' => 'Sidebar placement on homepage',
                'dimensions' => '300x250',
                'pricing' => [
                    ['duration' => 7, 'price' => 29.99],
                    ['duration' => 14, 'price' => 49.99],
                    ['duration' => 30, 'price' => 79.99],
                ],
            ],
            [
                'placement' => 'marketplace_sidebar',
                'name' => 'Marketplace Sidebar',
                'description' => 'Sidebar placement on marketplace pages',
                'dimensions' => '300x250',
                'pricing' => [
                    ['duration' => 7, 'price' => 24.99],
                    ['duration' => 14, 'price' => 44.99],
                    ['duration' => 30, 'price' => 69.99],
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'pricing' => $pricing,
        ]);
    }

    /**
     * Get user's advertisements
     */
    public function getUserAds(): JsonResponse
    {
        $user = Auth::user();

        $ads = Advertisement::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'advertisements' => $ads,
        ]);
    }

    /**
     * Create a new advertisement
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link_url' => 'required|url',
            'image' => 'required|image|max:2048',
            'placement' => 'required|in:homepage_top,homepage_sidebar,marketplace_top,marketplace_sidebar,game_page_top',
            'duration' => 'required|integer|in:7,14,30',
            'payment_method' => 'required|in:stripe,wallet',
        ]);

        // Upload image
        $imagePath = $request->file('image')->store('advertisements', 'public');
        $imageUrl = Storage::url($imagePath);

        // Calculate price based on placement and duration
        $price = $this->calculatePrice($validated['placement'], $validated['duration']);

        // Check wallet balance if paying with wallet
        if ($validated['payment_method'] === 'wallet') {
            $wallet = $user->wallet;
            if (!$wallet || $wallet->balance < $price) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance',
                ], 400);
            }
        }

        // Create advertisement
        $ad = Advertisement::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'image_url' => $imageUrl,
            'link_url' => $validated['link_url'],
            'placement' => $validated['placement'],
            'ad_type' => 'Banner',
            'start_date' => now(),
            'end_date' => now()->addDays($validated['duration']),
            'payment_amount' => $price,
            'payment_status' => 'Pending',
            'is_active' => false, // Activate after payment
        ]);

        // Process payment
        if ($validated['payment_method'] === 'wallet') {
            $wallet->withdraw($price, "Advertisement purchase - {$ad->title}");
            $ad->update([
                'payment_status' => 'Completed',
                'is_active' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Advertisement created successfully',
            'advertisement' => $ad,
        ]);
    }

    /**
     * Record an impression
     */
    public function recordImpression(Request $request, $id): JsonResponse
    {
        $ad = Advertisement::findOrFail($id);
        $ad->recordImpression();

        return response()->json(['success' => true]);
    }

    /**
     * Record a click
     */
    public function recordClick(Request $request, $id): JsonResponse
    {
        $ad = Advertisement::findOrFail($id);
        $ad->recordClick();

        return response()->json(['success' => true]);
    }

    /**
     * Update advertisement
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $ad = Advertisement::findOrFail($id);

        if ($ad->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'link_url' => 'sometimes|url',
            'is_active' => 'sometimes|boolean',
        ]);

        $ad->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement updated successfully',
            'advertisement' => $ad,
        ]);
    }

    /**
     * Delete advertisement
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        $ad = Advertisement::findOrFail($id);

        if ($ad->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Delete image from storage
        if ($ad->image_url) {
            $path = str_replace('/storage/', '', $ad->image_url);
            Storage::disk('public')->delete($path);
        }

        $ad->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advertisement deleted successfully',
        ]);
    }

    /**
     * Calculate price based on placement and duration
     */
    private function calculatePrice(string $placement, int $duration): float
    {
        $basePrices = [
            'homepage_top' => ['7' => 49.99, '14' => 89.99, '30' => 149.99],
            'marketplace_top' => ['7' => 39.99, '14' => 69.99, '30' => 119.99],
            'homepage_sidebar' => ['7' => 29.99, '14' => 49.99, '30' => 79.99],
            'marketplace_sidebar' => ['7' => 24.99, '14' => 44.99, '30' => 69.99],
            'game_page_top' => ['7' => 34.99, '14' => 59.99, '30' => 99.99],
        ];

        return $basePrices[$placement][(string)$duration] ?? 0;
    }
}
