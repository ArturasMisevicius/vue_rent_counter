<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Property;
use App\Policies\InvoicePolicy;
use App\Policies\MeterPolicy;
use App\Policies\PropertyPolicy;
use App\Support\Auth\ImpersonationManager;
use App\Support\Shell\Search\GlobalSearchRegistry;
use App\Support\Shell\Search\Providers\OrganizationSearchProvider;
use App\Support\Shell\Search\Providers\UserSearchProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImpersonationManager::class);

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
        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(Meter::class, MeterPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }
}
