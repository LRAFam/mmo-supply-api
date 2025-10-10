<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Advertisement extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'title',
        'description',
        'image_url',
        'link_url',
        'ad_type',
        'placement',
        'start_date',
        'end_date',
        'position',
        'payment_amount',
        'payment_status',
        'is_active',
        'impressions',
        'clicks',
        'ctr',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'payment_amount' => 'decimal:2',
        'ctr' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Scope for active advertisements
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('payment_status', 'Completed')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope for specific placement
     */
    public function scopePlacement(Builder $query, string $placement): Builder
    {
        return $query->where('placement', $placement);
    }

    /**
     * Record an impression
     */
    public function recordImpression(): void
    {
        $this->increment('impressions');
        $this->updateCTR();
    }

    /**
     * Record a click
     */
    public function recordClick(): void
    {
        $this->increment('clicks');
        $this->updateCTR();
    }

    /**
     * Update click-through rate
     */
    private function updateCTR(): void
    {
        if ($this->impressions > 0) {
            $this->update([
                'ctr' => ($this->clicks / $this->impressions) * 100
            ]);
        }
    }

    /**
     * Check if advertisement is currently active
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && $this->payment_status === 'Completed'
            && now()->between($this->start_date, $this->end_date);
    }
}
