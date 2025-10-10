<?php

namespace App\Services;

use App\Models\User;
use App\Models\Achievement;
use Illuminate\Support\Facades\Log;

class AchievementCheckService
{
    /**
     * Check all achievements for a user
     */
    public function checkAllAchievements(User $user): array
    {
        $newlyUnlocked = [];

        try {
            $achievements = Achievement::where('is_active', true)->get();

            foreach ($achievements as $achievement) {
                if ($achievement->checkAndUnlock($user)) {
                    $newlyUnlocked[] = [
                        'id' => $achievement->id,
                        'name' => $achievement->name,
                        'points' => $achievement->points,
                        'wallet_reward' => $achievement->wallet_reward,
                    ];

                    Log::info("🏆 Achievement Unlocked: {$achievement->name} for user {$user->id}");
                }
            }

            return $newlyUnlocked;
        } catch (\Exception $e) {
            Log::error('Achievement check failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check specific category of achievements
     */
    public function checkCategoryAchievements(User $user, string $category): array
    {
        $newlyUnlocked = [];

        try {
            $achievements = Achievement::where('category', $category)
                ->where('is_active', true)
                ->get();

            foreach ($achievements as $achievement) {
                if ($achievement->checkAndUnlock($user)) {
                    $newlyUnlocked[] = [
                        'id' => $achievement->id,
                        'name' => $achievement->name,
                        'points' => $achievement->points,
                        'wallet_reward' => $achievement->wallet_reward,
                    ];

                    Log::info("🏆 Achievement Unlocked: {$achievement->name} for user {$user->id}");
                }
            }

            return $newlyUnlocked;
        } catch (\Exception $e) {
            Log::error('Achievement check failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check and auto-claim achievements with rewards
     */
    public function checkAndAutoClaimAchievements(User $user): array
    {
        $results = [];

        try {
            $achievements = Achievement::where('is_active', true)->get();

            foreach ($achievements as $achievement) {
                // Check if can unlock
                if ($achievement->checkAndUnlock($user)) {
                    // Auto-claim the reward immediately
                    $claimed = $achievement->claimReward($user);

                    $results[] = [
                        'id' => $achievement->id,
                        'name' => $achievement->name,
                        'points' => $achievement->points,
                        'wallet_reward' => $achievement->wallet_reward,
                        'auto_claimed' => $claimed,
                    ];

                    Log::info("🏆 Achievement Unlocked & Claimed: {$achievement->name} for user {$user->id}");
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Achievement check and claim failed: ' . $e->getMessage());
            return [];
        }
    }
}
