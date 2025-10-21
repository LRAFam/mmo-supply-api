<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'items'];

    protected $casts = [
        'items' => 'array',
    ];

    /**
     * Get the user that owns this cart
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the cart items
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get cart items grouped by seller
     */
    public function itemsGroupedBySeller()
    {
        return $this->items()
            ->with(['seller', 'product'])
            ->get()
            ->groupBy('seller_id');
    }

    /**
     * Get the total number of items in the cart
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Get the cart subtotal
     */
    public function getSubtotalAttribute(): float
    {
        return $this->items->sum('subtotal');
    }
}
