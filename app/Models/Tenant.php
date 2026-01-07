<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        'unit_area',
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
            'unit_area' => 'decimal:2',
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

    /**
     * Get the utility readings for this tenant.
     */
    public function utilityReadings(): HasMany
    {
        return $this->hasMany(UtilityReading::class);
    }

    /**
     * Get the billing records for this tenant.
     */
    public function billingRecords(): HasMany
    {
        return $this->hasMany(BillingRecord::class);
    }

    /**
     * Get the meters associated with this tenant through the property.
     */
    public function meters(): HasManyThrough
    {
        return $this->hasManyThrough(
            Meter::class,
            Property::class,
            'id',          // Foreign key on properties table
            'property_id', // Foreign key on meters table
            'property_id', // Local key on tenants table
            'id'           // Local key on properties table
        );
    }

    /**
     * Get all attachments for this tenant.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the tenant's photo attachment.
     */
    public function photo(): ?Attachment
    {
        return $this->attachments()
            ->where('metadata->category', 'photo')
            ->latest()
            ->first();
    }

    /**
     * Get the tenant's lease contract attachment.
     */
    public function leaseContract(): ?Attachment
    {
        return $this->attachments()
            ->where('metadata->category', 'contract')
            ->latest()
            ->first();
    }

    /**
     * Get the tenant's identity documents.
     */
    public function identityDocuments()
    {
        return $this->attachments()
            ->where('metadata->category', 'identity');
    }

    /**
     * Get all document attachments (excluding photos).
     */
    public function documents()
    {
        return $this->attachments()
            ->whereIn('metadata->category', ['contract', 'identity', 'document']);
    }

    /**
     * Check if tenant is active for a given period.
     */
    public function isActiveForPeriod(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): bool
    {
        // Check if tenant has active status
        if (!($this->is_active ?? true)) {
            return false;
        }

        // Check lease period overlap
        $leaseStart = $this->lease_start ?? $this->occupancy_start_date;
        $leaseEnd = $this->lease_end ?? $this->occupancy_end_date;

        // If no lease start, assume active
        if (!$leaseStart) {
            return true;
        }

        // Check if lease period overlaps with billing period
        $leaseStartCarbon = \Carbon\Carbon::parse($leaseStart);
        $leaseEndCarbon = $leaseEnd ? \Carbon\Carbon::parse($leaseEnd) : null;

        // Lease starts before or during the billing period
        if ($leaseStartCarbon->gt($endDate)) {
            return false;
        }

        // Lease ends after or during the billing period (or no end date)
        if ($leaseEndCarbon && $leaseEndCarbon->lt($startDate)) {
            return false;
        }

        return true;
    }

    /**
     * Get the occupancy start date (alias for lease_start).
     */
    public function getOccupancyStartDateAttribute(): ?\Carbon\Carbon
    {
        return $this->lease_start;
    }

    /**
     * Get the occupancy end date (alias for lease_end).
     */
    public function getOccupancyEndDateAttribute(): ?\Carbon\Carbon
    {
        return $this->lease_end;
    }

    /**
     * Get the rent amount for this tenant.
     */
    public function getRentAmountAttribute(): float
    {
        return $this->attributes['rent_amount'] ?? 0.0;
    }

    /**
     * Check if tenant is currently active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->attributes['is_active'] ?? true;
    }
}
