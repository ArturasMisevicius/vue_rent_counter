<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule summer average gyvatukas calculation
// Runs annually on October 1st at 00:00 (start of heating season)
Schedule::command('gyvatukas:calculate-summer-average')
    ->yearlyOn(10, 1, '00:00')
    ->timezone('Europe/Vilnius');

// Schedule automated database backup
// Runs daily at 02:00 to create consistent SQLite snapshots
Schedule::command('backup:run')
    ->daily()
    ->at('02:00')
    ->timezone('Europe/Vilnius');

// Schedule automated backup cleanup
// Runs daily at 03:00 to enforce retention policy
Schedule::command('backup:clean')
    ->daily()
    ->at('03:00')
    ->timezone('Europe/Vilnius');
