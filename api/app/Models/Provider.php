<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Provider extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'vouches',
        'rating',
        'total_sales',
        'is_verified',
    ];

    protected $casts = [
        'vouches' => 'integer',
        'total_sales' => 'integer',
        'rating' => 'decimal:2',
        'is_verified' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
