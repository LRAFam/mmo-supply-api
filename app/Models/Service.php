<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'title',
        'slug',
        'description',
        'content',
        'images',
        'price',
        'discount',
        'is_active',
        'is_featured',
        'estimated_time',
    ];

    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected $appends = ['images_urls'];

    /**
     * Get full URLs for all images
     */
    protected function imagesUrls(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->images
                ? array_map(fn($img) => Storage::disk('s3')->url($img), $this->images)
                : [],
        );
    }

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

    public function getFinalPriceAttribute()
    {
        return $this->price - ($this->discount ?? 0);
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }
}
