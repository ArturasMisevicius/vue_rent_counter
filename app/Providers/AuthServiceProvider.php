<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\IntegrationHealthCheck;
use App\Models\Language;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\IntegrationHealthCheckPolicy;
use App\Policies\LanguagePolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\PlatformNotificationPolicy;
use App\Policies\SecurityViolationPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(SystemSetting::class, SystemSettingPolicy::class);
        Gate::policy(PlatformNotification::class, PlatformNotificationPolicy::class);
        Gate::policy(Language::class, LanguagePolicy::class);
        Gate::policy(SecurityViolation::class, SecurityViolationPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(IntegrationHealthCheck::class, IntegrationHealthCheckPolicy::class);
    }
}
