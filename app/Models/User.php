<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model - Hierarchical User Management
 * 
 * Represents users in the three-tier hierarchical system:
 * - Superadmin: System owner with full access across all organizations
 * - Admin: Property owner managing their portfolio within tenant_id scope
 * - Tenant: Apartment resident with access limited to their assigned property
 * 
 * **Superadmin Role**:
 * - Purpose: Manages the entire system across all organizations
 * - Access: Full system access without restrictions (bypasses tenant scope)
 * - Permissions: Create/manage Admin accounts, manage subscriptions, view system-wide statistics
 * - tenant_id: null (no tenant isolation)
 * 
 * **Admin Role**:
 * - Purpose: Manages property portfolio and tenant accounts
 * - Access: Limited to their own tenant_id scope (data isolation)
 * - Permissions: Create/manage buildings, properties, tenants, meters, readings, invoices
 * - Subscription: Requires active subscription with limits on properties and tenants
 * - tenant_id: Unique identifier for organization
 * 
 * **Tenant Role**:
 * - Purpose: View billing information and submit meter readings for their apartment
 * - Access: Limited to their assigned property only (property_id scope)
 * - Permissions: View property details, meters, consumption, invoices; submit readings
 * - Account Creation: Created by Admin and linked to specific property
 * - tenant_id: Inherited from Admin; property_id: Assigned property
 * 
 * @property int $id
 * @property int|null $tenant_id Organization identifier for data isolation (null for Superadmin)
 * @property int|null $property_id Assigned property for Tenant role
 * @property int|null $parent_user_id Admin who created this user (for Tenant role)
 * @property string $name User's full name
 * @property string $email Unique email address
 * @property string $password Hashed password
 * @property UserRole $role User role (superadmin, admin, manager, tenant)
 * @property bool $is_active Account activation status
 * @property string|null $organization_name Organization name (for Admin role)
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read Property|null $property Assigned property (for Tenant role)
 * @property-read User|null $parentUser Admin who created this user
 * @property-read \Illuminate\Database\Eloquent\Collection|User[] $childUsers Tenants created by this Admin
 * @property-read Subscription|null $subscription Subscription (for Admin role)
 * @property-read \Illuminate\Database\Eloquent\Collection|Property[] $properties Properties managed by this Admin
 * @property-read \Illuminate\Database\Eloquent\Collection|Building[] $buildings Buildings managed by this Admin
 * @property-read \Illuminate\Database\Eloquent\Collection|Invoice[] $invoices Invoices for this Admin's organization
 * @property-read \Illuminate\Database\Eloquent\Collection|MeterReading[] $meterReadings Meter readings entered by this user
 * 
 * @see \App\Enums\UserRole
 * @see \App\Models\Subscription
 * @see \App\Services\AccountManagementService
 * @see \App\Scopes\HierarchicalScope
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles {
        HasRoles::bootHasRoles as protected bootHasRolesTrait;
        HasRoles::hasRole as protected hasRoleTrait;
    }

    /**
     * Guard Spatie role boot hooks when permission tables are not present.
     */
    public static function bootHasRoles(): void
    {
        if (!Schema::hasTable(config('permission.table_names.model_has_roles'))) {
            return;
        }

        static::bootHasRolesTrait();
    }

    /**
     * Guard permission pivot detaching when permission tables are not present.
     */
    public static function bootHasPermissions(): void
    {
        if (!Schema::hasTable(config('permission.table_names.model_has_permissions'))) {
            return;
        }

        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }

            $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
            $teams = $registrar->teams;
            $registrar->teams = false;

            if (!is_a($model, \Spatie\Permission\Contracts\Permission::class)) {
                $model->permissions()->detach();
            }

            if (is_a($model, \Spatie\Permission\Contracts\Role::class)) {
                $model->users()->detach();
            }

            $registrar->teams = $teams;
        });
    }

    /**
     * The "booted" method of the model.
     * 
     * Note: Tenant scoping is NOT applied to User model to avoid
     * circular dependency during authentication. User filtering is
     * handled through policies and controller-level authorization.
     */
    protected static function booted(): void
    {
        // No global scope for User model
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'system_tenant_id',
        'is_super_admin',
        'tenant_id',
        'property_id',
        'parent_user_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'organization_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * Determine if the user can access the Filament admin panel.
     * 
     * This method implements the primary authorization gate for Filament panel access.
     * It works in conjunction with EnsureUserIsAdminOrManager middleware to provide
     * defense-in-depth security.
     * 
     * Authorization Rules:
     * - Admin Panel: ADMIN, MANAGER, SUPERADMIN roles only
     * - Other Panels: SUPERADMIN only
     * - TENANT role: Explicitly denied access to all panels
     * 
     * Requirements: 9.1, 9.2, 9.3
     * 
     * @param Panel $panel The Filament panel being accessed
     * @return bool True if user can access the panel, false otherwise
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Ensure user is active (prevents deactivated accounts from accessing panels)
        if (!$this->is_active) {
            return false;
        }

        // Admin panel: Allow ADMIN, MANAGER, and SUPERADMIN roles
        if ($panel->getId() === 'admin') {
            return in_array($this->role, [
                UserRole::ADMIN,
                UserRole::MANAGER,
                UserRole::SUPERADMIN,
            ], true);
        }

        // Other panels: Only SUPERADMIN
        return $this->role === UserRole::SUPERADMIN;
    }

    /**
     * Role helpers for clearer intent across tenant-aware services.
     */
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

    public function isTenantUser(): bool
    {
        return $this->role === UserRole::TENANT;
    }

    /**
     * Override role check to work without pivot tables in lightweight setups.
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        if (Schema::hasTable(config('permission.table_names.model_has_roles'))) {
            return $this->hasRoleTrait($roles, $guard);
        }

        $roleValue = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;

        return collect(Arr::wrap($roles))
            ->map(fn ($role) => $role instanceof \BackedEnum ? $role->value : (string) $role)
            ->contains($roleValue);
    }

    /**
     * Get the system tenant this user belongs to.
     */
    public function systemTenant(): BelongsTo
    {
        return $this->belongsTo(SystemTenant::class, 'system_tenant_id');
    }

    /**
     * Get the property assigned to this user (for tenant role).
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the parent user (admin) who created this user.
     */
    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    /**
     * Get the child users (tenants) created by this user.
     */
    public function childUsers(): HasMany
    {
        return $this->hasMany(User::class, 'parent_user_id');
    }

    /**
     * Get the subscription associated with this user (for admin role).
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Get the properties managed by this user (for admin role).
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Get the buildings managed by this user (for admin role).
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Get the invoices for this user's organization (for admin role).
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Get the meter readings entered by this user.
     */
    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class, 'entered_by');
    }

    /**
     * Get the meter reading audits created by this user.
     */
    public function meterReadingAudits(): HasMany
    {
        return $this->hasMany(MeterReadingAudit::class, 'changed_by_user_id');
    }

    /**
     * Get the tenant (renter) associated with this user.
     */
    public function tenant()
    {
        return $this->hasOne(Tenant::class, 'email', 'email');
    }

    /**
     * Get the dashboard customization for this user.
     */
    public function dashboardCustomization(): HasOne
    {
        return $this->hasOne(DashboardCustomization::class);
    }

    /**
     * Scope: Order users by role priority.
     * 
     * Orders users with superadmin first, then admin, manager, and tenant.
     */
    public function scopeOrderedByRole($query)
    {
        return $query->orderByRaw("
            CASE role
                WHEN 'superadmin' THEN 1
                WHEN 'admin' THEN 2
                WHEN 'manager' THEN 3
                WHEN 'tenant' THEN 4
                ELSE 5
            END
        ");
    }

    /**
     * Scope: Filter only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter users by role.
     */
    public function scopeOfRole($query, UserRole $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope: Filter users by tenant.
     */
    public function scopeOfTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Filter admin users (admin, manager, superadmin).
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', [
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::SUPERADMIN,
        ]);
    }

    /**
     * Scope: Filter tenant users only.
     */
    public function scopeTenants($query)
    {
        return $query->where('role', UserRole::TENANT);
    }

    /**
     * Scope: Filter users with expired email verification.
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }
}
