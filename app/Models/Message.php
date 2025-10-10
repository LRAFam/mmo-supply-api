<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'message',
        'metadata',
        'actions',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'metadata' => 'array',
        'actions' => 'array',
    ];

    // Always load sender with cosmetics
    protected $with = ['sender:id,name,username,avatar,active_profile_theme,active_title,auto_tier,owned_cosmetics,badge_inventory'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Check if message is from AI agent
     */
    public function isAIAgent(): bool
    {
        return $this->type === 'ai_agent';
    }

    /**
     * Check if message is system message
     */
    public function isSystem(): bool
    {
        return $this->type === 'system';
    }

    /**
     * Check if message has actions
     */
    public function hasActions(): bool
    {
        return !empty($this->actions);
    }

    /**
     * Get formatted cosmetic data for frontend
     */
    public function getSenderCosmetics(): array
    {
        if (!$this->sender) {
            return [];
        }

        $ownedCosmetics = is_array($this->sender->owned_cosmetics)
            ? $this->sender->owned_cosmetics
            : json_decode($this->sender->owned_cosmetics ?? '[]', true);

        return [
            'avatar' => $this->sender->avatar,
            'name' => $this->sender->name,
            'username' => $this->sender->username ?? $this->sender->name,
            'title' => $this->sender->active_title,
            'theme' => $this->sender->active_profile_theme,
            'tier' => $this->sender->auto_tier,
            'frame' => $ownedCosmetics['frame'] ?? null,
            'username_effect' => $ownedCosmetics['username_effect'] ?? null,
            'badges' => $this->sender->badge_inventory ?? [],
        ];
    }
}
