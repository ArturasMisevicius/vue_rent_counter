<?php

declare(strict_types=1);

namespace App\Filament\Support\Superadmin\Users;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Users\UserResource;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BlockedIpAddress;
use App\Models\Building;
use App\Models\DashboardCustomization;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\ManagerPermission;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\PlatformOrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use App\Models\SuperAdminAuditLog;
use App\Models\SystemConfiguration;
use App\Models\SystemTenant;
use App\Models\User;
use App\Models\UserKycProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UserDossierData
{
    private const USER_COLUMNS = [
        'id',
        'organization_id',
        'system_tenant_id',
        'name',
        'email',
        'phone',
        'role',
        'status',
        'locale',
        'currency',
        'email_verified_at',
        'last_login_at',
        'is_super_admin',
        'suspended_at',
        'suspension_reason',
        'created_at',
        'updated_at',
    ];

    private const SUMMARY_COUNTS = [
        'organizationMemberships',
        'managerPermissions',
        'organizationInvitations',
        'sentOrganizationInvitations',
        'propertyAssignments',
        'submittedMeterReadings',
        'invoices',
        'leases',
        'subscriptionRenewals',
        'securityViolations',
        'blockedIpAddresses',
        'actorAuditLogs',
        'organizationActivityLogs',
        'superAdminAuditLogs',
    ];

    public function resolve(int|string $key): User
    {
        return UserResource::getEloquentQuery()
            ->select(self::USER_COLUMNS)
            ->with($this->relationships())
            ->withCount(self::SUMMARY_COUNTS)
            ->whereKey($key)
            ->firstOrFail();
    }

    /**
     * @return array{
     *     summary: list<array{label: string, value: string}>,
     *     sections: list<array{title: string, count: int|null, empty: string, data: array<mixed>|null}>
     * }
     */
    public function for(User $user): array
    {
        $user->loadMissing($this->relationships());

        $missingCounts = collect(self::SUMMARY_COUNTS)
            ->filter(fn (string $relation): bool => $user->getAttribute(Str::snake($relation).'_count') === null)
            ->values()
            ->all();

        if ($missingCounts !== []) {
            $user->loadCount($missingCounts);
        }

        return [
            'summary' => $this->summary($user),
            'sections' => $this->sections($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function relationships(): array
    {
        return [
            'organization' => fn ($query) => $query->select($this->modelColumns(Organization::class)),
            'systemTenant' => fn ($query) => $query
                ->select($this->modelColumns(SystemTenant::class))
                ->with([
                    'createdByAdmin' => fn ($adminQuery) => $adminQuery->select(self::USER_COLUMNS),
                ]),
            'ownedOrganization' => fn ($query) => $query->select($this->modelColumns(Organization::class)),
            'sentOrganizationInvitations' => fn ($query) => $query
                ->select($this->modelColumns(OrganizationInvitation::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                ])
                ->latest('created_at'),
            'organizationMemberships' => fn ($query) => $query
                ->select($this->modelColumns(OrganizationUser::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                    'inviter' => fn ($inviterQuery) => $inviterQuery->select(self::USER_COLUMNS),
                ])
                ->latest('joined_at'),
            'managerPermissions' => fn ($query) => $query
                ->select($this->modelColumns(ManagerPermission::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                ])
                ->orderBy('resource')
                ->orderBy('id'),
            'organizationInvitations' => fn ($query) => $query
                ->select($this->modelColumns(OrganizationInvitation::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                    'inviter' => fn ($inviterQuery) => $inviterQuery->select(self::USER_COLUMNS),
                ])
                ->latest('created_at'),
            'invitedOrganizationMemberships' => fn ($query) => $query
                ->select($this->modelColumns(OrganizationUser::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                    'user' => fn ($userQuery) => $userQuery->select(self::USER_COLUMNS),
                ])
                ->latest('created_at'),
            'propertyAssignments' => fn ($query) => $query
                ->select($this->modelColumns(PropertyAssignment::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                    'property' => fn ($propertyQuery) => $propertyQuery
                        ->select($this->modelColumns(Property::class))
                        ->with([
                            'building' => fn ($buildingQuery) => $buildingQuery->select($this->modelColumns(Building::class)),
                        ]),
                ])
                ->latest('assigned_at'),
            'currentPropertyAssignment' => fn ($query) => $query
                ->select($this->modelColumns(PropertyAssignment::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                    'property' => fn ($propertyQuery) => $propertyQuery
                        ->select($this->modelColumns(Property::class))
                        ->with([
                            'building' => fn ($buildingQuery) => $buildingQuery->select($this->modelColumns(Building::class)),
                        ]),
                ]),
            'dashboardCustomization' => fn ($query) => $query->select($this->modelColumns(DashboardCustomization::class)),
            'kycProfile' => fn ($query) => $query
                ->select($this->modelColumns(UserKycProfile::class))
                ->with([
                    'reviewedBy' => fn ($reviewerQuery) => $reviewerQuery->select(self::USER_COLUMNS),
                    'attachments' => fn ($attachmentQuery) => $attachmentQuery
                        ->select($this->modelColumns(Attachment::class))
                        ->with([
                            'uploader' => fn ($uploaderQuery) => $uploaderQuery->select(self::USER_COLUMNS),
                        ]),
                ]),
            'submittedMeterReadings' => fn ($query) => $query
                ->select($this->modelColumns(MeterReading::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                    'meter' => fn ($meterQuery) => $meterQuery->select($this->modelColumns(Meter::class)),
                    'property' => fn ($propertyQuery) => $propertyQuery
                        ->select($this->modelColumns(Property::class))
                        ->with([
                            'building' => fn ($buildingQuery) => $buildingQuery->select($this->modelColumns(Building::class)),
                        ]),
                ])
                ->latest('reading_date'),
            'invoices' => fn ($query) => $query
                ->select($this->modelColumns(Invoice::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                    'property' => fn ($propertyQuery) => $propertyQuery
                        ->select($this->modelColumns(Property::class))
                        ->with([
                            'building' => fn ($buildingQuery) => $buildingQuery->select($this->modelColumns(Building::class)),
                        ]),
                ])
                ->latest('billing_period_start'),
            'leases' => fn ($query) => $query
                ->select($this->modelColumns(Lease::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                    'property' => fn ($propertyQuery) => $propertyQuery
                        ->select($this->modelColumns(Property::class))
                        ->with([
                            'building' => fn ($buildingQuery) => $buildingQuery->select($this->modelColumns(Building::class)),
                        ]),
                ])
                ->latest('start_date'),
            'subscriptionRenewals' => fn ($query) => $query
                ->select($this->modelColumns(SubscriptionRenewal::class))
                ->with([
                    'subscription' => fn ($subscriptionQuery) => $subscriptionQuery->select($this->modelColumns(Subscription::class)),
                    'user' => fn ($userQuery) => $userQuery->select(self::USER_COLUMNS),
                ])
                ->latest('created_at'),
            'createdSystemTenants' => fn ($query) => $query
                ->select($this->modelColumns(SystemTenant::class))
                ->latest('created_at'),
            'updatedSystemConfigurations' => fn ($query) => $query
                ->select($this->modelColumns(SystemConfiguration::class))
                ->latest('updated_at'),
            'sentPlatformOrganizationInvitations' => fn ($query) => $query
                ->select($this->modelColumns(PlatformOrganizationInvitation::class))
                ->latest('created_at'),
            'actorAuditLogs' => fn ($query) => $query
                ->select($this->modelColumns(AuditLog::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                ])
                ->latest('created_at'),
            'organizationActivityLogs' => fn ($query) => $query
                ->select($this->modelColumns(OrganizationActivityLog::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                ])
                ->latest('created_at'),
            'resourceActivityLogs' => fn ($query) => $query
                ->select($this->modelColumns(OrganizationActivityLog::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                ])
                ->latest('created_at'),
            'currentPropertyMeters' => fn ($query) => $query
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                    'property' => fn ($propertyQuery) => $propertyQuery
                        ->select($this->modelColumns(Property::class))
                        ->with([
                            'building' => fn ($buildingQuery) => $buildingQuery->select($this->modelColumns(Building::class)),
                        ]),
                ])
                ->orderBy('name')
                ->orderBy('id'),
            'currentPropertyReadings' => fn ($query) => $query
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                    'meter' => fn ($meterQuery) => $meterQuery->select($this->modelColumns(Meter::class)),
                    'property' => fn ($propertyQuery) => $propertyQuery
                        ->select($this->modelColumns(Property::class))
                        ->with([
                            'building' => fn ($buildingQuery) => $buildingQuery->select($this->modelColumns(Building::class)),
                        ]),
                ])
                ->latest('reading_date'),
            'superAdminAuditLogs' => fn ($query) => $query
                ->select($this->modelColumns(SuperAdminAuditLog::class))
                ->with([
                    'systemTenant' => fn ($systemTenantQuery) => $systemTenantQuery->select($this->modelColumns(SystemTenant::class)),
                ])
                ->latest('created_at'),
            'securityViolations' => fn ($query) => $query
                ->select($this->modelColumns(SecurityViolation::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                ])
                ->latest('occurred_at'),
            'blockedIpAddresses' => fn ($query) => $query
                ->select($this->modelColumns(BlockedIpAddress::class))
                ->with([
                    'organization' => fn ($organizationQuery) => $organizationQuery->select($this->modelColumns(Organization::class)),
                ])
                ->latest('created_at'),
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function summary(User $user): array
    {
        return [
            ['label' => __('superadmin.users.dossier.summary.role'), 'value' => $user->role instanceof UserRole ? $user->role->label() : (string) $user->role],
            ['label' => __('superadmin.users.dossier.summary.status'), 'value' => $user->status instanceof UserStatus ? $user->status->label() : (string) $user->status],
            ['label' => __('superadmin.users.dossier.summary.memberships'), 'value' => (string) $user->getAttribute('organization_memberships_count')],
            ['label' => __('superadmin.users.dossier.summary.invitations'), 'value' => (string) $user->getAttribute('organization_invitations_count')],
            ['label' => __('superadmin.users.dossier.summary.assignments'), 'value' => (string) $user->getAttribute('property_assignments_count')],
            ['label' => __('superadmin.users.dossier.summary.invoices'), 'value' => (string) $user->getAttribute('invoices_count')],
            ['label' => __('superadmin.users.dossier.summary.leases'), 'value' => (string) $user->getAttribute('leases_count')],
            ['label' => __('superadmin.users.dossier.summary.meter_readings'), 'value' => (string) $user->getAttribute('submitted_meter_readings_count')],
            ['label' => __('superadmin.users.dossier.summary.security_violations'), 'value' => (string) $user->getAttribute('security_violations_count')],
            ['label' => __('superadmin.users.dossier.summary.audit_logs'), 'value' => (string) $user->getAttribute('actor_audit_logs_count')],
            ['label' => __('superadmin.users.dossier.summary.organization_activity_logs'), 'value' => (string) $user->getAttribute('organization_activity_logs_count')],
            ['label' => __('superadmin.users.dossier.summary.superadmin_logs'), 'value' => (string) $user->getAttribute('super_admin_audit_logs_count')],
        ];
    }

    /**
     * @return list<array{title: string, count: int|null, empty: string, data: array<mixed>|null}>
     */
    private function sections(User $user): array
    {
        return [
            $this->section(__('superadmin.users.dossier.sections.account'), $this->accountData($user), __('superadmin.users.dossier.empty.account')),
            $this->section(__('superadmin.users.dossier.sections.primary_organization'), $this->modelData($user->organization), __('superadmin.users.dossier.empty.primary_organization')),
            $this->section(__('superadmin.users.dossier.sections.owned_organization'), $this->modelData($user->ownedOrganization), __('superadmin.users.dossier.empty.owned_organization')),
            $this->section(__('superadmin.users.dossier.sections.system_tenant'), $this->modelData($user->systemTenant), __('superadmin.users.dossier.empty.system_tenant')),
            $this->section(__('superadmin.users.dossier.sections.dashboard_customization'), $this->modelData($user->dashboardCustomization), __('superadmin.users.dossier.empty.dashboard_customization')),
            $this->section(__('superadmin.users.dossier.sections.kyc_profile'), $this->modelData($user->kycProfile), __('superadmin.users.dossier.empty.kyc_profile')),
            $this->collectionSection(__('superadmin.users.dossier.sections.organization_memberships'), $user->organizationMemberships, __('superadmin.users.dossier.empty.organization_memberships')),
            $this->collectionSection(__('superadmin.users.dossier.sections.manager_permissions'), $user->managerPermissions, __('superadmin.users.dossier.empty.manager_permissions')),
            $this->collectionSection(__('superadmin.users.dossier.sections.organization_invitations_for_email'), $user->organizationInvitations, __('superadmin.users.dossier.empty.organization_invitations_for_email')),
            $this->collectionSection(__('superadmin.users.dossier.sections.organization_invitations_sent'), $user->sentOrganizationInvitations, __('superadmin.users.dossier.empty.organization_invitations_sent')),
            $this->collectionSection(__('superadmin.users.dossier.sections.organization_memberships_invited'), $user->invitedOrganizationMemberships, __('superadmin.users.dossier.empty.organization_memberships_invited')),
            $this->collectionSection(__('superadmin.users.dossier.sections.property_assignments'), $user->propertyAssignments, __('superadmin.users.dossier.empty.property_assignments')),
            $this->section(__('superadmin.users.dossier.sections.current_property_assignment'), $this->modelData($user->currentPropertyAssignment), __('superadmin.users.dossier.empty.current_property_assignment')),
            $this->collectionSection(__('superadmin.users.dossier.sections.current_property_meters'), $user->currentPropertyMeters, __('superadmin.users.dossier.empty.current_property_meters')),
            $this->collectionSection(__('superadmin.users.dossier.sections.current_property_readings'), $user->currentPropertyReadings, __('superadmin.users.dossier.empty.current_property_readings')),
            $this->collectionSection(__('superadmin.users.dossier.sections.submitted_meter_readings'), $user->submittedMeterReadings, __('superadmin.users.dossier.empty.submitted_meter_readings')),
            $this->collectionSection(__('superadmin.users.dossier.sections.invoices'), $user->invoices, __('superadmin.users.dossier.empty.invoices')),
            $this->collectionSection(__('superadmin.users.dossier.sections.leases'), $user->leases, __('superadmin.users.dossier.empty.leases')),
            $this->collectionSection(__('superadmin.users.dossier.sections.subscription_renewals'), $user->subscriptionRenewals, __('superadmin.users.dossier.empty.subscription_renewals')),
            $this->collectionSection(__('superadmin.users.dossier.sections.created_system_tenants'), $user->createdSystemTenants, __('superadmin.users.dossier.empty.created_system_tenants')),
            $this->collectionSection(__('superadmin.users.dossier.sections.updated_system_configurations'), $user->updatedSystemConfigurations, __('superadmin.users.dossier.empty.updated_system_configurations')),
            $this->collectionSection(__('superadmin.users.dossier.sections.platform_organization_invitations'), $user->sentPlatformOrganizationInvitations, __('superadmin.users.dossier.empty.platform_organization_invitations')),
            $this->collectionSection(__('superadmin.users.dossier.sections.audit_logs_as_actor'), $user->actorAuditLogs, __('superadmin.users.dossier.empty.audit_logs_as_actor')),
            $this->collectionSection(__('superadmin.users.dossier.sections.organization_activity_logs'), $user->organizationActivityLogs, __('superadmin.users.dossier.empty.organization_activity_logs')),
            $this->collectionSection(__('superadmin.users.dossier.sections.resource_activity_logs'), $user->resourceActivityLogs, __('superadmin.users.dossier.empty.resource_activity_logs')),
            $this->collectionSection(__('superadmin.users.dossier.sections.superadmin_audit_logs'), $user->superAdminAuditLogs, __('superadmin.users.dossier.empty.superadmin_audit_logs')),
            $this->collectionSection(__('superadmin.users.dossier.sections.security_violations'), $user->securityViolations, __('superadmin.users.dossier.empty.security_violations')),
            $this->collectionSection(__('superadmin.users.dossier.sections.blocked_ip_addresses'), $user->blockedIpAddresses, __('superadmin.users.dossier.empty.blocked_ip_addresses')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function accountData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role instanceof UserRole ? $user->role->label() : $user->role,
            'status' => $user->status instanceof UserStatus ? $user->status->label() : $user->status,
            'organization_id' => $user->organization_id,
            'system_tenant_id' => $user->system_tenant_id,
            'locale' => $user->locale,
            'currency' => $user->currency,
            'email_verified_at' => $this->dateTime($user->email_verified_at),
            'last_login_at' => $this->dateTime($user->last_login_at),
            'is_super_admin' => $this->bool($user->is_super_admin),
            'suspended_at' => $this->dateTime($user->suspended_at),
            'suspension_reason' => $user->suspension_reason,
            'created_at' => $this->dateTime($user->created_at),
            'updated_at' => $this->dateTime($user->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function modelData(?Model $model): ?array
    {
        if (! $model instanceof Model) {
            return null;
        }

        return $this->normalizeState($model->toArray());
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return array{title: string, count: int|null, empty: string, data: array<mixed>|null}
     */
    private function collectionSection(string $title, Collection $records, string $empty): array
    {
        return $this->section(
            $title,
            $records->map(fn (Model $record): array => $this->normalizeState($record->toArray()))->all(),
            $empty,
            $records->count(),
        );
    }

    /**
     * @param  array<mixed>|null  $data
     * @return array{title: string, count: int|null, empty: string, data: array<mixed>|null}
     */
    private function section(string $title, ?array $data, string $empty, ?int $count = null): array
    {
        return [
            'title' => $title,
            'count' => $count,
            'empty' => $empty,
            'data' => $data === [] ? null : $data,
        ];
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     * @return array<string, mixed>|array<int, mixed>
     */
    private function normalizeState(array $state): array
    {
        $state = Arr::except($state, ['password', 'remember_token']);

        foreach ($state as $key => $value) {
            if (is_array($value)) {
                $state[$key] = $this->normalizeState($value);

                continue;
            }

            if ($value instanceof Model) {
                $state[$key] = $this->normalizeState($value->toArray());

                continue;
            }

            if ($value instanceof Collection) {
                $state[$key] = $value
                    ->map(fn (mixed $item): mixed => $item instanceof Model ? $this->normalizeState($item->toArray()) : $item)
                    ->all();

                continue;
            }

            if (is_bool($value)) {
                $state[$key] = $this->bool($value);
            }
        }

        return $state;
    }

    private function bool(bool $value): string
    {
        return $value ? __('superadmin.users.dossier.values.yes') : __('superadmin.users.dossier.values.no');
    }

    private function dateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->locale(app()->getLocale())->isoFormat('LLL');
        }

        return Carbon::parse((string) $value)
            ->locale(app()->getLocale())
            ->isoFormat('LLL');
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return list<string>
     */
    private function modelColumns(string $modelClass): array
    {
        if ($modelClass === User::class) {
            return self::USER_COLUMNS;
        }

        /** @var Model $model */
        $model = new $modelClass;

        return collect(['id', ...$model->getFillable(), 'created_at', 'updated_at'])
            ->filter(fn (mixed $column): bool => is_string($column) && $column !== '')
            ->unique()
            ->values()
            ->all();
    }
}
