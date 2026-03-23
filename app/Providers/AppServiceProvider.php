<?php

namespace App\Providers;

use App\Contracts\BillingServiceInterface;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\Auth\ImpersonationManager;
use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Filament\Support\Shell\Search\GlobalSearchRegistry;
use App\Filament\Support\Shell\Search\Providers\BuildingSearchProvider;
use App\Filament\Support\Shell\Search\Providers\InvoiceSearchProvider;
use App\Filament\Support\Shell\Search\Providers\MeterReadingSearchProvider;
use App\Filament\Support\Shell\Search\Providers\OrganizationSearchProvider;
use App\Filament\Support\Shell\Search\Providers\PropertySearchProvider;
use App\Filament\Support\Shell\Search\Providers\TenantSearchProvider;
use App\Filament\Support\Superadmin\Integration\IntegrationProbeRegistry;
use App\Filament\Support\Superadmin\Integration\Probes\DatabaseProbe;
use App\Filament\Support\Superadmin\Integration\Probes\MailProbe;
use App\Filament\Support\Superadmin\Integration\Probes\QueueProbe;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Observers\OrganizationObserver;
use App\Observers\PropertyAssignmentObserver;
use App\Observers\SubscriptionObserver;
use App\Observers\SystemSettingObserver;
use App\Observers\UserObserver;
use App\Services\Billing\BillingService;
use App\Services\TranslationCacheService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerDisposableEmailFacadeCompatibilityAlias();

        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(BillingServiceInterface::class, BillingService::class);
        $this->app->singleton(ImpersonationManager::class);
        $this->app->scoped(DashboardCacheService::class);
        $this->app->singleton(TranslationCacheService::class, function (): TranslationCacheService {
            return new TranslationCacheService(
                cache()->store(),
                config('app.locale', 'en'),
                config('app.supported_locales', ['en' => 'EN', 'lt' => 'LT', 'ru' => 'RU']),
            );
        });
        $this->app->singleton(IntegrationProbeRegistry::class, function ($app): IntegrationProbeRegistry {
            return new IntegrationProbeRegistry([
                $app->make(DatabaseProbe::class),
                $app->make(QueueProbe::class),
                $app->make(MailProbe::class),
            ]);
        });

        $this->app->singleton(GlobalSearchRegistry::class, function ($app): GlobalSearchRegistry {
            return new GlobalSearchRegistry([
                $app->make(OrganizationSearchProvider::class),
                $app->make(BuildingSearchProvider::class),
                $app->make(PropertySearchProvider::class),
                $app->make(TenantSearchProvider::class),
                $app->make(InvoiceSearchProvider::class),
                $app->make(MeterReadingSearchProvider::class),
            ]);
        });
    }

    private function registerDisposableEmailFacadeCompatibilityAlias(): void
    {
        $expectedFacade = 'EragLaravelDisposableEmail\\Facades\\DisposableEmail';
        $actualFacade = 'EragLaravelDisposableEmail\\Facade\\DisposableEmail';

        if (! class_exists($expectedFacade) && class_exists($actualFacade)) {
            class_alias($actualFacade, $expectedFacade);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        $this->configureAuthRateLimiters();
        $this->configureSecurityRateLimiters();

        Organization::observe(OrganizationObserver::class);
        Subscription::observe(SubscriptionObserver::class);
        User::observe(UserObserver::class);
        SystemSetting::observe(SystemSettingObserver::class);
        PropertyAssignment::observe(PropertyAssignmentObserver::class);
    }

    private function configureAuthRateLimiters(): void
    {
        RateLimiter::for('auth-login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                'auth-login|'.$this->throttleKey($request),
            );
        });

        RateLimiter::for('password-reset-link', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                'password-reset-link|'.$this->throttleKey($request),
            );
        });

        RateLimiter::for('password-reset', function (Request $request): Limit {
            $token = Str::lower((string) $request->input('token'));

            return Limit::perMinute(5)->by(
                'password-reset|'.$this->throttleKey($request).'|'.$token,
            );
        });

        RateLimiter::for('auth-register', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                'auth-register|'.$this->throttleKey($request),
            );
        });
    }

    private function configureSecurityRateLimiters(): void
    {
        RateLimiter::for('security-csp-report', function (Request $request): Limit {
            return Limit::perMinute(10)->by(
                'security-csp-report|'.$request->ip(),
            );
        });
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email'))).'|'.$request->ip();
    }
}
