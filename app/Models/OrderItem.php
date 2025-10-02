<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_id',
        'product_type',
        'product_id',
        'product_name',
        'product_description',
        'product_images',
        'game_name',
        'quantity',
        'price',
        'discount',
        'total',
        'status',
        'delivery_details',
        'delivered_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'delivered_at' => 'datetime',
        'product_images' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
