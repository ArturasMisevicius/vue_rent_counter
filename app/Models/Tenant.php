<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * Automatically generate a unique slug if missing.
     */
    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (! empty($tenant->slug)) {
                return;
            }

            $baseSlug = Str::slug($tenant->name ?? 'tenant-' . Str::random(8));
            $slug = $baseSlug;
            $counter = 1;

            while (static::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $tenant->slug = $slug;
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
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
     * Historical property assignments for this tenant.
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_tenant')
            ->withPivot(['assigned_at', 'vacated_at'])
            ->withTimestamps()
            ->orderByPivot('assigned_at', 'desc');
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
