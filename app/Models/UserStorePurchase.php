<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStorePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_item_id',
        'points_spent',
        'purchased_at',
        'used_at',
        'times_used',
        'is_active',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'used_at' => 'datetime',
        'times_used' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that made this purchase
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the store item that was purchased
     */
    public function storeItem(): BelongsTo
    {
        return $this->belongsTo(AchievementStoreItem::class, 'store_item_id');
    }

    /**
     * Mark item as used
     */
    public function markAsUsed(): void
    {
        $this->increment('times_used');

        if ($this->times_used === 1) {
            $this->update(['used_at' => now()]);
        }
    }

    /**
     * Check if item has remaining uses
     */
    public function hasRemainingUses(): bool
    {
        $item = $this->storeItem;

        // If no max_uses limit, always has uses
        if (!$item || $item->max_uses === null) {
            return true;
        }

        return $this->times_used < $item->max_uses;
    }

    /**
     * Check if item is on cooldown
     */
    public function isOnCooldown(): bool
    {
        $item = $this->storeItem;

        if (!$item || !$item->cooldown_days || !$this->used_at) {
            return false;
        }

        $cooldownEnds = $this->used_at->addDays($item->cooldown_days);
        return now()->isBefore($cooldownEnds);
    }

    /**
     * Get cooldown end time
     */
    public function getCooldownEndsAt()
    {
        $item = $this->storeItem;

        if (!$item || !$item->cooldown_days || !$this->used_at) {
            return null;
        }

        return $this->used_at->addDays($item->cooldown_days);
    }

    /**
     * Scope to get purchases by user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get active purchases
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get recent purchases
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('purchased_at', '>=', now()->subDays($days));
    }
}
