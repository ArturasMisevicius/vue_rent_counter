<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PropertyAssignmentStatus;
use Carbon\CarbonInterface;
use Database\Factories\PropertyAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyAssignment extends Model
{
    /** @use HasFactory<PropertyAssignmentFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'organization_id',
        'property_id',
        'tenant_user_id',
        'unit_area_sqm',
        'status',
        'is_primary',
        'occupants_count',
        'assigned_at',
        'unassigned_at',
        'move_out_date',
        'billing_start_date',
        'billing_end_date',
        'move_out_reason',
        'move_out_scheduled_by_user_id',
        'move_out_completed_by_user_id',
        'move_out_completed_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    private const SUPERADMIN_INDEX_COLUMNS = [
        'id',
        'organization_id',
        'property_id',
        'tenant_user_id',
        'unit_area_sqm',
        'status',
        'is_primary',
        'occupants_count',
        'assigned_at',
        'unassigned_at',
        'move_out_date',
        'billing_start_date',
        'billing_end_date',
        'move_out_reason',
        'move_out_scheduled_by_user_id',
        'move_out_completed_by_user_id',
        'move_out_completed_at',
        'created_by_user_id',
        'updated_by_user_id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'property_id',
        'tenant_user_id',
        'unit_area_sqm',
        'status',
        'is_primary',
        'occupants_count',
        'assigned_at',
        'unassigned_at',
        'move_out_date',
        'billing_start_date',
        'billing_end_date',
        'move_out_reason',
        'move_out_scheduled_by_user_id',
        'move_out_completed_by_user_id',
        'move_out_completed_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'unit_area_sqm' => 'decimal:2',
            'status' => PropertyAssignmentStatus::class,
            'is_primary' => 'boolean',
            'occupants_count' => 'integer',
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
            'move_out_date' => 'date',
            'billing_start_date' => 'date',
            'billing_end_date' => 'date',
            'move_out_completed_at' => 'datetime',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_user_id', $tenantId);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query
            ->whereNull('unassigned_at')
            ->whereIn('status', PropertyAssignmentStatus::openValues());
    }

    public function scopeOpenEnded(Builder $query): Builder
    {
        return $query->whereNull('unassigned_at');
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeActivePrimary(Builder $query): Builder
    {
        return $query
            ->current()
            ->primary()
            ->where('status', PropertyAssignmentStatus::ACTIVE->value);
    }

    public function scopeActiveDuring(Builder $query, CarbonInterface $periodStart, CarbonInterface $periodEnd): Builder
    {
        return $query
            ->whereIn('status', [
                PropertyAssignmentStatus::ACTIVE->value,
                PropertyAssignmentStatus::MOVE_OUT_SCHEDULED->value,
            ])
            ->where('assigned_at', '<=', $periodEnd)
            ->where(function (Builder $query) use ($periodStart): void {
                $query
                    ->whereNull('unassigned_at')
                    ->orWhere('unassigned_at', '>=', $periodStart);
            })
            ->where(function (Builder $query) use ($periodStart): void {
                $query
                    ->whereNull('billing_end_date')
                    ->orWhereDate('billing_end_date', '>=', $periodStart);
            });
    }

    public function scopeLatestAssignedFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('assigned_at')
            ->orderByDesc('id');
    }

    public function scopeWithTenantSummary(Builder $query): Builder
    {
        return $query->with([
            'tenant:id,organization_id,name,email,status,locale',
        ]);
    }

    public function scopeWithPropertySummary(Builder $query): Builder
    {
        return $query->with([
            'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
            'property.building:id,organization_id,name,address_line_1,city',
        ]);
    }

    public function scopeForWorkspaceSummary(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->forOrganization($organizationId)
            ->withTenantSummary()
            ->withPropertySummary()
            ->latestAssignedFirst();
    }

    public function scopeForSuperadminIndex(Builder $query): Builder
    {
        return $query
            ->select(self::SUPERADMIN_INDEX_COLUMNS)
            ->with([
                'organization:id,name',
            ])
            ->withTenantSummary()
            ->withPropertySummary()
            ->latestAssignedFirst();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function rentalContracts(): HasMany
    {
        return $this->hasMany(RentalContract::class);
    }

    public function moveOutProcesses(): HasMany
    {
        return $this->hasMany(MoveOutProcess::class);
    }

    public function activeMoveOutProcess(): HasMany
    {
        return $this->moveOutProcesses()->open();
    }

    public function moveOutScheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'move_out_scheduled_by_user_id');
    }

    public function moveOutCompletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'move_out_completed_by_user_id');
    }
}
