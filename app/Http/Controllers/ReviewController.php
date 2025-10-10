<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\OrderItem;
use App\Services\AchievementCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_item_id' => 'required|exists:order_items,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Get the order item and verify it belongs to the user
        $orderItem = OrderItem::with('order')->findOrFail($validated['order_item_id']);

        if ($orderItem->order->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if order item is completed
        if ($orderItem->status !== 'completed') {
            return response()->json(['error' => 'Can only review completed orders'], 400);
        }

        // Check if already reviewed
        $existingReview = Review::where('user_id', $request->user()->id)
            ->where('order_item_id', $validated['order_item_id'])
            ->first();

        if ($existingReview) {
            return response()->json(['error' => 'You have already reviewed this item'], 400);
        }

        // Create review
        $review = Review::create([
            'user_id' => $request->user()->id,
            'order_item_id' => $validated['order_item_id'],
            'reviewable_type' => $orderItem->product_type,
            'reviewable_id' => $orderItem->product_id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_approved' => true, // Auto-approve for now
        ]);

        // Check for review-related achievements
        $achievementService = app(AchievementCheckService::class);
        $achievementService->checkAndAutoClaimAchievements($request->user());

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => $review->load('user')
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user', 'orderItem'])
            ->where('is_approved', true);

        // Filter by reviewable (product)
        if ($request->has('reviewable_type') && $request->has('reviewable_id')) {
            $query->where('reviewable_type', $request->reviewable_type)
                  ->where('reviewable_id', $request->reviewable_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $reviews = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($reviews);
    }

    public function show($id): JsonResponse
    {
        $review = Review::with(['user', 'orderItem'])
            ->findOrFail($id);

        return response()->json($review);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $review = Review::findOrFail($id);

        // Verify ownership
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update($validated);

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review->load('user')
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $review = Review::findOrFail($id);

        // Verify ownership
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }
}
