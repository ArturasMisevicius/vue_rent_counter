<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Advanced Query Optimization Techniques for ServiceValidationEngine
 * 
 * This class demonstrates various Laravel query optimization patterns
 * specifically designed to eliminate N+1 queries and improve performance.
 */
class QueryOptimizationTechniques
{
    /**
     * TECHNIQUE 1: Subquery Selects for Aggregations
     * 
     * Instead of loading full relationships, use subqueries to get specific data
     */
    public function getReadingsWithSubquerySelects(): Collection
    {
        return MeterReading::query()
            ->select([
                'id', 'meter_id', 'value', 'reading_date',
                // Subquery for previous reading value
                DB::raw('(
                    SELECT value 
                    FROM meter_readings prev 
                    WHERE prev.meter_id = meter_readings.meter_id 
                    AND prev.reading_date < meter_readings.reading_date 
                    AND prev.validation_status = "validated"
                    ORDER BY prev.reading_date DESC 
                    LIMIT 1
                ) as previous_value'),
                
                // Subquery for consumption calculation
                DB::raw('(
                    meter_readings.value - (
                        SELECT COALESCE(prev.value, 0)
                        FROM meter_readings prev 
                        WHERE prev.meter_id = meter_readings.meter_id 
                        AND prev.reading_date < meter_readings.reading_date 
                        AND prev.validation_status = "validated"
                        ORDER BY prev.reading_date DESC 
                        LIMIT 1
                    )
                ) as consumption'),
                
                // Subquery for service configuration name
                DB::raw('(
                    SELECT us.name 
                    FROM service_configurations sc
                    JOIN utility_services us ON us.id = sc.utility_service_id
                    JOIN meters m ON m.service_configuration_id = sc.id
                    WHERE m.id = meter_readings.meter_id
                ) as service_name')
            ])
            ->where('validation_status', 'pending')
            ->get();
    }

    /**
     * TECHNIQUE 2: withCount(), withSum(), withAvg() for Aggregations
     * 
     * Use Laravel's aggregate methods instead of loading full collections
     */
    public function getMetersWithAggregates(): Collection
    {
        return \App\Models\Meter::query()
            ->withCount([
                'readings',
                'readings as validated_readings_count' => function ($query) {
                    $query->where('validation_status', 'validated');
                },
                'readings as pending_readings_count' => function ($query) {
                    $query->where('validation_status', 'pending');
                }
            ])
            ->withSum([
                'readings as total_consumption' => 'value',
                'readings as monthly_consumption' => function ($query) {
                    $query->where('reading_date', '>=', now()->startOfMonth());
                }
            ])
            ->withAvg('readings as average_reading', 'value')
            ->withMax('readings as latest_reading_date', 'reading_date')
            ->withMin('readings as first_reading_date', 'reading_date')
            ->get();
    }

    /**
     * TECHNIQUE 3: Conditional Relationship Loading
     * 
     * Load relationships only when needed based on conditions
     */
    public function getReadingsWithConditionalLoading(array $options = []): Collection
    {
        $query = MeterReading::query();

        // Base relationships always loaded
        $query->with([
            'meter:id,property_id,type,service_configuration_id'
        ]);

        // Conditional loading based on options
        if ($options['include_service_config'] ?? false) {
            $query->with([
                'meter.serviceConfiguration' => function ($query) {
                    $query->select(['id', 'utility_service_id', 'pricing_model', 'rate_schedule']);
                },
                'meter.serviceConfiguration.utilityService:id,name,unit_of_measurement'
            ]);
        }

        if ($options['include_validation_history'] ?? false) {
            $query->with([
                'validatedBy:id,name',
                'enteredBy:id,name'
            ]);
        }

        if ($options['include_property_details'] ?? false) {
            $query->with([
                'meter.property' => function ($query) {
                    $query->select(['id', 'name', 'address', 'building_id']);
                },
                'meter.property.building:id,name,address'
            ]);
        }

        return $query->get();
    }

    /**
     * TECHNIQUE 4: Lazy Eager Loading with loadMissing()
     * 
     * Load relationships only if they haven't been loaded already
     */
    public function processReadingsWithLazyLoading(Collection $readings): void
    {
        // First, try to use already loaded relationships
        foreach ($readings as $reading) {
            if ($reading->relationLoaded('meter')) {
                // Use already loaded meter
                $meter = $reading->meter;
            }
        }

        // Load missing relationships only when needed
        $readings->loadMissing([
            'meter.serviceConfiguration',
            'meter.serviceConfiguration.utilityService'
        ]);

        // Conditionally load additional relationships
        $readingsNeedingHistory = $readings->filter(function ($reading) {
            return $reading->input_method === 'estimated';
        });

        if ($readingsNeedingHistory->isNotEmpty()) {
            $readingsNeedingHistory->load([
                'meter.readings' => function ($query) {
                    $query->where('reading_date', '>=', now()->subMonths(6))
                          ->where('validation_status', 'validated')
                          ->orderBy('reading_date', 'desc');
                }
            ]);
        }
    }

