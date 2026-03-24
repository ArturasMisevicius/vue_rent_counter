<?php

namespace App\Models;

use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use Carbon\CarbonInterface;
use Database\Factories\MeterReadingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeterReading extends Model
{
    /** @use HasFactory<MeterReadingFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'property_id',
        'meter_id',
        'submitted_by_user_id',
        'reading_value',
        'reading_date',
        'validation_status',
        'submission_method',
        'notes',
        'created_at',
        'updated_at',
    ];

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

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('meter_readings.organization_id', $organizationId);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('meter_readings.property_id', $propertyId);
    }

    public function scopeForMeter(Builder $query, int $meterId): Builder
    {
        return $query->where('meter_readings.meter_id', $meterId);
    }

    public function scopeBetweenDates(
        Builder $query,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
    ): Builder {
        $resolvedStart = $startDate instanceof CarbonInterface ? $startDate->toDateString() : $startDate;
        $resolvedEnd = $endDate instanceof CarbonInterface ? $endDate->toDateString() : $endDate;

        return $query->whereBetween('meter_readings.reading_date', [$resolvedStart, $resolvedEnd]);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('meter_readings.reading_date')
            ->orderByDesc('meter_readings.id');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('meter_readings.validation_status', MeterReadingValidationStatus::VALID);
    }

    public function scopeComparable(Builder $query): Builder
    {
        return $query->whereIn('meter_readings.validation_status', MeterReadingValidationStatus::comparableValues());
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('meter_readings.validation_status', MeterReadingValidationStatus::PENDING);
    }

    public function scopeSubmittedBy(Builder $query, int $userId): Builder
    {
        return $query->where('meter_readings.submitted_by_user_id', $userId);
    }

    public function scopeBeforeDate(Builder $query, CarbonInterface|string $date): Builder
    {
        $resolvedDate = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        return $query->whereDate('meter_readings.reading_date', '<', $resolvedDate);
    }

    public function scopeBeforeOrOnDate(Builder $query, CarbonInterface|string $date): Builder
    {
        $resolvedDate = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        return $query->whereDate('meter_readings.reading_date', '<=', $resolvedDate);
    }

    public function scopeWithWorkspaceRelations(Builder $query): Builder
    {
        return $query->with([
            'meter:id,organization_id,property_id,name,identifier,unit',
            'meter.property:id,organization_id,building_id,name',
            'property:id,organization_id,building_id,name',
            'property.building:id,organization_id,name',
            'submittedBy:id,name',
        ]);
    }

    public function scopeWithIndexRelations(Builder $query, bool $includeOrganization = false): Builder
    {
        $query->with([
            'meter:id,organization_id,property_id,name,identifier,unit',
            'meter.property:id,organization_id,building_id,name',
            'property:id,organization_id,building_id,name',
            'property.building:id,organization_id,name',
            'submittedBy:id,name',
        ]);

        if (! $includeOrganization) {
            return $query;
        }

        return $query->with([
            'organization:id,name',
        ]);
    }

    public function scopeForAdminWorkspace(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::WORKSPACE_COLUMNS)
            ->forOrganization($organizationId)
            ->withWorkspaceRelations()
            ->latestFirst();
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        $query = $query
            ->select(self::WORKSPACE_COLUMNS)
            ->withIndexRelations($isSuperadmin)
            ->latestFirst();

        if ($isSuperadmin) {
            return $query;
        }

        if ($organizationId === null) {
            return $query->whereKey(-1);
        }

        return $query->forOrganization($organizationId);
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->where('meter_readings.organization_id', $organizationId);
    }

    public function scopeForValidationStatusValue(Builder $query, int|string|null $validationStatus): Builder
    {
        if (blank($validationStatus)) {
            return $query;
        }

        return $query->where('meter_readings.validation_status', $validationStatus);
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
