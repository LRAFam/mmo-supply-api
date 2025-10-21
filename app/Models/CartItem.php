<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'seller_id',
        'product_id',
        'product_type',
        'quantity',
        'price',
        'buyer_notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Get the cart that owns this item
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the seller of this item
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the product (polymorphic)
     */
    public function product(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the subtotal for this cart item
     */
    public function getSubtotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }
}
