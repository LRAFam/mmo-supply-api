<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSeasonParticipation extends Model
{
    use HasFactory;

    protected $table = 'user_season_participation';

    protected $fillable = [
        'user_id',
        'season_id',
        'rank',
        'total_sales',
        'total_earned',
        'achievements_unlocked',
        'participated',
    ];

    protected $casts = [
        'total_sales' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'participated' => 'boolean',
    ];

    /**
     * Get the user for this participation
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the season for this participation
     */
    public function season()
    {
        return $this->belongsTo(Season::class);
    }
}
