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
        'service_type',
        'pricing_mode',  // New: 'fixed' or 'package_based'
        'title',
        'slug',
        'description',
        'content',
        'images',
        'price',
        'discount',
        'discount_price',
        'is_active',
        'is_featured',
        'featured_until',
        'estimated_time',
        'delivery_method',
        'tags',
        'packages',
        'addons',
        'boosting_config',
        'requirements',
        'schedule',
        'max_concurrent_orders',
        'warranty_days',
        'refund_policy',
        'auto_deactivate',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'images' => 'array',
        'tags' => 'array',
        'packages' => 'array',
        'addons' => 'array',
        'boosting_config' => 'array',
        'schedule' => 'array',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'auto_deactivate' => 'boolean',
        'featured_until' => 'datetime',
    ];

    protected $appends = ['images_urls', 'stock', 'delivery_time', 'average_rating', 'reviews_count'];

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
        // Use discount_price if set, otherwise calculate from price - discount
        if ($this->discount_price !== null && $this->discount_price > 0) {
            return $this->discount_price;
        }
        return $this->price - ($this->discount ?? 0);
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getReviewsCountAttribute()
    {
        return $this->reviews()->count();
    }

    public function getStockAttribute()
    {
        // Services don't have stock - they're always available unless max_concurrent_orders is reached
        // Return 999 to indicate "always available" for now
        return 999;
    }

    public function getDeliveryTimeAttribute()
    {
        // Return estimated_time or a default
        return $this->estimated_time ?? 'Varies';
    }
}
