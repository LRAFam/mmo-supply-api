<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Discord\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define polymorphic morph map for order items and subscriptions
        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'items' => \App\Models\Item::class,
            'accounts' => \App\Models\Account::class,
            'currencies' => \App\Models\Currency::class,
            'services' => \App\Models\Service::class,
        ]);

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('discord', Provider::class);
        });
    }
}
