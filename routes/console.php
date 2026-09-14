<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily AI dashboard insight (early morning) + nightly data backup.
Schedule::command('insight:generate-daily')->dailyAt('06:00');
Schedule::command('backup:database')->dailyAt('02:30');

// If we ever have long-running jobs, the queue worker is triggered via cron too.
Schedule::command('queue:work --stop-when-empty --tries=3')->everyMinute()->withoutOverlapping();
