<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Enable WAL mode for SQLite
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA journal_mode=WAL;');
        }
    }
}
