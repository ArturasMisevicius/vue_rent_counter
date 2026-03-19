<?php

namespace App\Models;

use App\Enums\PropertyType;
use App\Traits\BelongsToTenant;
use App\Traits\HasTags;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Property extends Model
{
    use BelongsToTenant, HasFactory, HasTags;

    /**
     * Backwards-compatible computed display name.
     *
     * Some parts of the UI and tests refer to a `name` field even though the
     * database uses `address` + optional `unit_number`.
     *
     * @return string The computed display name for the property
     */
    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['name'] ?? $this->unit_number ?? $this->address ?? '');
    }

    /**
     * Backwards-compatible alias for the `type` enum.
     *
     * Legacy UI code expects `property_type` to be an enum-like value.
     *
     * @return PropertyType|null The property type enum
     */
    public function getPropertyTypeAttribute(): ?PropertyType
    {
        return $this->type;
    }

    /**
     * Get the full display address including unit number.
     *
     * @return string The formatted address with unit number if available
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address ?? '';

        if ($this->unit_number) {
            return trim($address.', Unit '.$this->unit_number);
        }

        return $address;
    }

    /**
     * Check if the property is currently occupied.
     *
     * @return bool True if the property has active tenants
     */
    public function isOccupied(): bool
    {
        return $this->tenants()->exists();
    }

    /**
     * Get the current tenant(s) for this property.
     *
     * @return Collection<int, Tenant>
     */
    public function getCurrentTenants(): Collection
    {
        return $this->tenants;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'address',
        'type',
        'area_sqm',
        'unit_number',
        'building_id',
        'heating_system_type',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
            'area_sqm' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the building this property belongs to.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Get the primary renter tenant for this property.
     * This is the tenant who is currently renting this property.
     */
    public function tenant(): HasMany
    {
        return $this->hasMany(Tenant::class, 'property_id');
    }

    /**
     * Get tenants assigned directly to this property.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'property_tenant')
            ->withPivot(['assigned_at', 'vacated_at'])
            ->withTimestamps()
            ->wherePivotNull('vacated_at')
            ->orderByPivot('assigned_at', 'desc');
    }

    /**
     * Get active tenants for this property.
     */
    public function activeTenants(): BelongsToMany
    {
        return $this->tenants(); // Same as tenants() since it already filters for active
    }

    /**
     * Tenant assignments including historical records.
     */
    public function tenantAssignments(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'property_tenant')
            ->withPivot(['assigned_at', 'vacated_at'])
            ->withTimestamps()
            ->orderByPivot('assigned_at', 'desc');
    }

    /**
     * Get the meters for this property.
     */
    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }

    /**
     * Get the service configurations for this property.
     */
    public function serviceConfigurations(): HasMany
    {
        return $this->hasMany(ServiceConfiguration::class);
    }

    /**
     * Get the utility services for this property through service configurations.
     */
    public function utilityServices(): BelongsToMany
    {
        return $this->belongsToMany(UtilityService::class, 'service_configurations')
            ->withPivot(['pricing_model', 'rate_schedule', 'distribution_method', 'is_shared_service', 'effective_from', 'effective_until', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the utility readings for this property.
     */
    public function utilityReadings(): HasMany
    {
        return $this->hasMany(UtilityReading::class);
    }

    /**
     * Get the shared services for this property.
     */
    public function sharedServices(): HasMany
    {
        return $this->hasMany(SharedService::class);
    }

    /**
     * Get the billing records for this property.
     */
    public function billingRecords(): HasMany
    {
        return $this->hasMany(BillingRecord::class);
    }

    /**
     * Get projects for this property (polymorphic)
     */
    public function projects(): MorphMany
    {
        return $this->morphMany(Project::class, 'projectable');
    }

    /**
     * Get active projects for this property
     */
    public function activeProjects(): MorphMany
    {
        return $this->projects()->where('status', 'active');
    }

    /**
     * Scope a query to properties of a specific type.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfType(Builder $query, PropertyType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to apartment properties.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeApartments(Builder $query): Builder
    {
        return $query->where('type', PropertyType::APARTMENT);
    }

    /**
     * Scope a query to house properties.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeHouses(Builder $query): Builder
    {
        return $query->where('type', PropertyType::HOUSE);
    }

    /**
     * Scope a query to residential properties (apartments, houses, studios).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeResidential(Builder $query): Builder
    {
        return $query->whereIn('type', [
            PropertyType::APARTMENT,
            PropertyType::HOUSE,
            PropertyType::STUDIO,
        ]);
    }

    /**
     * Scope a query to commercial properties.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCommercial(Builder $query): Builder
    {
        return $query->whereIn('type', [
            PropertyType::OFFICE,
            PropertyType::RETAIL,
            PropertyType::WAREHOUSE,
            PropertyType::COMMERCIAL,
        ]);
    }

    /**
     * Scope a query to occupied properties.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOccupied(Builder $query): Builder
    {
        return $query->whereHas('tenants');
    }

    /**
     * Scope a query to vacant properties.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVacant(Builder $query): Builder
    {
        return $query->whereDoesntHave('tenants');
    }

    /**
     * Scope a query to properties with active meters.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithActiveMeters(Builder $query): Builder
    {
        return $query->whereHas('meters');
    }

    /**
     * Scope a query to properties with specific tags.
     *
     * @param  Builder<static>  $query
     * @param  array<string|int|Tag>  $tags
     * @return Builder<static>
     */
    public function scopeWithTags(Builder $query, array $tags): Builder
    {
        return $this->scopeWithAnyTag($query, $tags);
    }

    /**
     * Get properties with efficient eager loading for common use cases.
     *
     * @return Builder<static>
     */
    public static function withCommonRelations(): Builder
    {
        return static::query()->with([
            'building:id,address,tenant_id',
            'tenants:id,name,tenant_id',
            'tags:id,name,slug,color',
            'meters:id,property_id,type,serial_number',
        ]);
    }

    /**
     * Get a summary of property statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatsSummary(): array
    {
        return [
            'total_meters' => $this->meters()->count(),
            'active_tenants' => $this->tenants()->count(),
            'tag_count' => $this->tags()->count(),
            'has_building' => ! is_null($this->building_id),
            'is_occupied' => $this->isOccupied(),
            'property_type_label' => $this->type?->getLabel() ?? 'Unknown',
        ];
    }

    /**
     * Check if property can be assigned to a tenant.
     */
    public function canAssignTenant(): bool
    {
        // Business rule: Only vacant properties can be assigned new tenants
        return ! $this->isOccupied();
    }

    /**
     * Get the primary display identifier for this property.
     */
    public function getDisplayIdentifier(): string
    {
        if ($this->unit_number) {
            return "{$this->address}, Unit {$this->unit_number}";
        }

        return $this->address ?? "Property #{$this->id}";
    }
}
