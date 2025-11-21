<?php

namespace App\Models;

use App\Enums\MeterType;
use App\Scopes\HierarchicalScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meter extends Model
{
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new HierarchicalScope);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'serial_number',
        'type',
        'property_id',
        'installation_date',
        'supports_zones',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MeterType::class,
            'installation_date' => 'date',
            'supports_zones' => 'boolean',
        ];
    }

    /**
     * Get the property this meter belongs to.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the readings for this meter.
     */
    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    /**
     * Scope a query to meters of a specific type.
     */
    public function scopeOfType($query, MeterType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Scope a query to meters that support zones.
     */
    public function scopeSupportsZones($query): void
    {
        $query->where('supports_zones', true);
    }

    /**
     * Scope a query to meters with their latest reading.
     */
    public function scopeWithLatestReading($query): void
    {
        $query->with(['readings' => function ($q) {
            $q->latest('reading_date')->limit(1);
        }]);
    }
}
