<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserSeasonPass extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'season_id',
        'pass_tier',
        'price_paid',
        'stripe_payment_intent_id',
        'purchased_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the season pass
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the season this pass is for
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Check if the pass is currently active
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if expired
        if ($this->expires_at && Carbon::now()->isAfter($this->expires_at)) {
            return false;
        }

        // Check if season is active
        if ($this->season && !$this->season->is_active) {
            return false;
        }

        return true;
    }

    /**
     * Get the reward multiplier for this pass tier
     */
    public function getRewardMultiplier(): float
    {
        $multipliers = config('achievements.reward_multipliers');
        return $multipliers[$this->pass_tier] ?? 0.0;
    }

    /**
     * Check if this pass tier can claim cash rewards
     */
    public function canClaimCashRewards(): bool
    {
        return $this->pass_tier !== 'free' && $this->isActive();
    }

    /**
     * Check if user can claim a specific achievement
     */
    public function canClaimAchievement($achievement): bool
    {
        // Check if pass is active
        if (!$this->isActive()) {
            return false;
        }

        // If achievement requires a pass tier
        if ($achievement->required_pass_tier) {
            $tierHierarchy = ['free', 'basic', 'premium', 'elite'];
            $userTierIndex = array_search($this->pass_tier, $tierHierarchy);
            $requiredTierIndex = array_search($achievement->required_pass_tier, $tierHierarchy);

            if ($userTierIndex < $requiredTierIndex) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate the cash reward for an achievement
     */
    public function calculateReward(float $baseReward): float
    {
        if (!$this->canClaimCashRewards()) {
            return 0.0;
        }

        $multiplier = $this->getRewardMultiplier();
        $reward = $baseReward * $multiplier;

        // Apply minimum reward threshold
        $minReward = config('achievements.minimums.cash_reward', 0.10);
        if ($reward > 0 && $reward < $minReward) {
            return $minReward;
        }

        return round($reward, 2);
    }

    /**
     * Check if the pass is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return Carbon::now()->isAfter($this->expires_at);
    }

    /**
     * Get pass tier display name
     */
    public function getTierDisplayName(): string
    {
        return ucfirst($this->pass_tier);
    }

    /**
     * Scope to get active passes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            });
    }

    /**
     * Scope to get passes for a specific season
     */
    public function scopeForSeason($query, $seasonId)
    {
        return $query->where('season_id', $seasonId);
    }

    /**
     * Scope to get passes of a specific tier
     */
    public function scopeOfTier($query, string $tier)
    {
        return $query->where('pass_tier', $tier);
    }
}
