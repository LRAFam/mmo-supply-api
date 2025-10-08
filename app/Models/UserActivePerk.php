<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserActivePerk extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_item_id',
        'perk_type',
        'perk_data',
        'activated_at',
        'expires_at',
        'is_active',
        'uses_remaining',
    ];

    protected $casts = [
        'perk_data' => 'array',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'uses_remaining' => 'integer',
    ];

    /**
     * Get the user that owns this perk
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the store item this perk came from
     */
    public function storeItem(): BelongsTo
    {
        return $this->belongsTo(AchievementStoreItem::class, 'store_item_id');
    }

    /**
     * Check if perk is still active
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check expiration
        if ($this->expires_at && Carbon::now()->isAfter($this->expires_at)) {
            $this->deactivate();
            return false;
        }

        // Check uses remaining
        if ($this->uses_remaining !== null && $this->uses_remaining <= 0) {
            $this->deactivate();
            return false;
        }

        return true;
    }

    /**
     * Deactivate this perk
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Use this perk (decrement uses_remaining)
     */
    public function use(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->uses_remaining !== null) {
            $this->decrement('uses_remaining');

            if ($this->uses_remaining <= 0) {
                $this->deactivate();
            }
        }

        return true;
    }

    /**
     * Get perk value (e.g., discount percentage, boost amount)
     */
    public function getValue(): mixed
    {
        return $this->perk_data['value'] ?? null;
    }

    /**
     * Get time remaining before expiration
     */
    public function getTimeRemaining(): ?string
    {
        if (!$this->expires_at) {
            return null;
        }

        $now = Carbon::now();
        if ($now->isAfter($this->expires_at)) {
            return 'Expired';
        }

        $diff = $now->diff($this->expires_at);

        if ($diff->days > 0) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '');
        }

        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
        }

        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    }

    /**
     * Scope to get active perks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('uses_remaining')
                    ->orWhere('uses_remaining', '>', 0);
            });
    }

    /**
     * Scope to get perks by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('perk_type', $type);
    }

    /**
     * Scope to get expired perks
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }
}
