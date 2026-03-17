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
use App\Support\Shell\DashboardUrlResolver;
use App\Support\Shell\Navigation\NavigationBuilder;
use App\Support\Shell\Search\GlobalSearchRegistry;
use App\Support\Shell\Search\Providers\OrganizationSearchProvider;
use App\Support\Shell\Search\Providers\UserSearchProvider;
use App\Support\Shell\UserAvatarColor;
use App\Support\Superadmin\Exports\NullOrganizationDataExportBuilder;
use App\Support\Superadmin\Exports\OrganizationDataExportBuilder;
use App\Support\Superadmin\Usage\NullOrganizationUsageReader;
use App\Support\Superadmin\Usage\OrganizationUsageReader;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(DashboardUrlResolver::class);
        $this->app->singleton(NavigationBuilder::class);
        $this->app->singleton(UserAvatarColor::class);
        $this->app->singleton(ImpersonationManager::class);
        $this->app->singleton(OrganizationSearchProvider::class);
        $this->app->singleton(UserSearchProvider::class);
        $this->app->singleton(OrganizationDataExportBuilder::class, NullOrganizationDataExportBuilder::class);
        $this->app->singleton(OrganizationUsageReader::class, NullOrganizationUsageReader::class);
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
