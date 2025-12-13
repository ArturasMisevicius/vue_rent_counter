<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

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

// Schedule overdue invoice notifications daily at 09:00
Schedule::command('invoices:notify-overdue')
    ->dailyAt('09:00')
    ->timezone('Europe/Vilnius');

// Schedule daily export reports at 02:30
Schedule::command('export:daily-reports')
    ->dailyAt('02:30')
    ->timezone('Europe/Vilnius');

// Schedule weekly export reports on Mondays at 03:00
Schedule::command('export:weekly-reports')
    ->weeklyOn(1, '03:00')
    ->timezone('Europe/Vilnius');

// Schedule monthly export reports on the 1st of each month at 04:00
Schedule::command('export:monthly-reports')
    ->monthlyOn(1, '04:00')
    ->timezone('Europe/Vilnius');

// Schedule export file cleanup weekly on Sundays at 05:00
Schedule::command('export:cleanup')
    ->weeklyOn(0, '05:00')
    ->timezone('Europe/Vilnius');

// Schedule subscription monitoring daily at 08:00
// Checks for expiring subscriptions and processes auto-renewals
Schedule::command('subscriptions:monitor')
    ->dailyAt('08:00')
    ->timezone('Europe/Vilnius');
