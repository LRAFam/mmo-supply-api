<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_number',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'prize_pool',
        'features',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'prize_pool' => 'decimal:2',
        'features' => 'array',
    ];

    /**
     * Get the current active season
     */
    public static function current()
    {
        return self::where('status', 'active')->first();
    }

    /**
     * Get season participants
     */
    public function participants()
    {
        return $this->hasMany(UserSeasonParticipation::class);
    }

    /**
     * Get season achievements
     */
    public function achievements()
    {
        return $this->hasMany(Achievement::class);
    }

    /**
     * Get season rewards
     */
    public function rewards()
    {
        return $this->hasMany(UserReward::class);
    }

    /**
     * Get season statistics
     */
    public function getStats()
    {
        $participants = $this->participants;
        $totalPrizesAwarded = $participants->sum('total_earned');
        $topEarner = $participants->orderBy('total_earned', 'desc')->first();

        return [
            'total_participants' => $participants->count(),
            'total_prizes_awarded' => $totalPrizesAwarded,
            'achievements_unlocked' => $participants->sum('achievements_unlocked'),
            'top_earner' => $topEarner ? [
                'name' => $topEarner->user->name,
                'total_earned' => $topEarner->total_earned,
            ] : null,
        ];
    }

    /**
     * Get season leaderboard
     */
    public function getLeaderboard()
    {
        return $this->participants()
            ->with('user:id,name')
            ->orderBy('rank')
            ->get()
            ->map(function ($participation) {
                return [
                    'rank' => $participation->rank,
                    'user_id' => $participation->user_id,
                    'user_name' => $participation->user->name,
                    'sales' => $participation->total_sales,
                    'reward' => $participation->total_earned,
                ];
            });
    }

    /**
     * Check if season is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if season has ended
     */
    public function hasEnded(): bool
    {
        return $this->status === 'ended';
    }

    /**
     * Get days remaining in season
     */
    public function daysRemaining(): int
    {
        if ($this->hasEnded()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }
}
