<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\IntegrationHealthCheck;
use App\Models\Invoice;
use App\Models\Language;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\Property;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\BuildingPolicy;
use App\Policies\IntegrationHealthCheckPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LanguagePolicy;
use App\Policies\MeterPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\PlatformNotificationPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\SecurityViolationPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AuditLog::class => AuditLogPolicy::class,
        Building::class => BuildingPolicy::class,
        IntegrationHealthCheck::class => IntegrationHealthCheckPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Language::class => LanguagePolicy::class,
        Meter::class => MeterPolicy::class,
        Organization::class => OrganizationPolicy::class,
        PlatformNotification::class => PlatformNotificationPolicy::class,
        Property::class => PropertyPolicy::class,
        SecurityViolation::class => SecurityViolationPolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        SystemSetting::class => SystemSettingPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
