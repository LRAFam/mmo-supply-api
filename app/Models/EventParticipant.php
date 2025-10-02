<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'joined_at',
        'score',
        'rank',
        'status',
        'prize_data',
        'prize_claimed',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'prize_data' => 'array',
        'prize_claimed' => 'boolean',
    ];

    /**
     * Event relationship
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if participant is a winner
     */
    public function isWinner(): bool
    {
        return $this->status === 'winner';
    }

    /**
     * Claim prize
     */
    public function claimPrize(): bool
    {
        if (!$this->isWinner() || $this->prize_claimed) {
            return false;
        }

        // Logic handled in Event model
        $this->event->awardPrizes();

        return true;
    }
}
