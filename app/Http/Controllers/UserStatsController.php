<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserStatsController extends Controller
{
    public function getUserStats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Get total orders
        $totalOrders = Order::where('user_id', $userId)->count();

        // Get total spent
        $totalSpent = Order::where('user_id', $userId)
            ->where('payment_status', 'completed')
            ->sum('total');

        // Get total reviews given
        $totalReviews = Review::where('user_id', $userId)->count();

        // Get recent activity
        $recentOrders = Order::where('user_id', $userId)
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentActivity = [];

        foreach ($recentOrders as $order) {
            // Add order completion activity
            if ($order->status === 'completed') {
                $recentActivity[] = [
                    'id' => 'order-' . $order->id,
                    'icon' => '📦',
                    'title' => 'Order #' . $order->order_number . ' completed',
                    'date' => $order->updated_at->diffForHumans(),
                    'amount' => $order->total
                ];
            } elseif ($order->status === 'pending') {
                $recentActivity[] = [
                    'id' => 'order-' . $order->id,
                    'icon' => '🛒',
                    'title' => 'Placed Order #' . $order->order_number,
                    'date' => $order->created_at->diffForHumans(),
                    'amount' => $order->total
                ];
            }
        }

        // Get recent reviews
        $recentReviews = Review::where('user_id', $userId)
            ->with(['orderItem'])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentReviews as $review) {
            $productName = $review->orderItem ? $review->orderItem->product_name : 'a product';
            $recentActivity[] = [
                'id' => 'review-' . $review->id,
                'icon' => '⭐',
                'title' => 'Left a review for ' . $productName,
                'date' => $review->created_at->diffForHumans()
            ];
        }

        // Sort activity by date
        usort($recentActivity, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        // Limit to 5 most recent activities
        $recentActivity = array_slice($recentActivity, 0, 5);

        return response()->json([
            'stats' => [
                'orders' => $totalOrders,
                'spent' => number_format($totalSpent, 2, '.', ''),
                'reviews' => $totalReviews
            ],
            'recent_activity' => $recentActivity
        ]);
    }
}
