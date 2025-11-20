<?php

namespace App\Models;

use App\Enums\PropertyType;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
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
     * Get the tenants for this property.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
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
