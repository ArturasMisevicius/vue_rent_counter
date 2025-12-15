<?php

namespace App\Providers;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
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
        \App\Models\Organization::class => \App\Policies\OrganizationPolicy::class,
        \App\Models\OrganizationActivityLog::class => \App\Policies\OrganizationActivityLogPolicy::class,
        \App\Models\Subscription::class => \App\Policies\SubscriptionPolicy::class,
        \App\Models\ServiceConfiguration::class => \App\Policies\ServiceConfigurationPolicy::class,
        \App\Models\Tenant::class => \App\Policies\TenantPolicy::class,
        \App\Models\Faq::class => \App\Policies\FaqPolicy::class,
        \App\Models\Language::class => \App\Policies\LanguagePolicy::class,
        // Note: PlatformUserPolicy is used for cross-organization user management
        // and is applied manually in Filament resources rather than auto-mapped
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register singleton services for better performance
        $this->app->singleton(\App\Services\MeterReadingService::class);
        $this->app->singleton(\App\Services\TimeRangeValidator::class);
        $this->app->singleton(\App\Services\BillingService::class);
        
        // Register dashboard cache service
        $this->app->singleton(\App\Services\DashboardCacheService::class);
        
        // Register query optimization service
        $this->app->singleton(\App\Services\QueryOptimizationService::class);
        
        // Register background job service
        $this->app->singleton(\App\Services\BackgroundJobService::class);
        
        // Register asset optimization service
        $this->app->singleton(\App\Services\AssetOptimizationService::class);
        
        // Register InputSanitizer with interface binding for dependency inversion
        $this->app->singleton(
            \App\Contracts\InputSanitizerInterface::class,
            \App\Services\InputSanitizer::class
        );

        // Register validation framework services
        $this->app->singleton(\App\Services\Validation\ValidationRuleFactory::class);
        $this->app->singleton(\App\Services\ServiceValidationEngine::class);
        
        // Register new refactored services
        $this->app->singleton(\App\Services\SystemHealthService::class);
        $this->app->singleton(\App\Repositories\MeterReadingRepository::class);

        // Register TariffResolver with its strategies
        $this->app->singleton(\App\Services\TariffResolver::class, function ($app) {
            return new \App\Services\TariffResolver([
                $app->make(\App\Services\TariffCalculation\FlatRateStrategy::class),
                $app->make(\App\Services\TariffCalculation\TimeOfUseStrategy::class),
            ]);
        });

        // Register SubscriptionChecker with interface binding
        $this->app->singleton(
            \App\Contracts\SubscriptionCheckerInterface::class,
            \App\Services\SubscriptionChecker::class
        );

        // Register Super Admin services with interface bindings
        $this->app->singleton(
            \App\Contracts\TenantManagementInterface::class,
            \App\Services\TenantManagementService::class
        );
        
        $this->app->singleton(
            \App\Contracts\SystemMonitoringInterface::class,
            \App\Services\SystemMonitoringService::class
        );
        
        $this->app->singleton(
            \App\Contracts\SuperAdminUserInterface::class,
            \App\Services\SuperAdminUserService::class
        );

        // Laravel 12 no longer binds the legacy 'files' service alias; add it for packages (Debugbar)
        if (! $this->app->bound('files')) {
            $this->app->singleton('files', fn () => new Filesystem());
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load translations moved from lang/vendor to lang/backup so the backup package keeps working
        $this->loadTranslationsFrom(lang_path('backup'), 'backup');

        // Register view composers
        \Illuminate\Support\Facades\View::composer(
            'layouts.app',
            \App\View\Composers\NavigationComposer::class
        );

        // Register Eloquent observers
        \App\Models\MeterReading::observe(\App\Observers\MeterReadingObserver::class);
        \App\Models\Faq::observe(\App\Observers\FaqObserver::class);
        \App\Models\Tariff::observe(\App\Observers\TariffObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\Language::observe(\App\Observers\LanguageObserver::class);
        \App\Models\Subscription::observe(\App\Observers\SubscriptionObserver::class);
        
        // Register superadmin audit observers
        \App\Models\Organization::observe(\App\Observers\SuperadminOrganizationObserver::class);
        \App\Models\Subscription::observe(\App\Observers\SuperadminSubscriptionObserver::class);
        \App\Models\User::observe(\App\Observers\SuperadminUserObserver::class);
        
        // Register cache invalidation observers
        $cacheObserver = app(\App\Observers\CacheInvalidationObserver::class);
        \App\Models\Organization::observe($cacheObserver);
        \App\Models\Subscription::observe($cacheObserver);
        \App\Models\OrganizationActivityLog::observe($cacheObserver);

        // Register security event listeners
        Event::listen(
            \App\Events\SecurityViolationDetected::class,
            \App\Listeners\LogSecurityViolation::class
        );

        if (! Collection::hasMacro('takeLast')) {
            Collection::macro('takeLast', function (int $count) {
                return $count <= 0
                    ? $this->take(0)
                    : $this->take(-$count);
            });
        }

        // Filament v4 compatibility: provide legacy Section alias if missing
        if (! class_exists(\Filament\Forms\Components\Section::class) &&
            class_exists(\Filament\Schemas\Components\Section::class)) {
            class_alias(\Filament\Schemas\Components\Section::class, \Filament\Forms\Components\Section::class);
        }

        // Filament v4 compatibility: bulk action group moved namespaces
        if (! class_exists(\Filament\Tables\Actions\BulkActionGroup::class) &&
            class_exists(\Filament\Actions\BulkActionGroup::class)) {
            class_alias(\Filament\Actions\BulkActionGroup::class, \Filament\Tables\Actions\BulkActionGroup::class);
        }

        if (! class_exists(\Filament\Tables\Actions\EditAction::class) &&
            class_exists(\Filament\Actions\EditAction::class)) {
            class_alias(\Filament\Actions\EditAction::class, \Filament\Tables\Actions\EditAction::class);
        }

        if (! class_exists(\Filament\Tables\Actions\DeleteAction::class) &&
            class_exists(\Filament\Actions\DeleteAction::class)) {
            class_alias(\Filament\Actions\DeleteAction::class, \Filament\Tables\Actions\DeleteAction::class);
        }

        if (! class_exists(\Filament\Tables\Actions\DeleteBulkAction::class) &&
            class_exists(\Filament\Actions\DeleteBulkAction::class)) {
            class_alias(\Filament\Actions\DeleteBulkAction::class, \Filament\Tables\Actions\DeleteBulkAction::class);
        }

        if (! class_exists(\Filament\Tables\Actions\ViewAction::class) &&
            class_exists(\Filament\Actions\ViewAction::class)) {
            class_alias(\Filament\Actions\ViewAction::class, \Filament\Tables\Actions\ViewAction::class);
        }

        if (! class_exists(\Filament\Tables\Actions\Action::class) &&
            class_exists(\Filament\Actions\Action::class)) {
            class_alias(\Filament\Actions\Action::class, \Filament\Tables\Actions\Action::class);
        }

        if (! class_exists(\Filament\Tables\Actions\BulkAction::class) &&
            class_exists(\Filament\Actions\BulkAction::class)) {
            class_alias(\Filament\Actions\BulkAction::class, \Filament\Tables\Actions\BulkAction::class);
        }

        if (! class_exists(\Filament\Tables\Actions\CreateAction::class) &&
            class_exists(\Filament\Actions\CreateAction::class)) {
            class_alias(\Filament\Actions\CreateAction::class, \Filament\Tables\Actions\CreateAction::class);
        }

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

        // Rate limiting for admin routes (120 requests per minute per user)
        // Prevents brute force attacks and DoS attempts
        \Illuminate\Support\Facades\RateLimiter::for('admin', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.'
                    ], 429);
                });
        });

        // Rate limiting for API routes (60 requests per minute per user)
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.'
                    ], 429);
                });
        });
    }
}
