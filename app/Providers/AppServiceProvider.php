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
        // Using singular forms to match our ProductType enum
        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'item' => \App\Models\Item::class,
            'account' => \App\Models\Account::class,
            'currency' => \App\Models\Currency::class,
            'service' => \App\Models\Service::class,
        ]);

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('discord', Provider::class);
        });

        // Register generic product observer for all product types
        \App\Models\Item::observe(\App\Observers\ProductObserver::class);
        \App\Models\Currency::observe(\App\Observers\ProductObserver::class);
        \App\Models\Account::observe(\App\Observers\ProductObserver::class);
        \App\Models\Service::observe(\App\Observers\ProductObserver::class);
    }
}
