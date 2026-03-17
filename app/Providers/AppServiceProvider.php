<?php

namespace App\Providers;

use App\Support\Shell\DashboardUrlResolver;
use App\Support\Shell\Navigation\NavigationBuilder;
use App\Support\Shell\UserAvatarColor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DashboardUrlResolver::class);
        $this->app->singleton(NavigationBuilder::class);
        $this->app->singleton(UserAvatarColor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
