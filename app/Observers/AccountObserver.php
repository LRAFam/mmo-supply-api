<?php

namespace App\Observers;

use App\Models\Account;
use App\Services\DiscordWebhookService;

class AccountObserver
{
    protected $discordWebhook;

    public function __construct(DiscordWebhookService $discordWebhook)
    {
        $this->discordWebhook = $discordWebhook;
    }

    /**
     * Handle the Account "created" event.
     */
    public function created(Account $account): void
    {
        if ($account->is_active ?? true) {
            $this->discordWebhook->sendNewListing($account, 'account');
        }
    }

    /**
     * Handle the Account "updated" event.
     */
    public function updated(Account $account): void
    {
        if ($account->wasChanged('is_featured') && $account->is_featured) {
            $this->discordWebhook->sendFeaturedListing($account, 'account');
        }
    }
}
