<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_number',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'prize_pool',
        'features',
        'pass_revenue',
        'rewards_paid',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'prize_pool' => 'decimal:2',
        'pass_revenue' => 'decimal:2',
        'rewards_paid' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the current active season
     */
    public static function current()
    {
        return self::where('status', 'active')->first();
    }

    /**
     * Get season participants
     */
    public function participants()
    {
        return $this->hasMany(UserSeasonParticipation::class);
    }

    /**
     * Get season achievements
     */
    public function achievements()
    {
        return $this->hasMany(Achievement::class);
    }

    /**
     * Get season passes
     */
    public function seasonPasses()
    {
        return $this->hasMany(UserSeasonPass::class);
    }

    /**
     * Get season rewards
     */
    public function rewards()
    {
        return $this->hasMany(UserReward::class);
    }

    /**
     * Get season statistics
     */
    public function getStats()
    {
        $participants = $this->participants;
        $totalPrizesAwarded = $participants->sum('total_earned');
        $topEarner = $participants->orderBy('total_earned', 'desc')->first();

        return [
            'total_participants' => $participants->count(),
            'total_prizes_awarded' => $totalPrizesAwarded,
            'achievements_unlocked' => $participants->sum('achievements_unlocked'),
            'top_earner' => $topEarner ? [
                'name' => $topEarner->user->name,
                'total_earned' => $topEarner->total_earned,
            ] : null,
        ];
    }

    /**
     * Get season leaderboard
     */
    public function getLeaderboard()
    {
        return $this->participants()
            ->with('user:id,name')
            ->orderBy('rank')
            ->get()
            ->map(function ($participation) {
                return [
                    'rank' => $participation->rank,
                    'user_id' => $participation->user_id,
                    'user_name' => $participation->user->name,
                    'sales' => $participation->total_sales,
                    'reward' => $participation->total_earned,
                ];
            });
    }

    /**
     * Check if season is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if season has ended
     */
    public function hasEnded(): bool
    {
        return $this->status === 'ended';
    }

    /**
     * Get days remaining in season
     */
    public function daysRemaining(): int
    {
        if ($this->hasEnded()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    /**
     * Calculate current rewards pool (15% of pass revenue)
     */
    public function calculateRewardsPool(): float
    {
        $rewardsPercentage = config('achievements.rewards_pool_percentage', 0.15);
        return round($this->pass_revenue * $rewardsPercentage, 2);
    }

    /**
     * Get remaining rewards pool
     */
    public function getRemainingRewardsPool(): float
    {
        $totalPool = $this->calculateRewardsPool();
        return max(0, $totalPool - $this->rewards_paid);
    }

    /**
     * Check if season has met minimum requirements for cash rewards
     */
    public function canPayCashRewards(): bool
    {
        $minimums = config('achievements.minimums', []);
        $minPool = $minimums['season_pool'] ?? 100.00;
        $minSales = $minimums['pass_sales_required'] ?? 20;

        $totalPool = $this->calculateRewardsPool();
        $passSales = $this->seasonPasses()->count();

        return $totalPool >= $minPool && $passSales >= $minSales;
    }

    /**
     * Record pass purchase
     */
    public function recordPassPurchase(float $amount): void
    {
        $this->increment('pass_revenue', $amount);

        // Update prize pool (total revenue tracked)
        $this->increment('prize_pool', $amount);
    }

    /**
     * Record reward payment
     */
    public function recordRewardPayment(float $amount): void
    {
        $this->increment('rewards_paid', $amount);
    }

    /**
     * Get pass tier pricing
     */
    public function getPassPricing(): array
    {
        return config('achievements.pass_prices', [
            'free' => 0.00,
            'basic' => 4.99,
            'premium' => 9.99,
            'elite' => 19.99,
        ]);
    }

    /**
     * Calculate dynamic reward for an achievement
     */
    public function calculateAchievementReward(Achievement $achievement, int $completionCount): float
    {
        if (!$this->canPayCashRewards()) {
            return 0.0;
        }

        $totalPool = $this->calculateRewardsPool();
        $percentages = config('achievements.base_reward_percentages', []);
        $tierPercentage = $percentages[$achievement->tier] ?? 0.02;

        // Calculate base reward: (pool * tier percentage) / number of completions
        $baseReward = ($totalPool * $tierPercentage) / max(1, $completionCount);

        // Apply minimum threshold
        $minReward = config('achievements.minimums.cash_reward', 0.10);
        if ($baseReward > 0 && $baseReward < $minReward) {
            return $minReward;
        }

        return round($baseReward, 2);
    }
}
