<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Tenant extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'property_id',
        'lease_start',
        'lease_end',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lease_start' => 'date',
            'lease_end' => 'date',
        ];
    }

    /**
     * Get the property this tenant rents.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the invoices for this tenant.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the meter readings through the property's meters.
     */
    public function meterReadings(): HasManyThrough
    {
        return $this->hasManyThrough(
            MeterReading::class,
            Meter::class,
            'property_id', // Foreign key on meters table
            'meter_id',    // Foreign key on meter_readings table
            'property_id', // Local key on tenants table
            'id'           // Local key on meters table
        );
    }
}
