<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'game_id',
        'banner_image',
        'starts_at',
        'ends_at',
        'status',
        'max_participants',
        'winner_count',
        'prizes',
        'rules',
        'requirements',
        'is_featured',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'prizes' => 'array',
        'rules' => 'array',
        'requirements' => 'array',
        'is_featured' => 'boolean',
    ];

    protected $appends = ['banner_image_url'];

    /**
     * Get full URL for banner image
     */
    protected function bannerImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->banner_image ? Storage::disk('s3')->url($this->banner_image) : null,
        );
    }

    /**
     * Game relationship
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Participants relationship
     */
    public function participants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    /**
     * Users participating
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'event_participants')
            ->withPivot('joined_at', 'score', 'rank', 'status', 'prize_data', 'prize_claimed')
            ->withTimestamps();
    }

    /**
     * Winners
     */
    public function winners()
    {
        return $this->participants()->where('status', 'winner')->orderBy('rank');
    }

    /**
     * Check if event is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               now()->between($this->starts_at, $this->ends_at);
    }

    /**
     * Check if event is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->status === 'upcoming' && now()->lt($this->starts_at);
    }

    /**
     * Check if event is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' || now()->gt($this->ends_at);
    }

    /**
     * Check if event has available spots
     */
    public function hasAvailableSpots(): bool
    {
        if (!$this->max_participants) {
            return true; // Unlimited
        }

        return $this->participants()->count() < $this->max_participants;
    }

    /**
     * Check if user can participate
     */
    public function canUserParticipate(User $user): bool
    {
        // Check if event is active
        if (!$this->isActive() && !$this->isUpcoming()) {
            return false;
        }

        // Check if user already participating
        if ($this->isUserParticipating($user)) {
            return false;
        }

        // Check if spots available
        if (!$this->hasAvailableSpots()) {
            return false;
        }

        // Check requirements
        if ($this->requirements && is_array($this->requirements)) {
            foreach ($this->requirements as $type => $value) {
                switch ($type) {
                    case 'min_purchases':
                        $purchases = $user->orders()->where('payment_status', 'completed')->count();
                        if ($purchases < $value) return false;
                        break;

                    case 'min_spent':
                        $spent = $user->orders()->where('payment_status', 'completed')->sum('total');
                        if ($spent < $value) return false;
                        break;

                    case 'is_seller':
                        if (!$user->is_seller) return false;
                        break;

                    case 'min_account_age_days':
                        $accountAge = now()->diffInDays($user->created_at);
                        if ($accountAge < $value) return false;
                        break;
                }
            }
        }

        return true;
    }

    /**
     * Check if user is participating
     */
    public function isUserParticipating(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Register user for event
     */
    public function registerUser(User $user): bool
    {
        if (!$this->canUserParticipate($user)) {
            return false;
        }

        try {
            $this->participants()->create([
                'user_id' => $user->id,
                'joined_at' => now(),
                'status' => 'registered',
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Award prizes to winners
     */
    public function awardPrizes(): void
    {
        $winners = $this->winners()->get();

        foreach ($winners as $winner) {
            if ($winner->prize_claimed) {
                continue;
            }

            $prizeData = $winner->prize_data;
            if (!$prizeData) {
                // Determine prize based on rank
                $prizeData = $this->getPrizeForRank($winner->rank);
            }

            DB::beginTransaction();
            try {
                // Award wallet balance
                if (isset($prizeData['wallet_amount']) && $prizeData['wallet_amount'] > 0) {
                    $winner->user->increment('wallet_balance', $prizeData['wallet_amount']);
                }

                // Mark prize as claimed
                $winner->update([
                    'prize_claimed' => true,
                    'prize_data' => $prizeData,
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
    }

    /**
     * Get prize for a specific rank
     */
    private function getPrizeForRank(int $rank): array
    {
        if (!$this->prizes || !isset($this->prizes[$rank - 1])) {
            return [];
        }

        return $this->prizes[$rank - 1];
    }

    /**
     * Determine winners based on scores
     */
    public function determineWinners(): void
    {
        $topParticipants = $this->participants()
            ->orderBy('score', 'desc')
            ->limit($this->winner_count)
            ->get();

        $rank = 1;
        foreach ($topParticipants as $participant) {
            $participant->update([
                'status' => 'winner',
                'rank' => $rank,
            ]);
            $rank++;
        }

        // Update non-winners
        $this->participants()
            ->whereNotIn('id', $topParticipants->pluck('id'))
            ->update(['status' => 'completed']);
    }

    /**
     * Complete event and award prizes
     */
    public function complete(): void
    {
        if ($this->type === 'tournament') {
            $this->determineWinners();
        }

        $this->update(['status' => 'completed']);
        $this->awardPrizes();
    }

    /**
     * Get active events
     */
    public static function getActive()
    {
        return self::where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->get();
    }

    /**
     * Get upcoming events
     */
    public static function getUpcoming()
    {
        return self::where('status', 'upcoming')
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Get featured events
     */
    public static function getFeatured()
    {
        return self::where('is_featured', true)
            ->whereIn('status', ['active', 'upcoming'])
            ->orderBy('starts_at')
            ->get();
    }
}
