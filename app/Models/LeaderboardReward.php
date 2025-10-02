<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period',
        'period_start',
        'period_end',
        'rank',
        'sales_amount',
        'reward_amount',
        'badge',
        'credited',
        'credited_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'sales_amount' => 'decimal:2',
        'reward_amount' => 'decimal:2',
        'credited' => 'boolean',
        'credited_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
