<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Repository for MeterReading model operations.
 * 
 * Implements Repository pattern to abstract data access logic
 * and provide optimized queries with proper eager loading.
 */
class MeterReadingRepository
{
    /**
     * Find multiple meter readings with optimized eager loading.
     * 
     * @param array $ids Array of meter reading IDs
     * @return Collection<int, MeterReading>
     */
    public function findManyWithRelations(array $ids): Collection
    {
        return MeterReading::whereIn('id', $ids)
            ->with([
                'meter.serviceConfiguration.utilityService',
                'meter.serviceConfiguration.tariff',
                'meter.serviceConfiguration.provider',
                'enteredBy:id,name',
                'validatedBy:id,name'
            ])
            ->get();
    }

    /**
     * Get paginated meter readings by validation status with optimized queries.
     * 
     * @param ValidationStatus $status Validation status to filter by
     * @param array $filters Additional filters (date_from, date_to, input_method, meter_ids, tenant_id)
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    public function getByStatusPaginated(ValidationStatus $status, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MeterReading::query()
            ->where('validation_status', $status)
            ->with([
                'meter:id,property_id,type,service_configuration_id',
                'meter.serviceConfiguration:id,utility_service_id',
                'meter.serviceConfiguration.utilityService:id,name,unit_of_measurement',
                'enteredBy:id,name',
                'validatedBy:id,name'
            ])
            ->select([
                'id', 'meter_id', 'reading_date', 'value', 'reading_values',
                'validation_status', 'input_method', 'entered_by', 'validated_by',
                'created_at', 'updated_at'
            ]);

        // Apply tenant scoping
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Apply date range filtering
        if (isset($filters['date_from'])) {
            $query->where('reading_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('reading_date', '<=', $filters['date_to']);
        }

        // Apply input method filtering
        if (isset($filters['input_method'])) {
            $query->where('input_method', $filters['input_method']);
        }

        // Apply meter filtering
        if (isset($filters['meter_ids']) && !empty($filters['meter_ids'])) {
            $query->whereIn('meter_id', $filters['meter_ids']);
        }

        return $query->orderBy('reading_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get validation count for today with caching.
     * 
     * @return int
     */
    public function getTodayValidationCount(): int
    {
        return \Illuminate\Support\Facades\Cache::remember('validation_count_today', 300, function () {
            return MeterReading::whereDate('updated_at', today())
                ->where('validation_status', '!=', ValidationStatus::PENDING)
                ->count();
        });
    }

    /**
     * Get readings requiring validation within date range.
     * 
     * @param \Carbon\Carbon $from Start date
     * @param \Carbon\Carbon $to End date
     * @return Collection<int, MeterReading>
     */
    public function getRequiringValidation(\Carbon\Carbon $from, \Carbon\Carbon $to): Collection
    {
        return MeterReading::where('validation_status', ValidationStatus::PENDING)
            ->whereBetween('reading_date', [$from, $to])
            ->with(['meter.serviceConfiguration.utilityService'])
            ->orderBy('reading_date', 'desc')
            ->get();
    }

    /**
     * Bulk update validation status for multiple readings.
     * 
     * @param array $readingIds Array of reading IDs
     * @param ValidationStatus $status New validation status
     * @param int $validatedBy User ID who performed validation
     * @return int Number of updated records
     */
    public function bulkUpdateValidationStatus(array $readingIds, ValidationStatus $status, int $validatedBy): int
    {
        return MeterReading::whereIn('id', $readingIds)
            ->update([
                'validation_status' => $status,
                'validated_by' => $validatedBy,
                'updated_at' => now(),
            ]);
    }
}