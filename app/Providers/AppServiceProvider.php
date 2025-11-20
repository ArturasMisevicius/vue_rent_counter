<?php

namespace App\Providers;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Tariff::class => \App\Policies\TariffPolicy::class,
        \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Models\MeterReading::class => \App\Policies\MeterReadingPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Property::class => \App\Policies\PropertyPolicy::class,
        \App\Models\Building::class => \App\Policies\BuildingPolicy::class,
        \App\Models\Meter::class => \App\Policies\MeterPolicy::class,
        \App\Models\Provider::class => \App\Policies\ProviderPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register singleton services for better performance
        $this->app->singleton(\App\Services\MeterReadingService::class);
        $this->app->singleton(\App\Services\TimeRangeValidator::class);
        $this->app->singleton(\App\Services\GyvatukasCalculator::class);

        // Register TariffResolver with its strategies
        $this->app->singleton(\App\Services\TariffResolver::class, function ($app) {
            return new \App\Services\TariffResolver([
                $app->make(\App\Services\TariffCalculation\FlatRateStrategy::class),
                $app->make(\App\Services\TariffCalculation\TimeOfUseStrategy::class),
            ]);
        });

        // Register BillingCalculatorFactory
        $this->app->singleton(\App\Services\BillingCalculation\BillingCalculatorFactory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Eloquent observers
        \App\Models\MeterReading::observe(\App\Observers\MeterReadingObserver::class);

        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register settings gates
        Gate::define('viewSettings', [\App\Policies\SettingsPolicy::class, 'viewSettings']);
        Gate::define('updateSettings', [\App\Policies\SettingsPolicy::class, 'updateSettings']);
        Gate::define('runBackup', [\App\Policies\SettingsPolicy::class, 'runBackup']);
        Gate::define('clearCache', [\App\Policies\SettingsPolicy::class, 'clearCache']);

        // Set tenant_id in session when user authenticates
        Event::listen(Authenticated::class, function (Authenticated $event) {
            if ($event->user && $event->user->tenant_id) {
                session(['tenant_id' => $event->user->tenant_id]);
            }
        });
    }
}
