<?php

namespace App\Observers;

use App\Models\Service;
use App\Services\DiscordWebhookService;

class ServiceObserver
{
    protected $discordWebhook;

    public function __construct(DiscordWebhookService $discordWebhook)
    {
        $this->discordWebhook = $discordWebhook;
    }

    /**
     * Handle the Service "created" event.
     */
    public function created(Service $service): void
    {
        if ($service->is_active ?? true) {
            $this->discordWebhook->sendNewListing($service, 'service');
        }
    }

    /**
     * Handle the Service "updated" event.
     */
    public function updated(Service $service): void
    {
        if ($service->wasChanged('is_featured') && $service->is_featured) {
            $this->discordWebhook->sendFeaturedListing($service, 'service');
        }
    }
}
