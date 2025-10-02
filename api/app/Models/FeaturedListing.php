<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FeaturedListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_type',
        'product_id',
        'price',
        'starts_at',
        'expires_at',
        'is_active',
        'stripe_payment_intent_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if featured listing is active and not expired
     */
    public function isActiveAndValid(): bool
    {
        return $this->is_active &&
               $this->starts_at->isPast() &&
               $this->expires_at->isFuture();
    }

    /**
     * Get pricing for featured listings based on duration
     */
    public static function getPricing(): array
    {
        return [
            7 => 5.00,   // 7 days - $5
            14 => 8.00,  // 14 days - $8
            30 => 15.00, // 30 days - $15
        ];
    }
}
