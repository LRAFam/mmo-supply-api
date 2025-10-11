<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    /**
     * Get all achievements with user progress
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $achievements = Achievement::with('nextTier')->where('is_active', true)
            ->orderBy('category')
            ->orderBy('achievement_group')
            ->orderBy('level')
            ->get()
            ->map(function ($achievement) use ($user) {
                $data = [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'slug' => $achievement->slug,
                    'achievement_group' => $achievement->achievement_group,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'category' => $achievement->category,
                    'tier' => $achievement->tier,
                    'level' => $achievement->level,
                    'points' => $achievement->points,
                    'wallet_reward' => $achievement->wallet_reward,
                    'is_secret' => $achievement->is_secret,
                    'unlocked' => false,
                    'progress' => 0,
                    'reward_claimed' => false,
                    'reward_claimed_at' => null,
                    'next_tier' => null,
                ];

                if ($user) {
                    $isUnlocked = $achievement->isUnlockedBy($user);
                    $progress = $achievement->getProgressFor($user);

                    $data['unlocked'] = $isUnlocked;
                    $data['progress'] = $progress['progress'] ?? 0;

                    // Only show secret achievements if unlocked
                    if ($achievement->is_secret && !$isUnlocked) {
                        $data['name'] = '???';
                        $data['description'] = 'Secret achievement - unlock to reveal';
                        $data['icon'] = '❓';
                    }

                    if ($isUnlocked) {
                        $userAchievement = $achievement->users()
                            ->where('user_id', $user->id)
                            ->first();

                        if ($userAchievement) {
                            $data['unlocked_at'] = $userAchievement->pivot->unlocked_at;
                            $data['reward_claimed'] = (bool) $userAchievement->pivot->reward_claimed;
                            $data['reward_claimed_at'] = $userAchievement->pivot->reward_claimed_at;
                        }
                    }

                    // Include next tier information if exists
                    if ($achievement->nextTier) {
                        $nextProgress = $achievement->nextTier->getProgressFor($user);
                        $data['next_tier'] = [
                            'id' => $achievement->nextTier->id,
                            'name' => $achievement->nextTier->name,
                            'tier' => $achievement->nextTier->tier,
                            'level' => $achievement->nextTier->level,
                            'points' => $achievement->nextTier->points,
                            'wallet_reward' => $achievement->nextTier->wallet_reward,
                            'requirements' => $achievement->nextTier->requirements,
                            'progress' => $nextProgress['progress'] ?? 0,
                        ];
                    }
                }

                return $data;
            });

        return response()->json([
            'achievements' => $achievements,
            'categories' => ['buyer', 'seller', 'social', 'special'],
            'tiers' => ['copper', 'bronze', 'silver', 'gold', 'emerald', 'sapphire', 'ruby', 'diamond', 'master', 'grandmaster'],
        ]);
    }

    /**
     * Get achievements by category
     */
    public function byCategory(Request $request, string $category): JsonResponse
    {
        $user = $request->user();

        $achievements = Achievement::where('category', $category)
            ->where('is_active', true)
            ->orderBy('level')
            ->get()
            ->map(function ($achievement) use ($user) {
                $data = [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'tier' => $achievement->tier,
                    'level' => $achievement->level,
                    'points' => $achievement->points,
                    'wallet_reward' => $achievement->wallet_reward,
                    'unlocked' => false,
                ];

                if ($user) {
                    $data['unlocked'] = $achievement->isUnlockedBy($user);
                    $data['progress'] = $achievement->getProgressFor($user);
                }

                return $data;
            });

        return response()->json(['achievements' => $achievements]);
    }

    /**
     * Get user's unlocked achievements
     */
    public function userAchievements(Request $request): JsonResponse
    {
        $user = $request->user();

        $unlockedAchievements = $user->achievements()
            ->orderBy('user_achievements.unlocked_at', 'desc')
            ->get()
            ->map(function ($achievement) {
                return [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'category' => $achievement->category,
                    'tier' => $achievement->tier,
                    'level' => $achievement->level,
                    'points' => $achievement->points,
                    'wallet_reward' => $achievement->wallet_reward,
                    'unlocked_at' => $achievement->pivot->unlocked_at,
                ];
            });

        $totalPoints = $unlockedAchievements->sum('points');
        $totalWalletRewards = $unlockedAchievements->sum('wallet_reward');

        return response()->json([
            'achievements' => $unlockedAchievements,
            'stats' => [
                'total_unlocked' => $unlockedAchievements->count(),
                'total_points' => $totalPoints,
                'total_wallet_rewards' => $totalWalletRewards,
            ],
        ]);
    }

    /**
     * Get user's achievement statistics
     */
    public function userStats(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalAchievements = Achievement::where('is_active', true)->count();
        $unlockedCount = $user->achievements()->count();

        // Get unlocked by category using a raw query to avoid GROUP BY issues
        $unlockedByCategory = \DB::table('achievements')
            ->join('user_achievements', 'achievements.id', '=', 'user_achievements.achievement_id')
            ->where('user_achievements.user_id', $user->id)
            ->select('category', \DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get()
            ->pluck('count', 'category');

        // Get unlocked by tier using a raw query
        $unlockedByTier = \DB::table('achievements')
            ->join('user_achievements', 'achievements.id', '=', 'user_achievements.achievement_id')
            ->where('user_achievements.user_id', $user->id)
            ->select('tier', \DB::raw('count(*) as count'))
            ->groupBy('tier')
            ->get()
            ->pluck('count', 'tier');

        // Use the user's actual spendable achievement_points instead of sum
        $spendablePoints = $user->achievement_points;

        // Calculate total earned points (including spent)
        $totalEarnedPoints = $user->achievements()->sum('points');

        // Calculate potential wallet rewards
        $totalWalletRewards = $user->achievements()->sum('wallet_reward');

        return response()->json([
            'total_achievements' => $totalAchievements,
            'unlocked_count' => $unlockedCount,
            'completion_percentage' => $totalAchievements > 0
                ? round(($unlockedCount / $totalAchievements) * 100, 2)
                : 0,
            'unlocked_by_category' => $unlockedByCategory,
            'unlocked_by_tier' => $unlockedByTier,
            'spendable_points' => $spendablePoints, // Current spendable balance
            'total_earned_points' => $totalEarnedPoints, // Lifetime earned (including spent)
            'total_wallet_rewards' => $totalWalletRewards,
        ]);
    }

    /**
     * Check for new unlockable achievements
     */
    public function checkUnlockable(Request $request): JsonResponse
    {
        $user = $request->user();
        $newlyUnlocked = [];
        $notificationService = app(NotificationService::class);

        $achievements = Achievement::where('is_active', true)->get();

        foreach ($achievements as $achievement) {
            if ($achievement->checkAndUnlock($user)) {
                $newlyUnlocked[] = [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'tier' => $achievement->tier,
                    'level' => $achievement->level,
                    'points' => $achievement->points,
                    'wallet_reward' => $achievement->wallet_reward,
                ];

                // Send notification for achievement unlock
                $notificationService->achievementUnlocked(
                    userId: $user->id,
                    achievementName: $achievement->name,
                    points: $achievement->points
                );
            }
        }

        return response()->json([
            'newly_unlocked' => $newlyUnlocked,
            'count' => count($newlyUnlocked),
        ]);
    }

    /**
     * Get recent achievements (leaderboard style)
     */
    public function recent(): JsonResponse
    {
        $recentUnlocks = \DB::table('user_achievements')
            ->join('users', 'user_achievements.user_id', '=', 'users.id')
            ->join('achievements', 'user_achievements.achievement_id', '=', 'achievements.id')
            ->select(
                'users.name as user_name',
                'achievements.name as achievement_name',
                'achievements.icon',
                'achievements.tier',
                'user_achievements.unlocked_at'
            )
            ->orderBy('user_achievements.unlocked_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json(['recent_unlocks' => $recentUnlocks]);
    }

    /**
     * Claim reward for an unlocked achievement
     */
    public function claimReward(Request $request, int $achievementId): JsonResponse
    {
        $user = $request->user();
        $achievement = Achievement::find($achievementId);

        if (!$achievement) {
            return response()->json([
                'message' => 'Achievement not found',
            ], 404);
        }

        if (!$achievement->isUnlockedBy($user)) {
            return response()->json([
                'message' => 'Achievement not unlocked',
            ], 403);
        }

        $claimed = $achievement->claimReward($user);

        if (!$claimed) {
            return response()->json([
                'message' => 'Reward already claimed',
            ], 400);
        }

        // Refresh user and wallet to get updated balance
        $user->refresh();
        $wallet = $user->wallet()->first();

        return response()->json([
            'message' => 'Reward claimed successfully',
            'reward' => [
                'points' => $achievement->points,
                'wallet_reward' => $achievement->wallet_reward,
            ],
            'new_wallet_balance' => $wallet ? $wallet->balance : $user->wallet_balance,
            'achievement_points' => $user->achievement_points,
        ]);
    }

    /**
     * Bulk claim all unclaimed achievement rewards
     * Useful for users who have unlocked achievements before auto-claim was implemented
     */
    public function claimAll(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all unclaimed achievements
        $unclaimedAchievements = $user->achievements()
            ->wherePivot('reward_claimed', false)
            ->get();

        if ($unclaimedAchievements->isEmpty()) {
            return response()->json([
                'message' => 'No unclaimed rewards found',
                'claimed_count' => 0,
            ]);
        }

        $claimedCount = 0;
        $totalPoints = 0;
        $totalWalletReward = 0;

        foreach ($unclaimedAchievements as $achievement) {
            $claimed = $achievement->claimReward($user);
            if ($claimed) {
                $claimedCount++;
                $totalPoints += $achievement->points;
                $totalWalletReward += $achievement->wallet_reward;
            }
        }

        // Refresh user to get updated balances
        $user->refresh();

        return response()->json([
            'message' => "Successfully claimed {$claimedCount} achievement rewards",
            'claimed_count' => $claimedCount,
            'total_points_earned' => $totalPoints,
            'total_wallet_rewards' => $totalWalletReward,
            'new_achievement_points' => $user->achievement_points,
            'new_wallet_balance' => $user->wallet_balance,
        ]);
    }
}
