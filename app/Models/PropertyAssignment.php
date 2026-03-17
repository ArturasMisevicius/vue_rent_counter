<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\PropertyAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'assigned_at',
        'unassigned_at',
    ];

    protected $fillable = [
        'organization_id',
        'property_id',
        'tenant_user_id',
        'unit_area_sqm',
        'assigned_at',
        'unassigned_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_area_sqm' => 'decimal:2',
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
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
        return $query->whereNull('unassigned_at');
    }

    public function scopeOpenEnded(Builder $query): Builder
    {
        return $query->whereNull('unassigned_at');
    }

    public function scopeActiveDuring(Builder $query, CarbonInterface $periodStart, CarbonInterface $periodEnd): Builder
    {
        return $query
            ->where('assigned_at', '<=', $periodEnd)
            ->where(function (Builder $query) use ($periodStart): void {
                $query
                    ->whereNull('unassigned_at')
                    ->orWhere('unassigned_at', '>=', $periodStart);
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
}