    /**
     * TECHNIQUE 5: Window Functions for Complex Queries
     * 
     * Use database window functions for efficient calculations
     */
    public function getReadingsWithWindowFunctions(): Collection
    {
        return DB::table('meter_readings')
            ->select([
                'id', 'meter_id', 'value', 'reading_date',
                
                // Previous reading using LAG window function
                DB::raw('LAG(value) OVER (
                    PARTITION BY meter_id, zone 
                    ORDER BY reading_date
                ) as previous_value'),
                
                // Consumption calculation
                DB::raw('value - LAG(value) OVER (
                    PARTITION BY meter_id, zone 
                    ORDER BY reading_date
                ) as consumption'),
                
                // Running total
                DB::raw('SUM(value) OVER (
                    PARTITION BY meter_id 
                    ORDER BY reading_date 
                    ROWS UNBOUNDED PRECEDING
                ) as running_total'),
                
                // Moving average (last 3 readings)
                DB::raw('AVG(value) OVER (
                    PARTITION BY meter_id 
                    ORDER BY reading_date 
                    ROWS 2 PRECEDING
                ) as moving_average'),
                
                // Rank readings by value within each meter
                DB::raw('RANK() OVER (
                    PARTITION BY meter_id 
                    ORDER BY value DESC
                ) as value_rank')
            ])
            ->where('validation_status', 'validated')
            ->orderBy('meter_id')
            ->orderBy('reading_date')
            ->get();
    }

    /**
     * TECHNIQUE 6: Bulk Operations with Raw SQL
     * 
     * Use raw SQL for complex bulk operations
     */
    public function bulkUpdateValidationStatus(array $readingIds, string $status): int
    {
        // Single query to update multiple records
        return DB::table('meter_readings')
            ->whereIn('id', $readingIds)
            ->update([
                'validation_status' => $status,
                'validated_at' => now(),
                'validated_by' => auth()->id(),
                'updated_at' => now()
            ]);
    }

    /**
     * TECHNIQUE 7: Efficient Pagination with Cursor
     * 
     * Use cursor pagination for large datasets
     */
    public function getPaginatedReadingsWithCursor(int $perPage = 50, ?string $cursor = null): array
    {
        $query = MeterReading::query()
            ->with([
                'meter:id,property_id,type',
                'meter.serviceConfiguration:id,utility_service_id',
                'meter.serviceConfiguration.utilityService:id,name'
            ])
            ->select(['id', 'meter_id', 'value', 'reading_date', 'validation_status'])
            ->orderBy('id');

        if ($cursor) {
            $query->where('id', '>', $cursor);
        }

        $readings = $query->limit($perPage + 1)->get();
        
        $hasMore = $readings->count() > $perPage;
        if ($hasMore) {
            $readings->pop(); // Remove the extra record
        }

        return [
            'data' => $readings,
            'has_more' => $hasMore,
            'next_cursor' => $hasMore ? $readings->last()->id : null
        ];
    }

    /**
     * TECHNIQUE 8: Optimized Exists Queries
     * 
     * Use whereExists instead of loading full relationships
     */
    public function getMetersWithPendingReadings(): Collection
    {
        return \App\Models\Meter::query()
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('meter_readings')
                      ->whereColumn('meter_readings.meter_id', 'meters.id')
                      ->where('meter_readings.validation_status', 'pending');
            })
            ->with(['serviceConfiguration:id,utility_service_id'])
            ->get();
    }

    /**
     * TECHNIQUE 9: Chunk Processing for Large Datasets
     * 
     * Process large datasets in chunks to avoid memory issues
     */
    public function processLargeDatasetInChunks(callable $processor, int $chunkSize = 1000): array
    {
        $processedCount = 0;
        $errors = [];

        MeterReading::query()
            ->where('validation_status', 'pending')
            ->with([
                'meter:id,service_configuration_id',
                'meter.serviceConfiguration:id,utility_service_id'
            ])
            ->chunk($chunkSize, function (Collection $readings) use ($processor, &$processedCount, &$errors) {
                try {
                    $processor($readings);
                    $processedCount += $readings->count();
                } catch (\Exception $e) {
                    $errors[] = "Chunk processing error: " . $e->getMessage();
                }
            });

        return [
            'processed_count' => $processedCount,
            'errors' => $errors
        ];
    }

    /**
     * TECHNIQUE 10: Database-Specific Optimizations for SQLite
     * 
     * SQLite-specific query optimizations
     */
    public function getSQLiteOptimizedQuery(): Collection
    {
        // SQLite doesn't support window functions in older versions
        // Use correlated subqueries instead
        return DB::table('meter_readings as mr1')
            ->select([
                'mr1.id', 'mr1.meter_id', 'mr1.value', 'mr1.reading_date',
                
                // Previous reading using correlated subquery
                DB::raw('(
                    SELECT mr2.value 
                    FROM meter_readings mr2 
                    WHERE mr2.meter_id = mr1.meter_id 
                    AND mr2.reading_date < mr1.reading_date 
                    AND mr2.validation_status = "validated"
                    ORDER BY mr2.reading_date DESC 
                    LIMIT 1
                ) as previous_value')
            ])
            ->where('mr1.validation_status', 'pending')
            ->orderBy('mr1.meter_id')
            ->orderBy('mr1.reading_date')
            ->get();
    }
}