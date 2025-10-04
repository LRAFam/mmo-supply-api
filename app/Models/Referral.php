<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referral_code',
        'total_earnings',
        'total_purchases',
        'first_purchase_at',
    ];

    protected $casts = [
        'total_earnings' => 'decimal:2',
        'first_purchase_at' => 'datetime',
    ];

    /**
     * Get the user who made the referral
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the user who was referred
     */
    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    /**
     * Get all earnings from this referral
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(ReferralEarning::class);
    }

    /**
     * Record a purchase and calculate earnings
     */
    public function recordPurchase(Order $order, float $commissionPercentage = 5.0): void
    {
        $commissionAmount = ($order->total * $commissionPercentage) / 100;

        // Create earning record
        $this->earnings()->create([
            'order_id' => $order->id,
            'referrer_id' => $this->referrer_id,
            'referred_id' => $this->referred_id,
            'order_amount' => $order->total,
            'commission_percentage' => $commissionPercentage,
            'commission_amount' => $commissionAmount,
            'level' => 1,
            'status' => 'pending',
        ]);

        // Update referral totals
        $this->increment('total_purchases');
        $this->increment('total_earnings', $commissionAmount);

        if (!$this->first_purchase_at) {
            $this->update(['first_purchase_at' => now()]);
        }

        // Update referrer's total earnings
        $this->referrer->increment('total_referral_earnings', $commissionAmount);

        // Add to wallet
        $wallet = $this->referrer->getOrCreateWallet();
        $wallet->increment('balance', $commissionAmount);

        // Create transaction record
        $this->referrer->transactions()->create([
            'type' => 'referral_commission',
            'amount' => $commissionAmount,
            'status' => 'completed',
            'description' => "Referral commission from order #{$order->id}",
            'wallet_id' => $wallet->id,
        ]);
    }
}
