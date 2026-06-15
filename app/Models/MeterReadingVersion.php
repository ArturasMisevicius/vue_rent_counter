<?php

namespace App\Models;

use App\Enums\MeterReadingStatus;
use Database\Factories\MeterReadingVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReadingVersion extends Model
{
    /** @use HasFactory<MeterReadingVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'meter_reading_id',
        'organization_id',
        'invoice_id',
        'billing_period_id',
        'changed_by_user_id',
        'version',
        'event',
        'previous_value',
        'current_value',
        'consumption',
        'status',
        'reading_date',
        'reason',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'previous_value' => 'decimal:3',
            'current_value' => 'decimal:3',
            'consumption' => 'decimal:3',
            'status' => MeterReadingStatus::class,
            'reading_date' => 'date',
            'snapshot' => 'array',
        ];
    }

    public function reading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class, 'meter_reading_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
