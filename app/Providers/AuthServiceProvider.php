<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\IntegrationHealthCheck;
use App\Models\Invoice;
use App\Models\Language;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Provider;
use App\Models\SecurityViolation;
use App\Models\ServiceConfiguration;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\Tag;
use App\Models\Tariff;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Models\UtilityService;
use App\Policies\AuditLogPolicy;
use App\Policies\BuildingPolicy;
use App\Policies\IntegrationHealthCheckPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LanguagePolicy;
use App\Policies\MeterPolicy;
use App\Policies\MeterReadingPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\OrganizationUserPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\PropertyAssignmentPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\ProviderPolicy;
use App\Policies\SecurityViolationPolicy;
use App\Policies\ServiceConfigurationPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\TagPolicy;
use App\Policies\TariffPolicy;
use App\Policies\TaskAssignmentPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\Policies\UtilityServicePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        MeterReading::class => MeterReadingPolicy::class,
        Organization::class => OrganizationPolicy::class,
        OrganizationUser::class => OrganizationUserPolicy::class,
        Project::class => ProjectPolicy::class,
        PropertyAssignment::class => PropertyAssignmentPolicy::class,
        Provider::class => ProviderPolicy::class,
        Property::class => PropertyPolicy::class,
        SecurityViolation::class => SecurityViolationPolicy::class,
        ServiceConfiguration::class => ServiceConfigurationPolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        SystemSetting::class => SystemSettingPolicy::class,
        Tag::class => TagPolicy::class,
        Tariff::class => TariffPolicy::class,
        Task::class => TaskPolicy::class,
        TaskAssignment::class => TaskAssignmentPolicy::class,
        User::class => UserPolicy::class,
        UtilityService::class => UtilityServicePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user): ?bool {
            if ($user->isSuperadmin()) {
                return true;
            }

            return null;
        });
    }
}
