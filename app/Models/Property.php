<?php

namespace App\Models;

use App\Enums\PropertyType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use BelongsToTenant, HasFactory;

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
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
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
     * Scope a query to properties of a specific type.
     */
    public function scopeOfType($query, PropertyType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Scope a query to apartment properties.
     */
    public function scopeApartments($query): void
    {
        $query->where('type', PropertyType::APARTMENT);
    }

    /**
     * Scope a query to house properties.
     */
    public function scopeHouses($query): void
    {
        $query->where('type', PropertyType::HOUSE);
    }
}
