<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Services\ApiTokenManager;
use App\Services\PanelAccessService;
use App\Services\UserRoleService;
use App\ValueObjects\UserCapabilities;
use App\ValueObjects\UserState;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;
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
 * @property-read \Illuminate\Database\Eloquent\Collection|PersonalAccessToken[] $tokens API tokens for this user
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

    // Constants for magic strings
    public const DEFAULT_ROLE = 'tenant';
    public const ADMIN_PANEL_ID = 'admin';
    
    // Role priorities for ordering
    public const ROLE_PRIORITIES = [
        'superadmin' => 1,
        'admin' => 2,
        'manager' => 3,
        'tenant' => 4,
    ];

    // Cache TTL constants
    private const CACHE_TTL_SHORT = 300; // 5 minutes
    private const CACHE_TTL_MEDIUM = 900; // 15 minutes
    private const CACHE_TTL_LONG = 3600; // 1 hour

    // Memoization properties
    private ?UserCapabilities $memoizedCapabilities = null;
    private ?UserState $memoizedState = null;
    private ?PanelAccessService $memoizedPanelService = null;
    private ?UserRoleService $memoizedRoleService = null;
    private ?ApiTokenManager $memoizedTokenManager = null;
    
    // Current access token (set by middleware)
    public ?PersonalAccessToken $currentAccessToken = null;

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
        
        // Clear cache when user is updated
        static::updated(function (User $user) {
            $user->clearCache();
            $user->refreshMemoizedData();
        });

        // Clear cache when user is deleted
        static::deleted(function (User $user) {
            $user->clearCache();
        });
    }

    /**
     * The attributes that are mass assignable.
     * 
     * SECURITY: Sensitive fields removed to prevent privilege escalation attacks.
     * Use dedicated methods for: system_tenant_id, is_super_admin, tenant_id, 
     * property_id, parent_user_id, role, suspended_at
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
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
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
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
        return $this->getPanelAccessService()->canAccessPanel($this, $panel);
    }

    /**
     * Role helpers for clearer intent across tenant-aware services.
     */
    public function isSuperadmin(): bool
    {
        return $this->getUserRoleService()->isSuperadmin($this);
    }

    public function isAdmin(): bool
    {
        return $this->getUserRoleService()->isAdmin($this);
    }

    public function isManager(): bool
    {
        return $this->getUserRoleService()->isManager($this);
    }

    public function isTenantUser(): bool
    {
        return $this->getUserRoleService()->isTenant($this);
    }

    /**
     * Override role check to work without pivot tables in lightweight setups.
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        return $this->getUserRoleService()->hasRole($this, $roles, $guard);
    }

    /**
     * Get user capabilities based on role and status (memoized).
     */
    public function getCapabilities(): UserCapabilities
    {
        return $this->memoizedCapabilities ??= UserCapabilities::fromUser($this);
    }

    /**
     * Get user state information (memoized).
     */
    public function getState(): UserState
    {
        return $this->memoizedState ??= new UserState($this);
    }

    /**
     * Check if user has administrative privileges.
     */
    public function hasAdministrativePrivileges(): bool
    {
        return $this->getUserRoleService()->hasAdministrativePrivileges($this);
    }

    /**
     * Get role priority for ordering.
     */
    public function getRolePriority(): int
    {
        return $this->getUserRoleService()->getRolePriority($this);
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
     * Get all API tokens for this user.
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(PersonalAccessToken::class, 'tokenable_id')
            ->where('tokenable_type', self::class);
    }

    /**
     * Organizations this user belongs to with roles
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'permissions', 'joined_at', 'left_at', 'is_active', 'invited_by'])
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    /**
     * All organization memberships including inactive
     */
    public function organizationMemberships(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'permissions', 'joined_at', 'left_at', 'is_active', 'invited_by'])
            ->withTimestamps();
    }

    /**
     * Projects created by this user
     */
    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * Projects assigned to this user
     */
    public function assignedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'assigned_to');
    }

    /**
     * Tasks assigned to this user with roles
     */
    public function taskAssignments(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments')
            ->using(TaskAssignment::class)
            ->withPivot(['role', 'assigned_at', 'completed_at', 'notes'])
            ->withTimestamps();
    }

    /**
     * Tasks where user is the primary assignee
     */
    public function assignedTasks(): BelongsToMany
    {
        return $this->taskAssignments()->wherePivot('role', 'assignee');
    }

    /**
     * Tasks where user is a reviewer
     */
    public function reviewTasks(): BelongsToMany
    {
        return $this->taskAssignments()->wherePivot('role', 'reviewer');
    }

    /**
     * Scope: Order users by role priority.
     * 
     * Orders users with superadmin first, then admin, manager, and tenant.
     */
    public function scopeOrderedByRole(Builder $query): Builder
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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('suspended_at');
    }

    /**
     * Scope: Filter users by role.
     */
    public function scopeOfRole(Builder $query, UserRole $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Scope: Filter users by tenant.
     */
    public function scopeOfTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Filter users for a specific tenant (used by repository).
     */
    public function scopeForTenant(Builder $query, $tenantId): Builder
    {
        $tenantValue = $tenantId instanceof \App\ValueObjects\TenantId ? $tenantId->getValue() : $tenantId;
        return $query->where('tenant_id', $tenantValue);
    }

    /**
     * Get the tenant ID column name.
     */
    public function getTenantIdColumn(): string
    {
        return 'tenant_id';
    }

    /**
     * Scope: Filter admin users (admin, manager, superadmin).
     */
    public function scopeAdmins(Builder $query): Builder
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
    public function scopeTenants(Builder $query): Builder
    {
        return $query->where('role', UserRole::TENANT);
    }

    /**
     * Scope: Filter users with expired email verification.
     */
    public function scopeUnverified(Builder $query): Builder
    {
        return $query->whereNull('email_verified_at');
    }

    /**
     * Scope: Filter users by system tenant (for superadmin operations).
     */
    public function scopeOfSystemTenant(Builder $query, int $systemTenantId): Builder
    {
        return $query->where('system_tenant_id', $systemTenantId);
    }

    /**
     * Scope: Filter suspended users.
     */
    public function scopeSuspended(Builder $query): Builder
    {
        return $query->whereNotNull('suspended_at');
    }

    /**
     * Scope: Filter users with recent activity (last 30 days).
     */
    public function scopeRecentlyActive(Builder $query): Builder
    {
        return $query->where('last_login_at', '>=', now()->subDays(30));
    }

    /**
     * Scope: Filter users by property assignment.
     */
    public function scopeOfProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope: Filter users created by specific parent user.
     */
    public function scopeCreatedBy(Builder $query, int $parentUserId): Builder
    {
        return $query->where('parent_user_id', $parentUserId);
    }

    /**
     * Scope: Eager load common relationships for performance.
     */
    public function scopeWithCommonRelations(Builder $query): Builder
    {
        return $query->with([
            'property:id,name,address,tenant_id',
            'parentUser:id,name,email,role',
            'systemTenant:id,name,slug',
        ]);
    }

    /**
     * Scope: Eager load extended relationships for detailed views.
     */
    public function scopeWithExtendedRelations(Builder $query): Builder
    {
        return $query->with([
            'property:id,name,address,tenant_id,building_id',
            'property.building:id,name,address',
            'parentUser:id,name,email,role,organization_name',
            'systemTenant:id,name,slug,description',
            'subscription:id,user_id,status,expires_at',
            'dashboardCustomization:id,user_id,layout,preferences',
        ]);
    }

    /**
     * Scope: Load only essential fields for listings.
     */
    public function scopeForListing(Builder $query): Builder
    {
        return $query->select([
            'id', 'name', 'email', 'role', 'is_active', 
            'tenant_id', 'property_id', 'last_login_at', 'created_at'
        ])->with([
            'property:id,name',
            'parentUser:id,name',
        ]);
    }

    /**
     * Scope: Filter users for API access (active with verified email).
     */
    public function scopeApiEligible(Builder $query): Builder
    {
        return $query->active()
                    ->whereNotNull('email_verified_at')
                    ->whereNull('suspended_at');
    }

    /**
     * Get user's role in a specific organization.
     */
    public function getRoleInOrganization(Organization $organization): ?string
    {
        $membership = $this->organizations()
            ->where('organization_id', $organization->id)
            ->first();
            
        return $membership?->pivot->role;
    }

    /**
     * Check if user has role in organization.
     */
    public function hasRoleInOrganization(Organization $organization, string $role): bool
    {
        return $this->organizations()
            ->where('organization_id', $organization->id)
            ->wherePivot('role', $role)
            ->exists();
    }

    /**
     * Get all projects across all organizations (optimized with caching).
     */
    public function allProjects(): Builder
    {
        // Cache organization IDs to avoid repeated queries
        $cacheKey = "user_org_ids:{$this->id}";
        $organizationIds = Cache::remember($cacheKey, self::CACHE_TTL_MEDIUM, function () {
            return $this->organizations()->pluck('organizations.id')->toArray();
        });
        
        return Project::whereIn('tenant_id', $organizationIds)
            ->orWhere('created_by', $this->id)
            ->orWhere('assigned_to', $this->id);
    }

    /**
     * Get all projects with eager loading for performance.
     */
    public function allProjectsWithRelations(): Builder
    {
        return $this->allProjects()
            ->with([
                'organization:id,name',
                'creator:id,name',
                'assignee:id,name',
                'tasks' => function ($query) {
                    $query->select('id', 'project_id', 'title', 'status')
                          ->where('status', '!=', 'completed')
                          ->limit(5);
                }
            ]);
    }

    /**
     * Clear all cached data for this user.
     */
    public function clearCache(): void
    {
        $this->getUserRoleService()->clearRoleCache($this);
        $this->getPanelAccessService()->clearPanelAccessCache($this);
        
        // Clear memoized properties
        $this->memoizedCapabilities = null;
        $this->memoizedState = null;
        
        // Clear specific cache keys
        Cache::forget("user_org_ids:{$this->id}");
        Cache::forget("user_projects_count:{$this->id}");
        Cache::forget("user_tasks_summary:{$this->id}");
    }

    /**
     * Assign user to tenant (secure method).
     * 
     * @param int $tenantId Target tenant ID
     * @param User $admin Admin performing the assignment
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function assignToTenant(int $tenantId, User $admin): void
    {
        if (!$admin->hasAdministrativePrivileges()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Insufficient privileges to assign tenant');
        }
        
        $this->tenant_id = $tenantId;
        $this->save();
        
        Log::info('User assigned to tenant', [
            'user_id' => $this->id,
            'tenant_id' => $tenantId,
            'admin_id' => $admin->id,
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Assign user to property (secure method).
     * 
     * @param int $propertyId Target property ID
     * @param User $admin Admin performing the assignment
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function assignToProperty(int $propertyId, User $admin): void
    {
        if (!$admin->hasAdministrativePrivileges()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Insufficient privileges to assign property');
        }
        
        $this->property_id = $propertyId;
        $this->parent_user_id = $admin->id;
        $this->save();
        
        Log::info('User assigned to property', [
            'user_id' => $this->id,
            'property_id' => $propertyId,
            'admin_id' => $admin->id,
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Promote user to superadmin (secure method).
     * 
     * @param User $currentSuperAdmin Current superadmin performing the promotion
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function promoteToSuperAdmin(User $currentSuperAdmin): void
    {
        if (!$currentSuperAdmin->isSuperadmin()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Only superadmins can promote users');
        }
        
        $this->is_super_admin = true;
        $this->role = UserRole::SUPERADMIN;
        $this->tenant_id = null; // Superadmins have no tenant scope
        $this->save();
        
        Log::warning('User promoted to superadmin', [
            'user_id' => $this->id,
            'promoted_by' => $currentSuperAdmin->id,
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Suspend user account (secure method).
     * 
     * @param string $reason Suspension reason
     * @param User $admin Admin performing the suspension
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function suspend(string $reason, User $admin): void
    {
        if (!$admin->hasAdministrativePrivileges()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Insufficient privileges to suspend user');
        }
        
        $this->suspended_at = now();
        $this->suspension_reason = $reason;
        $this->save();
        
        // Revoke all API tokens for security
        $this->revokeAllApiTokens();
        
        Log::warning('User account suspended', [
            'user_id' => $this->id,
            'reason' => $reason,
            'suspended_by' => $admin->id,
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Create API token with role-based abilities.
     * 
     * Automatically assigns abilities based on user role unless custom abilities are provided.
     * Uses custom ApiTokenManager service for token management.
     * 
     * @param string $name Token name for identification
     * @param array|null $abilities Custom abilities array, null for role-based defaults
     * @return string Plain text token for API authentication
     * 
     * @see \App\Services\ApiTokenManager
     */
    public function createApiToken(string $name, ?array $abilities = null): string
    {
        return $this->getApiTokenManager()->createToken($this, $name, $abilities);
    }

    /**
     * Revoke all API tokens for security.
     * 
     * Useful for security incidents, password changes, or account deactivation.
     * Immediately invalidates all active tokens for this user.
     * 
     * @return int Number of tokens revoked
     */
    public function revokeAllApiTokens(): int
    {
        return $this->getApiTokenManager()->revokeAllTokens($this);
    }

    /**
     * Get active API tokens count.
     * 
     * Returns the number of currently active API tokens for monitoring and security purposes.
     * 
     * @return int Number of active tokens
     */
    public function getActiveTokensCount(): int
    {
        return $this->getApiTokenManager()->getActiveTokenCount($this);
    }

    /**
     * Check if user has specific API ability.
     * 
     * Validates if the current access token has the specified ability.
     * Used for runtime permission checking in API endpoints.
     * 
     * @param string $ability The ability to check (e.g., 'meter-reading:write')
     * @return bool True if user has the ability, false otherwise
     */
    public function hasApiAbility(string $ability): bool
    {
        return $this->getApiTokenManager()->hasAbility($this, $ability);
    }

    /**
     * Check if user can create meter readings.
     * 
     * All authenticated roles can create meter readings in the Truth-but-Verify workflow.
     * 
     * @return bool True if user can create meter readings
     */
    public function canCreateMeterReadings(): bool
    {
        return in_array($this->role, [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ], true) && $this->is_active;
    }

    /**
     * Check if user can manage (approve/reject) meter readings.
     * 
     * Only managers and above can approve tenant-submitted readings.
     * 
     * @return bool True if user can manage meter readings
     */
    public function canManageMeterReadings(): bool
    {
        return in_array($this->role, [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
        ], true) && $this->is_active;
    }

    /**
     * Check if user can validate meter readings.
     * 
     * Alias for canManageMeterReadings for clarity in Truth-but-Verify workflow.
     * 
     * @return bool True if user can validate meter readings
     */
    public function canValidateMeterReadings(): bool
    {
        return $this->canManageMeterReadings();
    }

    /**
     * Check if user submissions require validation.
     * 
     * Tenant submissions require manager approval in Truth-but-Verify workflow.
     * 
     * @return bool True if user's submissions require validation
     */
    public function submissionsRequireValidation(): bool
    {
        return $this->role === UserRole::TENANT;
    }

    /**
     * Get the current access token.
     * 
     * @return PersonalAccessToken|null
     */
    public function currentAccessToken(): ?PersonalAccessToken
    {
        return $this->currentAccessToken;
    }

    /**
     * Create a token (Laravel Sanctum compatibility method).
     * 
     * @param string $name
     * @param array $abilities
     * @param \DateTimeInterface|null $expiresAt
     * @return object Object with plainTextToken property
     */
    public function createToken(string $name, array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): object
    {
        $plainTextToken = $this->getApiTokenManager()->createToken($this, $name, $abilities, $expiresAt);
        
        return (object) [
            'plainTextToken' => $plainTextToken,
        ];
    }

    /**
     * Get cached project count for this user.
     */
    public function getProjectsCount(): int
    {
        $cacheKey = "user_projects_count:{$this->id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL_MEDIUM, function () {
            return $this->allProjects()->count();
        });
    }

    /**
     * Get cached task summary for this user.
     */
    public function getTasksSummary(): array
    {
        $cacheKey = "user_tasks_summary:{$this->id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () {
            $assignments = $this->taskAssignments()
                ->join('tasks', 'task_assignments.task_id', '=', 'tasks.id')
                ->selectRaw('
                    COUNT(*) as total_tasks,
                    COUNT(CASE WHEN tasks.status = "pending" THEN 1 END) as pending_tasks,
                    COUNT(CASE WHEN tasks.status = "in_progress" THEN 1 END) as in_progress_tasks,
                    COUNT(CASE WHEN tasks.status = "completed" THEN 1 END) as completed_tasks,
                    COUNT(CASE WHEN tasks.due_date < NOW() AND tasks.status != "completed" THEN 1 END) as overdue_tasks
                ')
                ->first();

            return [
                'total' => $assignments->total_tasks ?? 0,
                'pending' => $assignments->pending_tasks ?? 0,
                'in_progress' => $assignments->in_progress_tasks ?? 0,
                'completed' => $assignments->completed_tasks ?? 0,
                'overdue' => $assignments->overdue_tasks ?? 0,
            ];
        });
    }

    /**
     * Personal projects (polymorphic) - optimized.
     */
    public function personalProjects(): MorphMany
    {
        return $this->morphMany(Project::class, 'projectable')
            ->select(['id', 'name', 'status', 'created_at', 'projectable_type', 'projectable_id'])
            ->latest();
    }

    /**
     * Get memoized PanelAccessService instance.
     */
    private function getPanelAccessService(): PanelAccessService
    {
        return $this->memoizedPanelService ??= app(PanelAccessService::class);
    }

    /**
     * Get memoized UserRoleService instance.
     */
    private function getUserRoleService(): UserRoleService
    {
        return $this->memoizedRoleService ??= app(UserRoleService::class);
    }

    /**
     * Get memoized ApiTokenManager instance.
     */
    private function getApiTokenManager(): ApiTokenManager
    {
        return $this->memoizedTokenManager ??= app(ApiTokenManager::class);
    }

    /**
     * Refresh memoized data (call after model updates).
     */
    public function refreshMemoizedData(): void
    {
        $this->memoizedCapabilities = null;
        $this->memoizedState = null;
        $this->memoizedPanelService = null;
        $this->memoizedRoleService = null;
        $this->memoizedTokenManager = null;
    }

}
