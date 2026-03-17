<?php

namespace App\Models;

use App\Enums\PropertyType;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'building_id',
        'name',
        'unit_number',
        'type',
        'floor_area_sqm',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'building_id',
        'name',
        'unit_number',
        'type',
        'floor_area_sqm',
    ];

    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
            'floor_area_sqm' => 'decimal:2',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeWithAddressRelations(Builder $query): Builder
    {
        return $query->with([
            'building:id,organization_id,name,address_line_1,address_line_2,city,postal_code,country_code',
        ]);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeWithCurrentAssignmentSummary(Builder $query): Builder
    {
        return $query->with([
            'currentAssignment:id,organization_id,property_id,tenant_user_id,assigned_at,unassigned_at',
            'currentAssignment.tenant:id,name',
        ]);
    }

    public function scopeWithAssignmentHistory(Builder $query): Builder
    {
        return $query->with([
            'assignments:id,organization_id,property_id,tenant_user_id,assigned_at,unassigned_at',
            'assignments.tenant:id,name',
        ]);
    }

    public function scopeWithWorkspaceSummary(Builder $query): Builder
    {
        return $query
            ->select(self::WORKSPACE_COLUMNS)
            ->withAddressRelations()
            ->withCurrentAssignmentSummary()
            ->withCount('meters');
    }

    public function scopeForOrganizationWorkspace(Builder $query, int $organizationId): Builder
    {
        return $query
            ->forOrganization($organizationId)
            ->withWorkspaceSummary()
            ->withAssignmentHistory()
            ->ordered();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PropertyAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(PropertyAssignment::class)
            ->current()
            ->latestAssignedFirst();
    }

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }

    public function serviceConfigurations(): HasMany
    {
        return $this->hasMany(ServiceConfiguration::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getCurrentTenantAttribute(): ?User
    {
        return $this->currentAssignment?->tenant;
    }

    public function getAddressAttribute(): string
    {
        $building = $this->building;
        $parts = array_filter([
            $building?->address_line_1,
            $this->unit_number ? 'Unit '.$this->unit_number : null,
            $building?->city,
        ]);

        return implode(', ', $parts);
    }
}
