<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use App\Enums\UserRole;
use App\Models\Concerns\HasGeneratedSlug;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasGeneratedSlug;

    private const CONTROL_PLANE_COLUMNS = [
        'id',
        'name',
        'slug',
        'status',
        'owner_user_id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'slug',
        'status',
        'owner_user_id',
        'system_tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrganizationStatus::class,
        ];
    }

    protected function slugSourceColumn(): string
    {
        return 'name';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OrganizationStatus::ACTIVE);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeWithOwnerSummary(Builder $query): Builder
    {
        return $query->with([
            'owner:id,name,email',
        ]);
    }

    public function scopeWithUsageCounts(Builder $query): Builder
    {
        return $query->withCount([
            'users',
            'properties',
            'meters',
            'invoices',
            'subscriptions',
        ]);
    }

    public function scopeWithCurrentSubscriptionSummary(Builder $query): Builder
    {
        return $query->with([
            'currentSubscription' => fn ($subscriptionQuery) => $subscriptionQuery
                ->select([
                    'id',
                    'organization_id',
                    'plan',
                    'status',
                    'is_trial',
                    'expires_at',
                    'property_limit_snapshot',
                    'tenant_limit_snapshot',
                    'meter_limit_snapshot',
                    'invoice_limit_snapshot',
                ])
                ->withLatestPaymentSummary(),
        ]);
    }

    public function scopeWithTenantCount(Builder $query): Builder
    {
        return $query->withCount([
            'users as tenants_count' => fn (Builder $tenantQuery): Builder => $tenantQuery->where('role', UserRole::TENANT),
        ]);
    }

    public function scopeWithBuildingCount(Builder $query): Builder
    {
        return $query->withCount('buildings');
    }

    public function scopeWithActivityLogCount(Builder $query): Builder
    {
        return $query->withCount('activityLogs');
    }

    public function scopeWithSecurityViolationCount(Builder $query): Builder
    {
        return $query->withCount('securityViolations');
    }

    public function scopeWithLastActivityAt(Builder $query): Builder
    {
        return $query->withMax('activityLogs', 'created_at');
    }

    public function scopeForSuperadminControlPlane(Builder $query): Builder
    {
        return $query
            ->select(self::CONTROL_PLANE_COLUMNS)
            ->withOwnerSummary()
            ->withCurrentSubscriptionSummary()
            ->withUsageCounts()
            ->withBuildingCount()
            ->withActivityLogCount()
            ->withTenantCount()
            ->latest('created_at')
            ->latest('id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function systemTenant(): BelongsTo
    {
        return $this->belongsTo(SystemTenant::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function ownershipCandidates(): HasMany
    {
        return $this->users()->whereKeyNot($this->owner_user_id);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->latestFirst();
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(OrganizationSetting::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function propertyAssignments(): HasMany
    {
        return $this->hasMany(PropertyAssignment::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }

    public function utilityServices(): HasMany
    {
        return $this->hasMany(UtilityService::class);
    }

    public function serviceConfigurations(): HasMany
    {
        return $this->hasMany(ServiceConfiguration::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(OrganizationActivityLog::class);
    }

    public function securityViolations(): HasMany
    {
        return $this->hasMany(SecurityViolation::class);
    }
}
