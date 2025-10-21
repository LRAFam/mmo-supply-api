<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'seller_id',
        'order_number',
        'order_group_id',
        'cart_id',
        'status',
        'subtotal',
        'tax',
        'total',
        'platform_fee',
        'seller_payout',
        'payment_method',
        'payment_status',
        'stripe_payment_intent_id',
        'stripe_transfer_id',
        'buyer_notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'seller_payout' => 'decimal:2',
    ];

    /**
     * Get the buyer (user who placed the order)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user relationship
     */
    public function buyer(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the seller for this order
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get the cart this order was created from
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get other orders in the same order group (from same checkout)
     */
    public function groupedOrders()
    {
        return self::where('order_group_id', $this->order_group_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }
}
