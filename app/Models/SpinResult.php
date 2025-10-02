<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpinResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'spin_wheel_id',
        'wheel_prize_id',
        'prize_name',
        'prize_type',
        'prize_value',
        'spun_at',
    ];

    protected $casts = [
        'prize_value' => 'decimal:2',
        'spun_at' => 'datetime',
    ];

    /**
     * Get the user who spun
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wheel that was spun
     */
    public function spinWheel()
    {
        return $this->belongsTo(SpinWheel::class);
    }

    /**
     * Get the prize that was won
     */
    public function wheelPrize()
    {
        return $this->belongsTo(WheelPrize::class);
    }
}
