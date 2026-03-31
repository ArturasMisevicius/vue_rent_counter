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
            ['label' => 'Role', 'value' => $user->role instanceof UserRole ? $user->role->label() : (string) $user->role],
            ['label' => 'Status', 'value' => $user->status instanceof UserStatus ? $user->status->label() : (string) $user->status],
            ['label' => 'Memberships', 'value' => (string) $user->getAttribute('organization_memberships_count')],
            ['label' => 'Invitations', 'value' => (string) $user->getAttribute('organization_invitations_count')],
            ['label' => 'Assignments', 'value' => (string) $user->getAttribute('property_assignments_count')],
            ['label' => 'Invoices', 'value' => (string) $user->getAttribute('invoices_count')],
            ['label' => 'Leases', 'value' => (string) $user->getAttribute('leases_count')],
            ['label' => 'Meter Readings', 'value' => (string) $user->getAttribute('submitted_meter_readings_count')],
            ['label' => 'Security Violations', 'value' => (string) $user->getAttribute('security_violations_count')],
            ['label' => 'Audit Logs', 'value' => (string) $user->getAttribute('actor_audit_logs_count')],
            ['label' => 'Org Activity Logs', 'value' => (string) $user->getAttribute('organization_activity_logs_count')],
            ['label' => 'Superadmin Logs', 'value' => (string) $user->getAttribute('super_admin_audit_logs_count')],
        ];
    }

    /**
     * @return list<array{title: string, count: int|null, empty: string, data: array<mixed>|null}>
     */
    private function sections(User $user): array
    {
        return [
            $this->section('Account', $this->accountData($user), 'No account information available.'),
            $this->section('Primary Organization', $this->modelData($user->organization), 'No primary organization assigned.'),
            $this->section('Owned Organization', $this->modelData($user->ownedOrganization), 'This user does not own an organization.'),
            $this->section('System Tenant', $this->modelData($user->systemTenant), 'No system tenant assigned.'),
            $this->section('Dashboard Customization', $this->modelData($user->dashboardCustomization), 'No dashboard customization saved.'),
            $this->section('KYC Profile', $this->modelData($user->kycProfile), 'No KYC profile on file.'),
            $this->collectionSection('Organization Memberships', $user->organizationMemberships, 'No organization memberships.'),
            $this->collectionSection('Manager Permissions', $user->managerPermissions, 'No manager permissions configured.'),
            $this->collectionSection('Organization Invitations For This Email', $user->organizationInvitations, 'No organization invitations linked to this email.'),
            $this->collectionSection('Organization Invitations Sent By User', $user->sentOrganizationInvitations, 'This user has not sent any organization invitations.'),
            $this->collectionSection('Organization Memberships Invited By User', $user->invitedOrganizationMemberships, 'This user has not invited any memberships.'),
            $this->collectionSection('Property Assignments', $user->propertyAssignments, 'No property assignments recorded.'),
            $this->section('Current Property Assignment', $this->modelData($user->currentPropertyAssignment), 'No current property assignment.'),
            $this->collectionSection('Current Property Meters', $user->currentPropertyMeters, 'No current property meters found.'),
            $this->collectionSection('Current Property Readings', $user->currentPropertyReadings, 'No current property readings found.'),
            $this->collectionSection('Submitted Meter Readings', $user->submittedMeterReadings, 'No meter readings submitted by this user.'),
            $this->collectionSection('Invoices', $user->invoices, 'No invoices linked to this user.'),
            $this->collectionSection('Leases', $user->leases, 'No leases linked to this user.'),
            $this->collectionSection('Subscription Renewals', $user->subscriptionRenewals, 'No subscription renewals linked to this user.'),
            $this->collectionSection('Created System Tenants', $user->createdSystemTenants, 'This user has not created any system tenants.'),
            $this->collectionSection('Updated System Configurations', $user->updatedSystemConfigurations, 'This user has not updated any system configurations.'),
            $this->collectionSection('Platform Organization Invitations', $user->sentPlatformOrganizationInvitations, 'This user has not sent any platform invitations.'),
            $this->collectionSection('Audit Logs As Actor', $user->actorAuditLogs, 'No audit logs recorded for this user as actor.'),
            $this->collectionSection('Organization Activity Logs', $user->organizationActivityLogs, 'No organization activity logs recorded for this user.'),
            $this->collectionSection('Resource Activity Logs', $user->resourceActivityLogs, 'No resource activity logs recorded for this user.'),
            $this->collectionSection('Superadmin Audit Logs', $user->superAdminAuditLogs, 'No superadmin audit logs recorded for this user.'),
            $this->collectionSection('Security Violations', $user->securityViolations, 'No security violations recorded for this user.'),
            $this->collectionSection('Blocked IP Addresses', $user->blockedIpAddresses, 'No blocked IP addresses recorded for this user.'),
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
        return $value ? 'Yes' : 'No';
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
