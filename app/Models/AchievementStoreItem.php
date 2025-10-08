<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AchievementStoreItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'icon',
        'points_cost',
        'rarity',
        'metadata',
        'is_active',
        'is_limited',
        'available_from',
        'available_until',
        'max_uses',
        'cooldown_days',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_limited' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'max_uses' => 'integer',
        'cooldown_days' => 'integer',
        'points_cost' => 'integer',
    ];

    /**
     * Check if item is currently available
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        // Check availability window
        if ($this->available_from && $now->isBefore($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->isAfter($this->available_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can purchase this item
     */
    public function canPurchase(User $user): array
    {
        // Check if item is available
        if (!$this->isAvailable()) {
            return [
                'can_purchase' => false,
                'reason' => 'This item is not currently available.'
            ];
        }

        // Check if user has enough points
        if ($user->achievement_points < $this->points_cost) {
            return [
                'can_purchase' => false,
                'reason' => 'Insufficient achievement points.'
            ];
        }

        // Check if user already owns this item (for non-consumable items)
        if ($this->isCosmetic() && $user->ownsCosmetic($this->slug)) {
            return [
                'can_purchase' => false,
                'reason' => 'You already own this item.'
            ];
        }

        // Check if item has usage limit
        if ($this->max_uses !== null) {
            $usageCount = $user->getItemUsageCount($this->slug);
            if ($usageCount >= $this->max_uses) {
                return [
                    'can_purchase' => false,
                    'reason' => 'Maximum uses reached for this item.'
                ];
            }
        }

        // Check cooldown
        if ($this->cooldown_days !== null) {
            $lastUsed = $user->getItemLastUsed($this->slug);
            if ($lastUsed) {
                $cooldownEnds = $lastUsed->addDays($this->cooldown_days);
                if (Carbon::now()->isBefore($cooldownEnds)) {
                    $daysRemaining = Carbon::now()->diffInDays($cooldownEnds, false);
                    return [
                        'can_purchase' => false,
                        'reason' => "Item is on cooldown. Available in {$daysRemaining} days."
                    ];
                }
            }
        }

        return [
            'can_purchase' => true,
            'reason' => null
        ];
    }

    /**
     * Check if item is a cosmetic type
     */
    public function isCosmetic(): bool
    {
        return in_array($this->category, [
            'profile_theme',
            'badge',
            'title',
            'frame',
            'username_effect',
        ]);
    }

    /**
     * Check if item is a marketplace perk
     */
    public function isMarketplacePerk(): bool
    {
        return in_array($this->category, [
            'marketplace_perk',
            'listing_boost',
        ]);
    }

    /**
     * Check if item is functional
     */
    public function isFunctional(): bool
    {
        return $this->category === 'functional';
    }

    /**
     * Get rarity color for UI
     */
    public function getRarityColor(): string
    {
        return match($this->rarity) {
            'common' => '#9CA3AF',      // gray-400
            'uncommon' => '#10B981',    // green-500
            'rare' => '#3B82F6',        // blue-500
            'epic' => '#A855F7',        // purple-500
            'legendary' => '#F59E0B',   // amber-500
            default => '#9CA3AF',
        };
    }

    /**
     * Get rarity display name
     */
    public function getRarityDisplayName(): string
    {
        return ucfirst($this->rarity);
    }

    /**
     * Apply item effect to user
     */
    public function applyToUser(User $user): bool
    {
        switch ($this->category) {
            case 'profile_theme':
            case 'title':
            case 'frame':
            case 'username_effect':
            case 'badge':
                return $user->addCosmetic($this->slug, $this->category);

            case 'marketplace_perk':
            case 'listing_boost':
                return $user->applyMarketplacePerk($this->slug, $this->metadata);

            case 'functional':
                return $user->applyFunctionalItem($this->slug, $this->metadata);

            default:
                return false;
        }
    }

    /**
     * Scope to get available items
     */
    public function scopeAvailable($query)
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('available_until')
                    ->orWhere('available_until', '>=', $now);
            });
    }

    /**
     * Scope to filter by category
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by rarity
     */
    public function scopeOfRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    /**
     * Scope to get limited items
     */
    public function scopeLimited($query)
    {
        return $query->where('is_limited', true);
    }

    /**
     * Scope to get seasonal items
     */
    public function scopeSeasonal($query)
    {
        return $query->where('category', 'seasonal');
    }
}
