<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'achievement_group',
        'description',
        'icon',
        'badge_icon',
        'category',
        'tier',
        'level',
        'next_tier_id',
        'points',
        'wallet_reward',
        'requirements',
        'is_active',
        'is_secret',
        'season_id',
        'is_seasonal',
        'grants_badge',
    ];

    protected $casts = [
        'requirements' => 'array',
        'is_active' => 'boolean',
        'is_secret' => 'boolean',
        'is_seasonal' => 'boolean',
        'grants_badge' => 'boolean',
        'wallet_reward' => 'decimal:2',
    ];

    /**
     * Users who have unlocked this achievement
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('unlocked_at', 'is_notified', 'progress_data', 'reward_claimed', 'reward_claimed_at')
            ->withTimestamps();
    }

    /**
     * Season this achievement belongs to
     */
    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Next tier achievement in the progression chain
     */
    public function nextTier()
    {
        return $this->belongsTo(Achievement::class, 'next_tier_id');
    }

    /**
     * Previous tier achievement in the progression chain
     */
    public function previousTier()
    {
        return $this->hasOne(Achievement::class, 'next_tier_id');
    }

    /**
     * Check if a user has unlocked this achievement
     */
    public function isUnlockedBy(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Unlock achievement for a user and automatically claim reward
     */
    public function unlockFor(User $user): bool
    {
        if ($this->isUnlockedBy($user)) {
            return false;
        }

        DB::beginTransaction();
        try {
            // Attach achievement to user
            $this->users()->attach($user->id, [
                'unlocked_at' => now(),
                'is_notified' => false,
                'reward_claimed' => true, // Auto-claim on unlock
                'reward_claimed_at' => now(),
            ]);

            // Automatically award rewards
            $this->awardRewards($user);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Award rewards to user (called automatically on unlock)
     */
    private function awardRewards(User $user): void
    {
        // Check if user has a valid season pass for cash rewards
        $seasonPass = null;
        if ($this->season_id) {
            $seasonPass = $user->seasonPasses()
                ->where('season_id', $this->season_id)
                ->where('is_active', true)
                ->first();
        }

        // Calculate cash reward based on season pass tier (only if they have a pass)
        $cashReward = 0;
        if ($this->wallet_reward > 0 && $seasonPass) {
            $cashReward = $seasonPass->calculateReward($this->wallet_reward);

            if ($cashReward > 0) {
                $user->increment('wallet_balance', $cashReward);

                // Also update the Wallet model balance
                $wallet = $user->wallet;
                if ($wallet) {
                    $wallet->increment('balance', $cashReward);

                    // Create transaction record
                    $wallet->transactions()->create([
                        'user_id' => $user->id,
                        'type' => 'achievement',
                        'amount' => $cashReward,
                        'status' => 'completed',
                        'description' => "Achievement reward: {$this->name}" .
                            ($seasonPass ? " ({$seasonPass->getTierDisplayName()} Pass)" : ''),
                    ]);
                }
            }
        }

        // Award achievement points (ALL users get points, even without a season pass)
        $pointsToAward = $this->points;
        if (!$pointsToAward && $this->tier) {
            // Fallback to config if not set on achievement
            $pointValues = config('achievements.point_values', []);
            $pointsToAward = $pointValues[$this->tier] ?? 0;
        }

        if ($pointsToAward > 0) {
            $user->increment('achievement_points', $pointsToAward);
        }

        // Grant badge if this is a badge-granting achievement
        if ($this->grants_badge && $this->badge_icon) {
            $user->addBadge($this->slug, $this->badge_icon, $this->name);
        }
    }

    /**
     * Claim reward for this achievement (for backwards compatibility with existing unclaimed achievements)
     */
    public function claimReward(User $user): bool
    {
        $userAchievement = $this->users()->where('user_id', $user->id)->first();

        if (!$userAchievement) {
            return false; // Achievement not unlocked
        }

        if ($userAchievement->pivot->reward_claimed) {
            return false; // Reward already claimed
        }

        DB::beginTransaction();
        try {
            // Mark reward as claimed
            $this->users()->updateExistingPivot($user->id, [
                'reward_claimed' => true,
                'reward_claimed_at' => now(),
            ]);

            // Award rewards using the same logic as auto-claim
            $this->awardRewards($user);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if user meets requirements for this achievement
     */
    public function checkRequirements(User $user): bool
    {
        if (!$this->requirements) {
            return true;
        }

        foreach ($this->requirements as $type => $value) {
            switch ($type) {
                case 'total_purchases':
                    $count = $user->orders()->where('payment_status', 'completed')->count();
                    if ($count < $value) return false;
                    break;

                case 'total_spent':
                    $spent = $user->orders()->where('payment_status', 'completed')->sum('total');
                    if ($spent < $value) return false;
                    break;

                case 'total_sales':
                    if (!$user->is_seller) return false;
                    $sales = $user->sellerOrders()->where('payment_status', 'completed')->count();
                    if ($sales < $value) return false;
                    break;

                case 'total_revenue':
                    if (!$user->is_seller) return false;
                    $revenue = $user->sellerOrders()->where('payment_status', 'completed')->sum('total');
                    if ($revenue < $value) return false;
                    break;

                case 'reviews_count':
                    $reviews = $user->reviews()->count();
                    if ($reviews < $value) return false;
                    break;

                case 'average_rating':
                    $avgRating = $user->receivedReviews()->avg('rating');
                    if (!$avgRating || $avgRating < $value) return false;
                    break;

                case 'messages_sent':
                    $messages = $user->sentMessages()->count();
                    if ($messages < $value) return false;
                    break;

                case 'is_seller':
                    if (!$user->is_seller) return false;
                    break;

                case 'account_age_days':
                    $accountAge = now()->diffInDays($user->created_at);
                    if ($accountAge < $value) return false;
                    break;

                case 'wishlist_items':
                    $wishlistCount = DB::table('wishlists')->where('user_id', $user->id)->count();
                    if ($wishlistCount < $value) return false;
                    break;

                case 'daily_spins_count':
                    $dailySpins = DB::table('spin_results')
                        ->join('spin_wheels', 'spin_results.spin_wheel_id', '=', 'spin_wheels.id')
                        ->where('spin_results.user_id', $user->id)
                        ->where('spin_wheels.type', 'free')
                        ->count();
                    if ($dailySpins < $value) return false;
                    break;

                case 'premium_spins_count':
                    $premiumSpins = DB::table('spin_results')
                        ->join('spin_wheels', 'spin_results.spin_wheel_id', '=', 'spin_wheels.id')
                        ->where('spin_results.user_id', $user->id)
                        ->where('spin_wheels.type', 'premium')
                        ->count();
                    if ($premiumSpins < $value) return false;
                    break;

                case 'login_streak_days':
                    $currentStreak = $user->login_streak ?? 0;
                    if ($currentStreak < $value) return false;
                    break;

                case 'total_spins':
                    $totalSpins = DB::table('spin_results')->where('user_id', $user->id)->count();
                    if ($totalSpins < $value) return false;
                    break;

                case 'consecutive_spin_wins':
                    // This would need more complex logic to track consecutive wins
                    // For now, return false to prevent auto-unlock
                    return false;

                case 'max_spin_win':
                    $maxWin = DB::table('spin_results')
                        ->where('user_id', $user->id)
                        ->max('prize_value') ?? 0;
                    if ($maxWin < $value) return false;
                    break;

                default:
                    // Unknown requirement type - fail safe by returning false
                    return false;
            }
        }

        return true;
    }

    /**
     * Check and unlock achievement for user if requirements are met
     */
    public function checkAndUnlock(User $user): bool
    {
        if (!$this->is_active || $this->isUnlockedBy($user)) {
            return false;
        }

        if ($this->checkRequirements($user)) {
            return $this->unlockFor($user);
        }

        return false;
    }

    /**
     * Get all achievements for a specific category
     */
    public static function getByCategory(string $category)
    {
        return self::where('category', $category)
            ->where('is_active', true)
            ->orderBy('level')
            ->get();
    }

    /**
     * Get user progress towards this achievement
     */
    public function getProgressFor(User $user): array
    {
        if ($this->isUnlockedBy($user)) {
            return ['unlocked' => true, 'progress' => 100];
        }

        if (!$this->requirements || !is_array($this->requirements)) {
            return ['unlocked' => false, 'progress' => 0];
        }

        $totalRequirements = count($this->requirements);
        $progressSum = 0;

        foreach ($this->requirements as $type => $value) {
            $current = 0;

            switch ($type) {
                case 'total_purchases':
                    $current = $user->orders()->where('payment_status', 'completed')->count();
                    break;
                case 'total_spent':
                    $current = $user->orders()->where('payment_status', 'completed')->sum('total');
                    break;
                case 'total_sales':
                    $current = $user->sellerOrders()->where('payment_status', 'completed')->count();
                    break;
                case 'total_revenue':
                    $current = $user->sellerOrders()->where('payment_status', 'completed')->sum('total');
                    break;
                case 'reviews_count':
                    $current = $user->reviews()->count();
                    break;
                case 'messages_sent':
                    $current = DB::table('messages')->where('sender_id', $user->id)->count();
                    break;
                case 'average_rating':
                    $current = $user->receivedReviews()->avg('rating') ?? 0;
                    break;
                case 'is_seller':
                    $current = $user->is_seller ? 100 : 0;
                    $value = 100;
                    break;
                case 'account_age_days':
                    $current = abs(now()->diffInDays($user->created_at));
                    break;
                case 'wishlist_items':
                    $current = DB::table('wishlists')->where('user_id', $user->id)->count();
                    break;
                case 'daily_spins_count':
                    $current = DB::table('spin_results')
                        ->join('spin_wheels', 'spin_results.spin_wheel_id', '=', 'spin_wheels.id')
                        ->where('spin_results.user_id', $user->id)
                        ->where('spin_wheels.type', 'free')
                        ->count();
                    break;
                case 'premium_spins_count':
                    $current = DB::table('spin_results')
                        ->join('spin_wheels', 'spin_results.spin_wheel_id', '=', 'spin_wheels.id')
                        ->where('spin_results.user_id', $user->id)
                        ->where('spin_wheels.type', 'premium')
                        ->count();
                    break;
                case 'login_streak_days':
                    $current = $user->login_streak ?? 0;
                    break;
                case 'total_spins':
                    $current = DB::table('spin_results')->where('user_id', $user->id)->count();
                    break;
                case 'max_spin_win':
                    $current = DB::table('spin_results')
                        ->where('user_id', $user->id)
                        ->max('prize_value') ?? 0;
                    break;
                case 'consecutive_spin_wins':
                    // Complex calculation - skip for now
                    $current = 0;
                    break;
                default:
                    // Unknown requirement type - skip and continue
                    continue 2;
            }

            // Calculate percentage for this requirement (cap at 100%, min at 0%)
            $requirementProgress = max(0, min(100, ($current / max(1, $value)) * 100));
            $progressSum += $requirementProgress;
        }

        // Average progress across all requirements
        $progress = $totalRequirements > 0 ? ($progressSum / $totalRequirements) : 0;

        return [
            'unlocked' => false,
            'progress' => max(0, round($progress, 2)),
        ];
    }
}
