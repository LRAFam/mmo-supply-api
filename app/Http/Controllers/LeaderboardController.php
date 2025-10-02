<?php

namespace App\Http\Controllers;

use App\Models\LeaderboardReward;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    /**
     * Get current leaderboard rankings
     */
    public function index(Request $request): JsonResponse
    {
        $period = $request->input('period', 'monthly'); // 'weekly' or 'monthly'
        $limit = $request->input('limit', 50);

        // Get date ranges
        $dates = $this->getPeriodDates($period);

        // Get top sellers by sales in the current period
        $leaderboard = User::where('is_seller', true)
            ->where('monthly_sales', '>', 0)
            ->select('id', 'name', 'monthly_sales', 'lifetime_sales', 'auto_tier')
            ->orderBy('monthly_sales', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($user, $index) use ($period, $dates) {
                $rank = $index + 1;
                $reward = $this->getRewardForRank($rank, $period);

                return [
                    'rank' => $rank,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'sales' => floatval($user->monthly_sales),
                    'lifetime_sales' => floatval($user->lifetime_sales),
                    'tier' => $user->auto_tier ?? 'standard',
                    'potential_reward' => $reward['amount'],
                    'badge' => $reward['badge'],
                ];
            });

        $now = now();
        $endDate = $dates['end'];
        $daysRemaining = $now->startOfDay()->diffInDays($endDate->startOfDay(), false);

        return response()->json([
            'success' => true,
            'period' => $period,
            'period_start' => $dates['start']->toDateString(),
            'period_end' => $dates['end']->toDateString(),
            'days_remaining' => max(0, (int) $daysRemaining),
            'leaderboard' => $leaderboard,
            'reward_structure' => $this->getRewardStructure($period),
        ]);
    }

    /**
     * Get user's leaderboard history
     */
    public function userHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        $history = LeaderboardReward::where('user_id', $user->id)
            ->orderBy('period_start', 'desc')
            ->limit(12)
            ->get()
            ->map(function ($reward) {
                return [
                    'period' => $reward->period,
                    'period_start' => $reward->period_start->toDateString(),
                    'period_end' => $reward->period_end->toDateString(),
                    'rank' => $reward->rank,
                    'sales' => floatval($reward->sales_amount),
                    'reward' => floatval($reward->reward_amount),
                    'badge' => $reward->badge,
                    'credited' => $reward->credited,
                ];
            });

        // Get current ranking
        $currentRank = $this->getCurrentUserRank($user->id);

        return response()->json([
            'success' => true,
            'current_rank' => $currentRank,
            'current_sales' => floatval($user->monthly_sales),
            'history' => $history,
            'total_rewards_earned' => LeaderboardReward::where('user_id', $user->id)
                ->where('credited', true)
                ->sum('reward_amount'),
        ]);
    }

    /**
     * Get period date ranges
     */
    private function getPeriodDates(string $period): array
    {
        if ($period === 'weekly') {
            return [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ];
        }

        // Monthly
        return [
            'start' => now()->startOfMonth(),
            'end' => now()->endOfMonth(),
        ];
    }

    /**
     * Get reward amount and badge for a given rank
     */
    private function getRewardForRank(int $rank, string $period): array
    {
        $rewards = $this->getRewardStructure($period);

        foreach ($rewards as $reward) {
            if ($rank >= $reward['rank_start'] && $rank <= $reward['rank_end']) {
                return [
                    'amount' => $reward['amount'],
                    'badge' => $reward['badge'],
                ];
            }
        }

        return ['amount' => 0, 'badge' => null];
    }

    /**
     * Get reward structure for period
     */
    private function getRewardStructure(string $period): array
    {
        if ($period === 'weekly') {
            return [
                ['rank_start' => 1, 'rank_end' => 1, 'amount' => 5.00, 'badge' => 'gold', 'label' => '1st Place'],
                ['rank_start' => 2, 'rank_end' => 2, 'amount' => 2.50, 'badge' => 'silver', 'label' => '2nd Place'],
                ['rank_start' => 3, 'rank_end' => 3, 'amount' => 1.00, 'badge' => 'bronze', 'label' => '3rd Place'],
                ['rank_start' => 4, 'rank_end' => 10, 'amount' => 0.50, 'badge' => null, 'label' => 'Top 10'],
            ];
        }

        // Monthly rewards (bigger prizes)
        return [
            ['rank_start' => 1, 'rank_end' => 1, 'amount' => 25.00, 'badge' => 'gold', 'label' => '1st Place'],
            ['rank_start' => 2, 'rank_end' => 2, 'amount' => 15.00, 'badge' => 'silver', 'label' => '2nd Place'],
            ['rank_start' => 3, 'rank_end' => 3, 'amount' => 7.50, 'badge' => 'bronze', 'label' => '3rd Place'],
            ['rank_start' => 4, 'rank_end' => 5, 'amount' => 3.00, 'badge' => null, 'label' => 'Top 5'],
            ['rank_start' => 6, 'rank_end' => 10, 'amount' => 2.00, 'badge' => null, 'label' => 'Top 10'],
            ['rank_start' => 11, 'rank_end' => 20, 'amount' => 1.00, 'badge' => null, 'label' => 'Top 20'],
        ];
    }

    /**
     * Get current user's rank
     */
    private function getCurrentUserRank(int $userId): ?int
    {
        $user = User::find($userId);
        if (!$user || !$user->is_seller) {
            return null;
        }

        $rank = User::where('is_seller', true)
            ->where('monthly_sales', '>', $user->monthly_sales)
            ->count();

        return $rank + 1;
    }
}
