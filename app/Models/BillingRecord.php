<?php

namespace App\Models;

use Database\Factories\BillingRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingRecord extends Model
{
    /** @use HasFactory<BillingRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'property_id',
        'utility_service_id',
        'invoice_id',
        'tenant_user_id',
        'amount',
        'consumption',
        'rate',
        'meter_reading_start',
        'meter_reading_end',
        'notes',
        'billing_period_start',
        'billing_period_end',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'consumption' => 'decimal:3',
            'rate' => 'decimal:4',
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function utilityService(): BelongsTo
    {
        return $this->belongsTo(UtilityService::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }
}
