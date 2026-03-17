<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'role',
        'status',
        'locale',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    private const TENANT_WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'email',
        'role',
        'status',
        'locale',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
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
            'currentPropertyAssignment.property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
            'currentPropertyAssignment.property.building:id,organization_id,name,address_line_1,city',
        ]);
    }

    public function scopeWithTenantWorkspaceSummary(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::TENANT_WORKSPACE_COLUMNS)
            ->forOrganization($organizationId)
            ->tenants()
            ->withCurrentPropertySummary()
            ->orderedByName();
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

    public function platformNotificationDeliveries(): HasMany
    {
        return $this->hasMany(PlatformNotificationDelivery::class);
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
            ->withOrganizationSummary()
            ->orderedByName();
    }

    public function isSuperadmin(): bool
    {
        return $this->role === UserRole::SUPERADMIN || $this->is_super_admin;
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
        return $this->isAdminLike();
    }

    public function canBeDeletedFromSuperadmin(): bool
    {
        return ! $this->actorAuditLogs()
            ->select(['id', 'actor_user_id'])
            ->exists();
    }

    public function getCurrentPropertyAttribute(): ?Property
    {
        return $this->currentPropertyAssignment?->property;
    }
}
