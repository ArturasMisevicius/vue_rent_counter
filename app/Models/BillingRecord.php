<?php

namespace App\Models;

use Database\Factories\BillingRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingRecord extends Model
{
    /** @use HasFactory<BillingRecordFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
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
        'created_at',
        'updated_at',
    ];

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

    public function startReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class, 'meter_reading_start');
    }

    public function endReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class, 'meter_reading_end');
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_user_id', $tenantId);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeForInvoice(Builder $query, int $invoiceId): Builder
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeForBillingPeriod(
        Builder $query,
        \DateTimeInterface|string $periodStart,
        \DateTimeInterface|string $periodEnd,
    ): Builder {
        return $query
            ->whereDate('billing_period_start', $periodStart)
            ->whereDate('billing_period_end', $periodEnd);
    }

    public function scopeLatestBillingFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('billing_period_end')
            ->orderByDesc('id');
    }

    public function scopeWithBillingRelations(Builder $query): Builder
    {
        return $query->with([
            'property:id,organization_id,building_id,name,unit_number',
            'utilityService:id,organization_id,name,unit_of_measurement,default_pricing_model,service_type_bridge',
            'invoice:id,organization_id,tenant_user_id,invoice_number,status,due_date,total_amount',
            'tenant:id,organization_id,name,email,role,status',
            'startReading:id,meter_id,reading_value,reading_date,validation_status',
            'endReading:id,meter_id,reading_value,reading_date,validation_status',
        ]);
    }

    public function scopeForOrganizationWorkspace(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::WORKSPACE_COLUMNS)
            ->forOrganization($organizationId)
            ->withBillingRelations()
            ->latestBillingFirst();
    }
}
