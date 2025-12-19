<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Security Commands
        $schedule->command('tokens:prune --hours=24')
                 ->daily()
                 ->at('02:00')
                 ->description('Prune expired API tokens for security');

        $schedule->command('security:monitor')
                 ->everyFifteenMinutes()
                 ->description('Monitor security metrics and send alerts');

        // Existing scheduled commands
        $schedule->command('inspire')->hourly();
        
        // Cache optimization
        $schedule->command('cache:prune-stale-tags')
                 ->hourly()
                 ->description('Prune stale cache tags');

        // Log rotation
        $schedule->command('log:clear --keep=30')
                 ->daily()
                 ->at('01:00')
                 ->description('Clear old log files');

        // Database maintenance
        $schedule->command('model:prune')
                 ->daily()
                 ->at('03:00')
                 ->description('Prune soft-deleted models');

        // Security audit (weekly)
        $schedule->command('security:audit')
                 ->weekly()
                 ->sundays()
                 ->at('04:00')
                 ->description('Weekly security audit')
                 ->when(function () {
                     return app()->environment('production');
                 });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}