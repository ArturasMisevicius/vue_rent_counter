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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCurrentTenants()
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
     * Scope a query to properties of a specific type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param PropertyType $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, PropertyType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to apartment properties.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApartments($query)
    {
        return $query->where('type', PropertyType::APARTMENT);
    }

    /**
     * Scope a query to house properties.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHouses($query)
    {
        return $query->where('type', PropertyType::HOUSE);
    }

    /**
     * Scope a query to residential properties (apartments, houses, studios).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResidential($query)
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommercial($query)
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOccupied($query)
    {
        return $query->whereHas('tenants');
    }

    /**
     * Scope a query to vacant properties.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVacant($query)
    {
        return $query->whereDoesntHave('tenants');
    }

    /**
     * Scope a query to properties with active meters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithActiveMeters($query)
    {
        return $query->whereHas('meters');
    }
}
