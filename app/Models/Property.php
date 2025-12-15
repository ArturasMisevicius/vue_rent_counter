<?php

namespace App\Models;

use App\Enums\PropertyType;
use App\Traits\BelongsToTenant;
use App\Traits\HasTags;
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
            return trim($address . ', Unit ' . $this->unit_number);
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
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant>
     */
    public function getCurrentTenants(): \Illuminate\Database\Eloquent\Collection
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
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @param PropertyType $type
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOfType(\Illuminate\Database\Eloquent\Builder $query, PropertyType $type): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to apartment properties.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeApartments(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', PropertyType::APARTMENT);
    }

    /**
     * Scope a query to house properties.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeHouses(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', PropertyType::HOUSE);
    }

    /**
     * Scope a query to residential properties (apartments, houses, studios).
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeResidential(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
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
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeCommercial(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
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
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOccupied(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('tenants');
    }

    /**
     * Scope a query to vacant properties.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeVacant(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereDoesntHave('tenants');
    }

    /**
     * Scope a query to properties with active meters.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeWithActiveMeters(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('meters');
    }

    /**
     * Scope a query to properties with specific tags.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @param array<string|int|\App\Models\Tag> $tags
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeWithTags(\Illuminate\Database\Eloquent\Builder $query, array $tags): \Illuminate\Database\Eloquent\Builder
    {
        return $this->scopeWithAnyTag($query, $tags);
    }

    /**
     * Get properties with efficient eager loading for common use cases.
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public static function withCommonRelations(): \Illuminate\Database\Eloquent\Builder
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
            'has_building' => !is_null($this->building_id),
            'is_occupied' => $this->isOccupied(),
            'property_type_label' => $this->type?->getLabel() ?? 'Unknown',
        ];
    }

    /**
     * Check if property can be assigned to a tenant.
     *
     * @return bool
     */
    public function canAssignTenant(): bool
    {
        // Business rule: Only vacant properties can be assigned new tenants
        return !$this->isOccupied();
    }

    /**
     * Get the primary display identifier for this property.
     *
     * @return string
     */
    public function getDisplayIdentifier(): string
    {
        if ($this->unit_number) {
            return "{$this->address}, Unit {$this->unit_number}";
        }

        return $this->address ?? "Property #{$this->id}";
    }
}
