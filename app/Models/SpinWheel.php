<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpinWheel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'cost',
        'cooldown_hours',
        'is_active',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get all prizes for this wheel
     */
    public function prizes()
    {
        return $this->hasMany(WheelPrize::class);
    }

    /**
     * Get all spin results for this wheel
     */
    public function spinResults()
    {
        return $this->hasMany(SpinResult::class);
    }

    /**
     * Get user spin tracking for this wheel
     */
    public function userSpins()
    {
        return $this->hasMany(UserWheelSpin::class);
    }

    /**
     * Check if a user can spin this wheel
     */
    public function canUserSpin(User $user): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // For free spins, check cooldown
        if ($this->type === 'free') {
            $userSpin = $this->userSpins()->where('user_id', $user->id)->first();

            if (!$userSpin) {
                return true; // First spin
            }

            return now()->gte($userSpin->next_available_at);
        }

        // For premium spins, check subscription and remaining spins
        if ($this->type === 'premium') {
            $tier = $user->getSubscriptionTier();

            if (!in_array($tier, ['premium', 'elite'])) {
                return false; // No subscription
            }

            // Reset spins if it's a new week
            if (!$user->premium_spins_reset_at || now()->gte($user->premium_spins_reset_at)) {
                return true; // New week, spins will be allocated
            }

            return $user->premium_spins_remaining > 0;
        }

        return true;
    }

    /**
     * Get time until next available spin for user
     */
    public function getNextSpinTime(User $user): ?\Carbon\Carbon
    {
        if ($this->cost > 0) {
            return null; // No cooldown for paid spins
        }

        $userSpin = $this->userSpins()->where('user_id', $user->id)->first();

        if (!$userSpin) {
            return null; // Can spin now
        }

        return $userSpin->next_available_at;
    }
}
