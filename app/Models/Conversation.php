<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'order_id',
        'subject',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    /**
     * Get the other participant in the conversation
     */
    public function getOtherUser(int $userId): User
    {
        return $this->user_one_id === $userId ? $this->userTwo : $this->userOne;
    }

    /**
     * Get unread count for a specific user
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Mark all messages as read for a specific user
     */
    public function markAsRead(int $userId): void
    {
        $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Find or create conversation between two users
     */
    public static function findOrCreate(int $userOneId, int $userTwoId, ?int $orderId = null): self
    {
        // Ensure consistent ordering
        [$minId, $maxId] = $userOneId < $userTwoId ? [$userOneId, $userTwoId] : [$userTwoId, $userOneId];

        // If order_id is provided, include it in the search criteria
        $searchCriteria = [
            'user_one_id' => $minId,
            'user_two_id' => $maxId,
        ];

        if ($orderId !== null) {
            $searchCriteria['order_id'] = $orderId;
        }

        return self::firstOrCreate(
            $searchCriteria,
            [
                'order_id' => $orderId,
                'last_message_at' => now(),
            ]
        );
    }
}
