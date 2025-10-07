<?php

namespace App\Observers;

use App\Models\Currency;
use App\Services\DiscordWebhookService;

class CurrencyObserver
{
    protected $discordWebhook;

    public function __construct(DiscordWebhookService $discordWebhook)
    {
        $this->discordWebhook = $discordWebhook;
    }

    /**
     * Handle the Currency "created" event.
     */
    public function created(Currency $currency): void
    {
        if ($currency->is_active ?? true) {
            $this->discordWebhook->sendNewListing($currency, 'currency');
        }
    }

    /**
     * Handle the Currency "updated" event.
     */
    public function updated(Currency $currency): void
    {
        if ($currency->wasChanged('is_featured') && $currency->is_featured) {
            $this->discordWebhook->sendFeaturedListing($currency, 'currency');
        }
    }
}
