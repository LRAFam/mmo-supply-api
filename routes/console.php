<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Reset monthly sales for all providers on the 1st of each month at midnight UTC
Schedule::command('sales:reset-monthly')
    ->monthlyOn(1, '00:00')
    ->timezone('UTC');

// Distribute monthly leaderboard rewards on the 1st of each month at 00:30 UTC (after sales reset)
Schedule::command('leaderboard:distribute-rewards monthly')
    ->monthlyOn(1, '00:30')
    ->timezone('UTC');

// Distribute weekly leaderboard rewards every Monday at midnight UTC
Schedule::command('leaderboard:distribute-rewards weekly')
    ->weeklyOn(1, '00:00')
    ->timezone('UTC');
