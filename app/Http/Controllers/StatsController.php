<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Item;
use App\Models\Currency;
use App\Models\Account;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function getPlatformStats(): JsonResponse
    {
        // Get total active users (users who have logged in or made purchases)
        $totalUsers = User::count();

        // Get total completed orders
        $completedOrders = Order::where('payment_status', 'completed')->count();

        // Get total products available
        $totalProducts = Item::count() + Currency::count() + Account::count() + Service::count();

        // Get total value of completed orders
        $totalVolume = Order::where('payment_status', 'completed')->sum('total');

        return response()->json([
            'users' => $totalUsers,
            'orders' => $completedOrders,
            'products' => $totalProducts,
            'volume' => number_format($totalVolume, 2, '.', '')
        ]);
    }
}
