<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Observers\OrganizationObserver;
use App\Observers\PlatformNotificationObserver;
use App\Observers\SubscriptionObserver;
use App\Observers\SystemSettingObserver;
use App\Observers\UserObserver;
use App\Support\Audit\AuditLogger;
use App\Support\Auth\ImpersonationManager;
use App\Support\Shell\Search\GlobalSearchRegistry;
use App\Support\Shell\Search\Providers\OrganizationSearchProvider;
use App\Support\Shell\Search\Providers\UserSearchProvider;
use App\Support\Superadmin\Integration\IntegrationProbeRegistry;
use App\Support\Superadmin\Integration\Probes\DatabaseProbe;
use App\Support\Superadmin\Integration\Probes\MailProbe;
use App\Support\Superadmin\Integration\Probes\QueueProbe;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(ImpersonationManager::class);
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
                $app->make(UserSearchProvider::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Organization::observe(OrganizationObserver::class);
        Subscription::observe(SubscriptionObserver::class);
        User::observe(UserObserver::class);
        SystemSetting::observe(SystemSettingObserver::class);
        PlatformNotification::observe(PlatformNotificationObserver::class);
    }
}
