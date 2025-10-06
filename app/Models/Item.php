<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'name',
        'title',
        'slug',
        'description',
        'content',
        'images',
        'price',
        'discount',
        'discount_price',
        'stock',
        'min_quantity',
        'max_quantity',
        'is_active',
        'is_featured',
        'featured_until',
        'delivery_time',
        'delivery_method',
        'requirements',
        'warranty_days',
        'refund_policy',
        'tags',
        'variants',
        'auto_deactivate',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'images' => 'array',
        'tags' => 'array',
        'variants' => 'array',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'auto_deactivate' => 'boolean',
        'featured_until' => 'datetime',
    ];

    protected $appends = ['images_urls'];

    /**
     * Get full URLs for all images
     */
    protected function imagesUrls(): Attribute
    {
        return Attribute::make(
            get: fn () => is_array($this->images) && !empty($this->images)
                ? array_map(function($img) {
                    // If already a full URL, return as is
                    if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                        return $img;
                    }
                    // Otherwise generate temporary URL
                    return Storage::disk('s3')->temporaryUrl($img, now()->addHours(24));
                }, $this->images)
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
