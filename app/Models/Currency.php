<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Currency extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'name',
        'slug',
        'description',
        'stock',
        'rate',
        'price_per_unit',
        'min_amount',
        'max_amount',
        'images',
        'is_active',
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'price_per_unit' => 'decimal:2',
        'rate' => 'decimal:2',
    ];

    protected $appends = ['rating', 'total_sales', 'vouches', 'is_verified'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'user_id', 'user_id')
            ->where('game_id', $this->game_id);
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    // Get rating from associated provider
    public function getRatingAttribute()
    {
        $provider = Provider::where('user_id', $this->user_id)
            ->where('game_id', $this->game_id)
            ->first();

        return $provider ? $provider->rating : 0;
    }

    // Get other provider stats
    public function getTotalSalesAttribute()
    {
        $provider = Provider::where('user_id', $this->user_id)
            ->where('game_id', $this->game_id)
            ->first();

        return $provider ? $provider->total_sales : 0;
    }

    public function getVouchesAttribute()
    {
        $provider = Provider::where('user_id', $this->user_id)
            ->where('game_id', $this->game_id)
            ->first();

        return $provider ? $provider->vouches : 0;
    }

    public function getIsVerifiedAttribute()
    {
        $provider = Provider::where('user_id', $this->user_id)
            ->where('game_id', $this->game_id)
            ->first();

        return $provider ? $provider->is_verified : false;
    }
}
