<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Billing record model for tracking billing calculations.
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $property_id
 * @property int $utility_service_id
 * @property float $amount
 * @property float $consumption
 * @property float $rate
 * @property int|null $meter_reading_start
 * @property int|null $meter_reading_end
 * @property string|null $notes
 * @property \Carbon\Carbon $billing_period_start
 * @property \Carbon\Carbon $billing_period_end
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class BillingRecord extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'utility_service_id',
        'amount',
        'consumption',
        'rate',
        'meter_reading_start',
        'meter_reading_end',
        'notes',
        'billing_period_start',
        'billing_period_end',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'consumption' => 'decimal:2',
        'rate' => 'decimal:4',
        'meter_reading_start' => 'integer',
        'meter_reading_end' => 'integer',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
    ];

    /**
     * Get the tenant this billing record belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the property this billing record belongs to.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the utility service this billing record belongs to.
     */
    public function utilityService(): BelongsTo
    {
        return $this->belongsTo(UtilityService::class);
    }

    /**
     * Get the invoices that include this billing record.
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'invoice_billing_record')
                    ->withTimestamps();
    }
}