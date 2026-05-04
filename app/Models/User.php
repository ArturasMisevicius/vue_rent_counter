<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Filament\Support\Workspace\WorkspaceResolver;
use Closure;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    private const CONTROL_PLANE_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'email',
        'phone',
        'avatar_disk',
        'avatar_path',
        'avatar_mime_type',
        'avatar_updated_at',
        'role',
        'status',
        'locale',
        'email_verified_at',
        'last_login_at',
        'suspended_at',
        'suspension_reason',
        'created_at',
        'updated_at',
    ];

    private const TENANT_WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'email',
        'phone',
        'avatar_disk',
        'avatar_path',
        'avatar_mime_type',
        'avatar_updated_at',
        'role',
        'status',
        'locale',
        'email_verified_at',
        'last_login_at',
        'suspended_at',
        'suspension_reason',
        'created_at',
        'updated_at',
    ];

    private const LOGIN_DEMO_COLUMNS = [
        'id',
        'name',
        'email',
        'role',
        'is_super_admin',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar_disk',
        'avatar_path',
        'avatar_mime_type',
        'avatar_updated_at',
        'role',
        'status',
        'locale',
        'organization_id',
        'last_login_at',
        'currency',
        'system_tenant_id',
        'is_super_admin',
        'suspended_at',
        'suspension_reason',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'avatar_updated_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_super_admin' => 'boolean',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeTenants(Builder $query): Builder
    {
        return $query->where('role', UserRole::TENANT);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    public function scopeAdminLike(Builder $query): Builder
    {
        return $query->whereIn('role', UserRole::adminLikeValues());
    }

    public function scopeOrderedByName(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeForLoginDemoTable(Builder $query): Builder
    {
        return $query
            ->select(self::LOGIN_DEMO_COLUMNS)
            ->where(function (Builder $query): void {
                $query
                    ->where('email', 'like', '%@example.com')
                    ->orWhere('email', 'like', '%@tenanto-demo.test');
            })
            ->orderByDesc('is_super_admin')
            ->orderedByName();
    }

    public function scopeWithOrganizationSummary(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
        ]);
    }

    public function scopeWithCurrentPropertySummary(Builder $query): Builder
    {
        return $query->with([
            'currentPropertyAssignment:id,organization_id,property_id,tenant_user_id,unit_area_sqm,assigned_at,unassigned_at',
            'currentPropertyAssignment.property:id,organization_id,building_id,name,floor,unit_number,type,floor_area_sqm',
            'currentPropertyAssignment.property.building:id,organization_id,name,address_line_1,city',
        ]);
    }

    public function scopeWithPaidInvoiceSummary(Builder $query): Builder
    {
        return $query->withSum([
            'invoices as tenant_total_paid_amount' => fn (Builder $invoiceQuery): Builder => $invoiceQuery->where('status', InvoiceStatus::PAID),
        ], 'amount_paid');
    }

    public function scopeWithTenantDeletionSummary(Builder $query): Builder
    {
        return $query->withExists([
            'invoices as tenant_delete_has_invoices',
        ]);
    }

    public function scopeWithOrganizationRosterSupportSummary(Builder $query): Builder
    {
        return $query->withExists([
            'ownedOrganization as roster_is_owner',
            'organizationInvitations as roster_has_unaccepted_invitation' => fn (Builder $invitationQuery): Builder => $invitationQuery
                ->whereColumn('organization_id', 'users.organization_id')
                ->whereColumn('role', 'users.role')
                ->whereNull('accepted_at'),
        ]);
    }

    public function scopeWithTenantWorkspaceSummary(Builder $query, int $organizationId): Builder
    {
        return $query
            ->withTenantResourceSummary()
            ->forOrganization($organizationId)
            ->orderedByName();
    }

    public function scopeForTenantControlPlane(Builder $query): Builder
    {
        return $query
            ->withTenantResourceSummary()
            ->withOrganizationSummary()
            ->orderedByName();
    }

    public function scopeWithTenantResourceSummary(Builder $query): Builder
    {
        return $query
            ->select(self::TENANT_WORKSPACE_COLUMNS)
            ->tenants()
            ->withCurrentPropertySummary()
            ->withPaidInvoiceSummary()
            ->withTenantDeletionSummary();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function systemTenant(): BelongsTo
    {
        return $this->belongsTo(SystemTenant::class);
    }

    public function ownedOrganization(): HasOne
    {
        return $this->hasOne(Organization::class, 'owner_user_id');
    }

    public function sentOrganizationInvitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'inviter_user_id');
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function managerPermissions(): HasMany
    {
        return $this->hasMany(ManagerPermission::class);
    }

    public function organizationInvitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'email', 'email');
    }

    public function invitedOrganizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class, 'invited_by');
    }

    public function propertyAssignments(): HasMany
    {
        return $this->hasMany(PropertyAssignment::class, 'tenant_user_id');
    }

    public function currentPropertyAssignment(): HasOne
    {
        return $this->hasOne(PropertyAssignment::class, 'tenant_user_id')
            ->current()
            ->latestAssignedFirst();
    }

    public function dashboardCustomization(): HasOne
    {
        return $this->hasOne(DashboardCustomization::class);
    }

    public function kycProfile(): HasOne
    {
        return $this->hasOne(UserKycProfile::class);
    }

    public function submittedMeterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class, 'submitted_by_user_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tenant_user_id');
    }

    public function tenantInvoices(): HasMany
    {
        return $this->invoices();
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class, 'tenant_user_id');
    }

    public function subscriptionRenewals(): HasMany
    {
        return $this->hasMany(SubscriptionRenewal::class);
    }

    public function createdSystemTenants(): HasMany
    {
        return $this->hasMany(SystemTenant::class, 'created_by_admin_id');
    }

    public function updatedSystemConfigurations(): HasMany
    {
        return $this->hasMany(SystemConfiguration::class, 'updated_by_admin_id');
    }

    public function sentPlatformOrganizationInvitations(): HasMany
    {
        return $this->hasMany(PlatformOrganizationInvitation::class, 'invited_by');
    }

    public function actorAuditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    public function organizationActivityLogs(): HasMany
    {
        return $this->hasMany(OrganizationActivityLog::class);
    }

    public function resourceActivityLogs(): HasMany
    {
        return $this->hasMany(OrganizationActivityLog::class, 'resource_id')
            ->where('resource_type', self::class);
    }

    public function currentPropertyMeters(): HasManyThrough
    {
        return $this->hasManyThrough(
            Meter::class,
            PropertyAssignment::class,
            'tenant_user_id',
            'property_id',
            'id',
            'property_id',
        )->whereNull('property_assignments.unassigned_at');
    }

    public function currentPropertyReadings(): HasManyThrough
    {
        return $this->hasManyThrough(
            MeterReading::class,
            PropertyAssignment::class,
            'tenant_user_id',
            'property_id',
            'id',
            'property_id',
        )->whereNull('property_assignments.unassigned_at');
    }

    public function superAdminAuditLogs(): HasMany
    {
        return $this->hasMany(SuperAdminAuditLog::class, 'admin_id');
    }

    public function securityViolations(): HasMany
    {
        return $this->hasMany(SecurityViolation::class);
    }

    public function blockedIpAddresses(): HasMany
    {
        return $this->hasMany(BlockedIpAddress::class, 'blocked_by_user_id');
    }

    public function scopeForSuperadminControlPlane(Builder $query): Builder
    {
        return $query
            ->select(self::CONTROL_PLANE_COLUMNS)
            ->withExists([
                'invoices as superadmin_delete_has_invoices',
                'ownedOrganization as superadmin_delete_has_owned_buildings' => fn (Builder $ownedOrganizationQuery): Builder => $ownedOrganizationQuery->whereHas(
                    'buildings',
                    fn (Builder $buildingQuery): Builder => $buildingQuery->select(['id', 'organization_id']),
                ),
                'actorAuditLogs as superadmin_delete_has_audit_logs',
                'currentPropertyAssignment as superadmin_delete_has_current_property_assignment',
                'leases as superadmin_delete_has_active_leases' => fn (Builder $leaseQuery): Builder => $leaseQuery->active(),
                'submittedMeterReadings as superadmin_delete_has_meter_readings',
                'ownedOrganization as superadmin_delete_has_owned_organization',
                'superAdminAuditLogs as superadmin_delete_has_superadmin_audit_logs',
            ])
            ->withOrganizationSummary()
            ->orderedByName();
    }

    public function isSuperadmin(): bool
    {
        return $this->role === UserRole::SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::MANAGER;
    }

    public function isTenant(): bool
    {
        return $this->role === UserRole::TENANT;
    }

    public function isAdminLike(): bool
    {
        return $this->isSuperadmin() || $this->isAdmin() || $this->isManager();
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
            && ($this->isAdminLike() || $this->isTenant());
    }

    public function canBeDeletedFromSuperadmin(): bool
    {
        return $this->superadminDeletionBlockedReason() === null;
    }

    public function currentOrganization(): ?Organization
    {
        $organizationId = app(WorkspaceResolver::class)->resolveFor($this)->organizationId;

        if ($organizationId === null) {
            return null;
        }

        if ($this->relationLoaded('organization') && $this->organization?->id === $organizationId) {
            return $this->organization;
        }

        return Organization::query()
            ->select([
                'id',
                'name',
                'slug',
                'status',
                'owner_user_id',
                'created_at',
                'updated_at',
            ])
            ->find($organizationId);
    }

    public function hasOrganizationRole(Organization|int|null $organization, UserRole $role): bool
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        if ($organizationId === null) {
            return false;
        }

        if ($this->organization_id === $organizationId && $this->role === $role) {
            return true;
        }

        return $this->organizationMemberships()
            ->active()
            ->where('organization_id', $organizationId)
            ->where('role', $role->value)
            ->exists();
    }

    public function canChangeRoleFromOrganizationRoster(): bool
    {
        if ($this->role === UserRole::SUPERADMIN) {
            return false;
        }

        $isOwner = $this->getAttribute('roster_is_owner');

        if ($isOwner !== null) {
            return ! (bool) $isOwner;
        }

        return ! $this->ownedOrganization()
            ->select(['id', 'owner_user_id'])
            ->exists();
    }

    public function canResendOrganizationInvitationFromRoster(): bool
    {
        if ($this->status !== UserStatus::INACTIVE || $this->role === UserRole::SUPERADMIN) {
            return false;
        }

        $hasInvitation = $this->getAttribute('roster_has_unaccepted_invitation');

        if ($hasInvitation !== null) {
            return (bool) $hasInvitation;
        }

        return $this->latestResendableOrganizationInvitation() instanceof OrganizationInvitation;
    }

    public function latestResendableOrganizationInvitation(): ?OrganizationInvitation
    {
        if (blank($this->organization_id)) {
            return null;
        }

        return $this->organizationInvitations()
            ->select([
                'id',
                'organization_id',
                'inviter_user_id',
                'email',
                'role',
                'full_name',
                'token',
                'expires_at',
                'accepted_at',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $this->organization_id)
            ->where('role', $this->role)
            ->whereNull('accepted_at')
            ->latest('expires_at')
            ->latest('id')
            ->first();
    }

    public function superadminDeletionBlockedReason(): ?string
    {
        $blockers = [];

        if ($this->hasSuperadminDeletionInvoiceBlocker()) {
            $blockers[] = __('superadmin.users.deletion_reasons.invoices');
        }

        if ($this->hasSuperadminDeletionBuildingBlocker()) {
            $blockers[] = __('superadmin.users.deletion_reasons.buildings');
        }

        if ($this->hasSuperadminDeletionActiveDataBlocker()) {
            $blockers[] = __('superadmin.users.deletion_reasons.active_records');
        }

        if ($blockers === []) {
            return null;
        }

        return __('superadmin.users.deletion_reasons.wrapper', [
            'reasons' => implode('; ', $blockers),
        ]);
    }

    public function getCurrentPropertyAttribute(): ?Property
    {
        return $this->currentPropertyAssignment?->property;
    }

    public function currentUnitAreaDisplay(): string
    {
        $unitArea = $this->currentPropertyAssignment?->unit_area_sqm;

        if ($unitArea === null) {
            return '—';
        }

        return $this->formatDecimal((float) $unitArea, 2).' m²';
    }

    public function totalPaidAmount(): float
    {
        $value = $this->getAttribute('tenant_total_paid_amount');

        if ($value !== null) {
            return (float) $value;
        }

        return (float) $this->invoices()
            ->select(['id', 'tenant_user_id', 'amount_paid', 'status'])
            ->where('status', InvoiceStatus::PAID)
            ->sum('amount_paid');
    }

    public function totalPaidDisplay(string $currency = 'EUR'): string
    {
        return EuMoneyFormatter::format($this->totalPaidAmount(), $currency);
    }

    private function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }

    public function canBeDeletedFromAdminWorkspace(): bool
    {
        $hasInvoices = $this->getAttribute('tenant_delete_has_invoices');

        if ($hasInvoices !== null) {
            return ! (bool) $hasInvoices;
        }

        return ! $this->invoices()
            ->select(['id', 'tenant_user_id'])
            ->exists();
    }

    public function adminDeletionBlockedReason(): ?string
    {
        return $this->canBeDeletedFromAdminWorkspace()
            ? null
            : __('admin.tenants.messages.delete_blocked');
    }

    private function hasSuperadminDeletionInvoiceBlocker(): bool
    {
        return $this->resolveSuperadminDeletionFlag(
            'superadmin_delete_has_invoices',
            fn (): bool => $this->invoices()
                ->select(['id', 'tenant_user_id'])
                ->exists(),
        );
    }

    private function hasSuperadminDeletionBuildingBlocker(): bool
    {
        return $this->resolveSuperadminDeletionFlag(
            'superadmin_delete_has_owned_buildings',
            fn (): bool => $this->ownedOrganization()
                ->whereHas(
                    'buildings',
                    fn (Builder $buildingQuery): Builder => $buildingQuery->select(['id', 'organization_id']),
                )
                ->select(['id', 'owner_user_id'])
                ->exists(),
        );
    }

    private function hasSuperadminDeletionActiveDataBlocker(): bool
    {
        return $this->resolveSuperadminDeletionFlag(
            'superadmin_delete_has_audit_logs',
            fn (): bool => $this->actorAuditLogs()
                ->select(['id', 'actor_user_id'])
                ->exists(),
        ) || $this->resolveSuperadminDeletionFlag(
            'superadmin_delete_has_current_property_assignment',
            fn (): bool => $this->currentPropertyAssignment()
                ->select(['id', 'tenant_user_id'])
                ->exists(),
        ) || $this->resolveSuperadminDeletionFlag(
            'superadmin_delete_has_active_leases',
            fn (): bool => $this->leases()
                ->select(['id', 'tenant_user_id'])
                ->active()
                ->exists(),
        ) || $this->resolveSuperadminDeletionFlag(
            'superadmin_delete_has_meter_readings',
            fn (): bool => $this->submittedMeterReadings()
                ->select(['id', 'submitted_by_user_id'])
                ->exists(),
        ) || (! $this->hasSuperadminDeletionBuildingBlocker() && $this->resolveSuperadminDeletionFlag(
            'superadmin_delete_has_owned_organization',
            fn (): bool => $this->ownedOrganization()
                ->select(['id', 'owner_user_id'])
                ->exists(),
        )) || $this->resolveSuperadminDeletionFlag(
            'superadmin_delete_has_superadmin_audit_logs',
            fn (): bool => $this->superAdminAuditLogs()
                ->select(['id', 'admin_id'])
                ->exists(),
        );
    }

    private function resolveSuperadminDeletionFlag(string $attribute, Closure $resolver): bool
    {
        $value = $this->getAttribute($attribute);

        if ($value !== null) {
            return (bool) $value;
        }

        return $resolver();
    }
}
