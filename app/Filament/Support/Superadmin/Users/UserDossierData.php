<?php

declare(strict_types=1);

namespace App\Filament\Support\Superadmin\Users;

use App\Enums\AuditLogAction;
use App\Enums\DistributionMethod;
use App\Enums\IntegrationHealthStatus;
use App\Enums\InvoiceStatus;
use App\Enums\KycVerificationStatus;
use App\Enums\LanguageStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\OrganizationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PricingModel;
use App\Enums\ProjectCostRecordType;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectTeamRole;
use App\Enums\ProjectType;
use App\Enums\PropertyType;
use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Enums\ServiceType;
use App\Enums\SubscriptionAccessMode;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\SystemSettingCategory;
use App\Enums\TariffType;
use App\Enums\TariffZone;
use App\Enums\TenantStatus;
use App\Enums\UnitOfMeasurement;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WeekendLogic;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Filament\Support\Localization\LocalizedCodeLabel;
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
use BackedEnum;
use Filament\Support\Contracts\HasLabel;
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

    private const ENUM_CLASSES_BY_FIELD = [
        'access_mode' => [SubscriptionAccessMode::class],
        'action' => [AuditLogAction::class],
        'approval_status' => [KycVerificationStatus::class, MeterReadingValidationStatus::class],
        'category' => [SystemSettingCategory::class],
        'distribution_method' => [DistributionMethod::class],
        'duration' => [SubscriptionDuration::class],
        'health_status' => [IntegrationHealthStatus::class],
        'method' => [PaymentMethod::class],
        'payment_method' => [PaymentMethod::class],
        'period' => [SubscriptionDuration::class],
        'plan' => [SubscriptionPlan::class],
        'pricing_model' => [PricingModel::class],
        'priority' => [ProjectPriority::class],
        'role' => [ProjectTeamRole::class, UserRole::class],
        'severity' => [SecurityViolationSeverity::class],
        'status' => [
            UserStatus::class,
            OrganizationStatus::class,
            SubscriptionStatus::class,
            InvoiceStatus::class,
            ProjectStatus::class,
            MeterStatus::class,
            TenantStatus::class,
            LanguageStatus::class,
            KycVerificationStatus::class,
            MeterReadingValidationStatus::class,
            IntegrationHealthStatus::class,
        ],
        'submission_method' => [MeterReadingSubmissionMethod::class],
        'subscription_status' => [SubscriptionStatus::class],
        'tariff_type' => [TariffType::class],
        'type' => [
            ProjectType::class,
            MeterType::class,
            PropertyType::class,
            ServiceType::class,
            SecurityViolationType::class,
            ProjectCostRecordType::class,
            TariffType::class,
        ],
        'unit' => [UnitOfMeasurement::class],
        'unit_of_measurement' => [UnitOfMeasurement::class],
        'validation_status' => [MeterReadingValidationStatus::class],
        'verification_status' => [KycVerificationStatus::class],
        'weekend_logic' => [WeekendLogic::class],
        'zone' => [TariffZone::class],
    ];

    private const CODE_LABEL_PREFIXES_BY_FIELD = [
        'action' => [
            'superadmin.audit_logs.actions',
        ],
        'method' => [
            'superadmin.relation_resources.subscription_renewals.methods',
            'superadmin.relation_resources.invoice_payments.methods',
        ],
        'automation_level' => [
            'superadmin.relation_resources.subscription_renewals.methods',
        ],
        'status' => [
            'superadmin.relation_resources.invoice_email_logs.statuses',
        ],
    ];

    public function __construct(
        private readonly DatabaseContentLocalizer $databaseContentLocalizer,
    ) {}

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
            'locale' => $this->localizedScalarValue('locale', $user->locale) ?? $user->locale,
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

        return $this->normalizeModel($model);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeModel(Model $model): array
    {
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
            $records->map(fn (Model $record): array => $this->normalizeModel($record))->all(),
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
        $state = $this->normalizeKnownContent(Arr::except($state, ['password', 'remember_token']));

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

                continue;
            }

            if (is_scalar($value) && $this->isDateField($key) && filled((string) $value)) {
                $state[$key] = $this->dateTime($value);

                continue;
            }

            $localizedValue = $this->localizedScalarValue($key, $value);

            if ($localizedValue !== null) {
                $state[$key] = $localizedValue;

                continue;
            }

            $enumLabel = $this->enumLabel($key, $value);

            if ($enumLabel !== null) {
                $state[$key] = $enumLabel;
            }
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     * @return array<string, mixed>|array<int, mixed>
     */
    private function normalizeKnownContent(array $state): array
    {
        if ($this->looksLikeMeter($state) && is_scalar($state['name'] ?? null)) {
            $state['name'] = $this->databaseContentLocalizer->meterName(
                (string) $state['name'],
                is_scalar($state['type'] ?? null) ? (string) $state['type'] : null,
            );
        }

        if (
            $this->looksLikeMeterReading($state)
            && (is_string($state['notes'] ?? null) || ($state['notes'] ?? null) === null)
        ) {
            $state['notes'] = $this->databaseContentLocalizer->meterReadingNotes($state['notes'] ?? null);
        }

        if ($this->looksLikeBuilding($state) && is_scalar($state['name'] ?? null)) {
            $state['name'] = $this->databaseContentLocalizer->buildingName((string) $state['name']);
        }

        if ($this->looksLikeProperty($state) && is_scalar($state['name'] ?? null)) {
            $state['name'] = $this->databaseContentLocalizer->propertyName(
                (string) $state['name'],
                is_scalar($state['type'] ?? null) ? (string) $state['type'] : null,
                is_scalar($state['unit_number'] ?? null) ? (string) $state['unit_number'] : null,
            );
        }

        if ($this->looksLikeInvoice($state) && (is_string($state['notes'] ?? null) || ($state['notes'] ?? null) === null)) {
            $state['notes'] = $this->databaseContentLocalizer->invoiceNotes($state['notes'] ?? null);
        }

        if ($this->looksLikeBillingRecord($state) && (is_string($state['notes'] ?? null) || ($state['notes'] ?? null) === null)) {
            $state['notes'] = $this->databaseContentLocalizer->billingRecordNotes($state['notes'] ?? null);
        }

        if ($this->looksLikeProject($state)) {
            if (is_scalar($state['name'] ?? null)) {
                $state['name'] = $this->databaseContentLocalizer->projectName((string) $state['name']);
            }

            if (is_string($state['description'] ?? null) || ($state['description'] ?? null) === null) {
                $state['description'] = $this->databaseContentLocalizer->projectDescription($state['description'] ?? null);
            }
        }

        if ($this->looksLikeTask($state)) {
            if (is_scalar($state['title'] ?? null)) {
                $state['title'] = $this->databaseContentLocalizer->taskTitle((string) $state['title']);
            }

            if (is_string($state['description'] ?? null) || ($state['description'] ?? null) === null) {
                $state['description'] = $this->databaseContentLocalizer->taskDescription($state['description'] ?? null);
            }
        }

        if ($this->looksLikeTaskAssignment($state) && (is_string($state['notes'] ?? null) || ($state['notes'] ?? null) === null)) {
            $state['notes'] = $this->databaseContentLocalizer->taskAssignmentNotes($state['notes'] ?? null);
        }

        if ($this->looksLikeTimeEntry($state) && (is_string($state['description'] ?? null) || ($state['description'] ?? null) === null)) {
            $state['description'] = $this->databaseContentLocalizer->timeEntryDescription($state['description'] ?? null);
        }

        if ($this->looksLikeAttachment($state) && (is_string($state['description'] ?? null) || ($state['description'] ?? null) === null)) {
            $state['description'] = $this->databaseContentLocalizer->attachmentDescription($state['description'] ?? null);
        }

        if ($this->looksLikeComment($state) && (is_string($state['body'] ?? null) || ($state['body'] ?? null) === null)) {
            $state['body'] = $this->databaseContentLocalizer->commentBody($state['body'] ?? null);
        }

        if ($this->looksLikeSubscriptionRenewal($state) && (is_string($state['notes'] ?? null) || ($state['notes'] ?? null) === null)) {
            $state['notes'] = $this->databaseContentLocalizer->subscriptionRenewalNotes($state['notes'] ?? null);
        }

        if ($this->looksLikeAuditLog($state) && (is_string($state['description'] ?? null) || ($state['description'] ?? null) === null)) {
            $state['description'] = $this->databaseContentLocalizer->activityDescription($state['description'] ?? null);
        }

        if ($this->looksLikeUtilityService($state)) {
            if (is_scalar($state['name'] ?? null)) {
                $state['name'] = $this->databaseContentLocalizer->utilityServiceName(
                    (string) $state['name'],
                    is_scalar($state['service_type_bridge'] ?? null) ? (string) $state['service_type_bridge'] : null,
                );
            }

            if (is_string($state['description'] ?? null) || ($state['description'] ?? null) === null) {
                $state['description'] = $this->databaseContentLocalizer->utilityServiceDescription($state['description'] ?? null);
            }
        }

        if ($this->looksLikeSystemConfiguration($state) && (is_string($state['description'] ?? null) || ($state['description'] ?? null) === null)) {
            $state['description'] = $this->databaseContentLocalizer->systemConfigurationDescription($state['description'] ?? null);
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeMeter(array $state): bool
    {
        return array_key_exists('identifier', $state)
            && array_key_exists('type', $state)
            && array_key_exists('unit', $state)
            && array_key_exists('installed_at', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeMeterReading(array $state): bool
    {
        return array_key_exists('reading_value', $state)
            && array_key_exists('reading_date', $state)
            && array_key_exists('validation_status', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeBuilding(array $state): bool
    {
        return array_key_exists('address_line_1', $state)
            && array_key_exists('city', $state)
            && array_key_exists('country_code', $state)
            && array_key_exists('organization_id', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeProperty(array $state): bool
    {
        return array_key_exists('building_id', $state)
            && array_key_exists('unit_number', $state)
            && array_key_exists('floor_area_sqm', $state)
            && array_key_exists('type', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeInvoice(array $state): bool
    {
        return array_key_exists('invoice_number', $state)
            && array_key_exists('billing_period_start', $state)
            && array_key_exists('total_amount', $state)
            && array_key_exists('due_date', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeBillingRecord(array $state): bool
    {
        return array_key_exists('utility_service_id', $state)
            && array_key_exists('invoice_id', $state)
            && array_key_exists('consumption', $state)
            && array_key_exists('rate', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeProject(array $state): bool
    {
        return array_key_exists('name', $state)
            && array_key_exists('reference_number', $state)
            && array_key_exists('completion_percentage', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeTask(array $state): bool
    {
        return array_key_exists('title', $state)
            && array_key_exists('project_id', $state)
            && array_key_exists('estimated_hours', $state)
            && array_key_exists('actual_hours', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeTaskAssignment(array $state): bool
    {
        return array_key_exists('task_id', $state)
            && array_key_exists('user_id', $state)
            && array_key_exists('role', $state)
            && array_key_exists('assigned_at', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeTimeEntry(array $state): bool
    {
        return array_key_exists('task_id', $state)
            && array_key_exists('assignment_id', $state)
            && array_key_exists('hours', $state)
            && array_key_exists('logged_at', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeAttachment(array $state): bool
    {
        return array_key_exists('attachable_type', $state)
            && array_key_exists('attachable_id', $state)
            && array_key_exists('filename', $state)
            && array_key_exists('path', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeComment(array $state): bool
    {
        return array_key_exists('commentable_type', $state)
            && array_key_exists('commentable_id', $state)
            && array_key_exists('body', $state)
            && array_key_exists('user_id', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeSubscriptionRenewal(array $state): bool
    {
        return array_key_exists('subscription_id', $state)
            && array_key_exists('old_expires_at', $state)
            && array_key_exists('new_expires_at', $state)
            && array_key_exists('duration_days', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeAuditLog(array $state): bool
    {
        return array_key_exists('subject_type', $state)
            && array_key_exists('subject_id', $state)
            && array_key_exists('action', $state)
            && array_key_exists('occurred_at', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeUtilityService(array $state): bool
    {
        return array_key_exists('unit_of_measurement', $state)
            && array_key_exists('default_pricing_model', $state)
            && array_key_exists('service_type_bridge', $state)
            && array_key_exists('is_global_template', $state);
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $state
     */
    private function looksLikeSystemConfiguration(array $state): bool
    {
        return array_key_exists('key', $state)
            && array_key_exists('value', $state)
            && array_key_exists('type', $state)
            && array_key_exists('category', $state)
            && array_key_exists('default_value', $state);
    }

    private function localizedScalarValue(string $key, mixed $value): ?string
    {
        if (! is_scalar($value) || $value === '') {
            return null;
        }

        $field = LocalizedCodeLabel::segment($key);
        $rawValue = (string) $value;

        if ($field !== 'locale') {
            return null;
        }

        $locales = config('tenanto.locales', []);

        if (is_array($locales) && filled($locales[$rawValue] ?? null)) {
            return (string) $locales[$rawValue];
        }

        $supportedLocales = config('app.supported_locales', []);

        if (is_array($supportedLocales) && filled($supportedLocales[$rawValue] ?? null)) {
            return (string) $supportedLocales[$rawValue];
        }

        return null;
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
            return $value->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateTimeFormat());
        }

        return Carbon::parse((string) $value)
            ->locale(app()->getLocale())
            ->translatedFormat(LocalizedDateFormatter::dateTimeFormat());
    }

    private function isDateField(string $key): bool
    {
        return Str::endsWith($key, ['_at', '_date', '_on', 'period_start', 'period_end']);
    }

    private function enumLabel(string $key, mixed $value): ?string
    {
        if (! is_scalar($value) || $value === '') {
            return null;
        }

        $field = LocalizedCodeLabel::segment($key);
        $rawValue = (string) $value;

        foreach (self::ENUM_CLASSES_BY_FIELD[$field] ?? [] as $enumClass) {
            if (! is_a($enumClass, BackedEnum::class, true)) {
                continue;
            }

            /** @var class-string<BackedEnum> $enumClass */
            $case = $enumClass::tryFrom($rawValue);

            if ($case instanceof HasLabel) {
                return (string) $case->getLabel();
            }
        }

        foreach (self::CODE_LABEL_PREFIXES_BY_FIELD[$field] ?? [] as $prefix) {
            $translationKey = $prefix.'.'.LocalizedCodeLabel::segment($rawValue);

            if (trans()->has($translationKey)) {
                return __($translationKey);
            }
        }

        return null;
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
