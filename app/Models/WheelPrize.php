<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WheelPrize extends Model
{
    use HasFactory;

    protected $fillable = [
        'spin_wheel_id',
        'name',
        'type',
        'value',
        'probability_weight',
        'color',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the wheel this prize belongs to
     */
    public function spinWheel()
    {
        return $this->belongsTo(SpinWheel::class);
    }

    /**
     * Get all spin results for this prize
     */
    public function spinResults()
    {
        return $this->hasMany(SpinResult::class);
    }
}
