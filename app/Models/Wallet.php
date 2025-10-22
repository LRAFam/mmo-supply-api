<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'bonus_balance',
        'pending_balance',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'bonus_balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    // Helper methods
    public function deposit(float $amount, string $description = null, array $metadata = []): Transaction
    {
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'deposit',
            'amount' => $amount,
            'currency' => $this->currency,
            'status' => 'completed',
            'description' => $description ?? 'Wallet deposit',
            'metadata' => $metadata,
        ]);
    }

    public function withdraw(float $amount, string $description = null, array $metadata = []): Transaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'withdrawal',
            'amount' => -$amount,
            'currency' => $this->currency,
            'status' => 'completed',
            'description' => $description ?? 'Wallet withdrawal',
            'metadata' => $metadata,
        ]);
    }

    public function purchase(float $amount, ?int $orderId = null, string $description = null): Transaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'purchase',
            'amount' => -$amount,
            'currency' => $this->currency,
            'status' => 'completed',
            'order_id' => $orderId,
            'description' => $description ?? 'Purchase',
        ]);
    }

    public function receiveSale(float $amount, int $orderId, string $description = null): Transaction
    {
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'sale',
            'amount' => $amount,
            'currency' => $this->currency,
            'status' => 'completed',
            'order_id' => $orderId,
            'description' => $description ?? 'Sale proceeds',
        ]);
    }

    public function refund(float $amount, int $orderId, string $description = null): Transaction
    {
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'refund',
            'amount' => $amount,
            'currency' => $this->currency,
            'status' => 'completed',
            'order_id' => $orderId,
            'description' => $description ?? 'Order refund',
        ]);
    }

    public function getAvailableBalanceAttribute(): float
    {
        return $this->balance - $this->pending_balance;
    }

    public function getTotalBalanceAttribute(): float
    {
        return $this->balance + $this->bonus_balance;
    }

    public function addBonusBalance(float $amount, string $description = null, array $metadata = []): Transaction
    {
        $this->increment('bonus_balance', $amount);

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'bonus',
            'amount' => $amount,
            'currency' => $this->currency,
            'status' => 'completed',
            'description' => $description ?? 'Bonus balance added',
            'metadata' => $metadata,
        ]);
    }
}
