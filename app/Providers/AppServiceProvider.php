<?php

namespace App\Providers;

use App\Support\Shell\DashboardUrlResolver;
use App\Support\Shell\Navigation\NavigationBuilder;
use App\Support\Shell\Search\GlobalSearchRegistry;
use App\Support\Shell\Search\Providers\OrganizationSearchProvider;
use App\Support\Shell\Search\Providers\UserSearchProvider;
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
        $this->app->singleton(OrganizationSearchProvider::class);
        $this->app->singleton(UserSearchProvider::class);
        $this->app->singleton(GlobalSearchRegistry::class, function ($app): GlobalSearchRegistry {
            return new GlobalSearchRegistry([
                $app->make(OrganizationSearchProvider::class),
                $app->make(UserSearchProvider::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
