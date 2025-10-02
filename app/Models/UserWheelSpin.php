<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWheelSpin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'spin_wheel_id',
        'last_spin_at',
        'next_available_at',
    ];

    protected $casts = [
        'last_spin_at' => 'datetime',
        'next_available_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the spin wheel
     */
    public function spinWheel()
    {
        return $this->belongsTo(SpinWheel::class);
    }
}
