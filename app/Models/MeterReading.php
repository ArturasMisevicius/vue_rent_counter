<?php

namespace App\Models;

use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use Database\Factories\MeterReadingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeterReading extends Model
{
    /** @use HasFactory<MeterReadingFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'property_id',
        'meter_id',
        'submitted_by_user_id',
        'reading_value',
        'reading_date',
        'validation_status',
        'submission_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reading_value' => 'decimal:3',
            'reading_date' => 'date',
            'validation_status' => MeterReadingValidationStatus::class,
            'submission_method' => MeterReadingSubmissionMethod::class,
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

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(MeterReadingAudit::class);
    }
}
