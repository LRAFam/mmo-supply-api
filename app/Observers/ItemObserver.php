<?php

namespace App\Observers;

use App\Models\Item;
use App\Services\DiscordWebhookService;

class ItemObserver
{
    protected $discordWebhook;

    public function __construct(DiscordWebhookService $discordWebhook)
    {
        $this->discordWebhook = $discordWebhook;
    }

    /**
     * Handle the Item "created" event.
     */
    public function created(Item $item): void
    {
        // Only send webhook if the item is active/published
        if ($item->is_active ?? true) {
            $this->discordWebhook->sendNewListing($item, 'item');
        }
    }

    /**
     * Handle the Item "updated" event.
     */
    public function updated(Item $item): void
    {
        // If item was just featured, send featured webhook
        if ($item->wasChanged('is_featured') && $item->is_featured) {
            $this->discordWebhook->sendFeaturedListing($item, 'item');
        }
    }
}
