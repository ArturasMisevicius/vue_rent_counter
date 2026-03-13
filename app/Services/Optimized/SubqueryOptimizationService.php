<?php

declare(strict_types=1);

namespace App\Services\Optimized;

use App\Models\MeterReading;
use App\Models\Meter;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Subquery Optimization Examples
 * 
 * Demonstrates when to use subqueries vs JOINs and optimization techniques
 */
final readonly class SubqueryOptimizationService
{
    /**
     * SUBQUERY vs JOIN Performance Comparison
     * 
     * Rule of thumb:
     * - Use JOINs when you need data from both tables
     * - Use subqueries when you only need to filter based on related data
     * - Use EXISTS for better performance than IN with large datasets
     */

    /**
     * BAD: Correlated Subquery (executes for each row)
     */
    public function getPropertiesWithReadingsBad(int $tenantId): Builder
    {
        return Property::where('tenant_id', $tenantId)
            ->where(function ($query) {
                $query->whereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('meters')
                        ->whereColumn('meters.property_id', 'properties.id')
                        ->whereExists(function ($nestedSubquery) {
                            $nestedSubquery->select(DB::raw(1))
                                ->from('meter_readings')
                                ->whereColumn('meter_readings.meter_id', 'meters.id')
                                ->where('meter_readings.created_at', '>=', now()->subDays(30));
                        });
                });
            });
    }

    /**
     * GOOD: Non-Correlated Subquery with JOIN
     */
    public function getPropertiesWithReadingsGood(int $tenantId): Builder
    {
        // First, get meter IDs with recent readings (non-correlated)
        $metersWithRecentReadings = DB::table('meter_readings')
            ->select('meter_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->distinct()
            ->pluck('meter_id');

        // Then use IN clause with the pre-calculated list
        return Property::where('tenant_id', $tenantId)
            ->whereHas('meters', function ($query) use ($metersWithRecentReadings) {
                $query->whereIn('id', $metersWithRecentReadings);
            });
    }

    /**
     * BEST: Single JOIN with aggregation
     */
    public function getPropertiesWithReadingsBest(int $tenantId): Builder
    {
        return Property::query()
            ->select('properties.*')
            ->join('meters', 'properties.id', '=', 'meters.property_id')
            ->join('meter_readings', 'meters.id', '=', 'meter_readings.meter_id')
            ->where('properties.tenant_id', $tenantId)
            ->where('meter_readings.created_at', '>=', now()->subDays(30))
            ->groupBy('properties.id')
            ->having(DB::raw('COUNT(meter_readings.id)'), '>', 0);
    }

    /**
     * Subquery in SELECT (Window Functions Alternative)
     */
    public function getMetersWithLatestReading(int $tenantId): array
    {
        // BAD: N+1 Query Pattern
        // $meters = Meter::where('tenant_id', $tenantId)->get();
        // foreach ($meters as $meter) {
        //     $meter->latest_reading = $meter->readings()->latest()->first();
        // }

        // GOOD: Subquery in SELECT
        return DB::table('meters as m')
            ->select([
                'm.*',
                DB::raw('(
                    SELECT mr.value 
                    FROM meter_readings mr 
                    WHERE mr.meter_id = m.id 
                    ORDER BY mr.reading_date DESC 
                    LIMIT 1
                ) as latest_reading_value'),
                DB::raw('(
                    SELECT mr.reading_date 
                    FROM meter_readings mr 
                    WHERE mr.meter_id = m.id 
                    ORDER BY mr.reading_date DESC 
                    LIMIT 1
                ) as latest_reading_date'),
            ])
            ->where('m.tenant_id', $tenantId)
            ->get()
            ->toArray();
    }

    /**
     * BEST: Window Function (MySQL 8.0+, PostgreSQL)
     */
    public function getMetersWithLatestReadingWindowFunction(int $tenantId): array
    {
        return DB::select("
            SELECT 
                m.*,
                mr.value as latest_reading_value,
                mr.reading_date as latest_reading_date
            FROM meters m
            LEFT JOIN (
                SELECT 
                    meter_id,
                    value,
                    reading_date,
                    ROW_NUMBER() OVER (PARTITION BY meter_id ORDER BY reading_date DESC) as rn
                FROM meter_readings
            ) mr ON m.id = mr.meter_id AND mr.rn = 1
            WHERE m.tenant_id = ?
        ", [$tenantId]);
    }

    /**
     * Subquery Placement Optimization
     */
    
    /**
     * BAD: Subquery in WHERE with OR conditions
     */
    public function getReadingsWithComplexFilterBad(int $tenantId): Builder
    {
        return MeterReading::where('tenant_id', $tenantId)
            ->where(function ($query) {
                $query->where('validation_status', 'validated')
                    ->orWhereIn('meter_id', function ($subquery) {
                        $subquery->select('id')
                            ->from('meters')
                            ->where('type', 'electricity')
                            ->where('supports_zones', true);
                    });
            });
    }

    /**
     * GOOD: Subquery in FROM clause (derived table)
     */
    public function getReadingsWithComplexFilterGood(int $tenantId): array
    {
        return DB::select("
            SELECT mr.*
            FROM meter_readings mr
            JOIN (
                SELECT id as meter_id FROM meters 
                WHERE type = 'electricity' AND supports_zones = true
                UNION
                SELECT DISTINCT meter_id FROM meter_readings 
                WHERE validation_status = 'validated'
            ) filtered_meters ON mr.meter_id = filtered_meters.meter_id
            WHERE mr.tenant_id = ?
        ", [$tenantId]);
    }

    /**
     * Materialized Subqueries for Complex Aggregations
     */
    public function getMeterConsumptionSummary(int $tenantId): array
    {
        // Create temporary table for complex calculations
        DB::statement("
            CREATE TEMPORARY TABLE temp_consumption_summary AS
            SELECT 
                m.id as meter_id,
                m.serial_number,
                m.type,
                p.name as property_name,
                
                -- Current month consumption
                COALESCE(SUM(CASE 
                    WHEN mr.reading_date >= DATE_FORMAT(NOW(), '%Y-%m-01') 
                    THEN mr.value - LAG(mr.value) OVER (
                        PARTITION BY mr.meter_id 
                        ORDER BY mr.reading_date
                    )
                END), 0) as current_month_consumption,
                
                -- Previous month consumption
                COALESCE(SUM(CASE 
                    WHEN mr.reading_date >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 1 MONTH)
                    AND mr.reading_date < DATE_FORMAT(NOW(), '%Y-%m-01')
                    THEN mr.value - LAG(mr.value) OVER (
                        PARTITION BY mr.meter_id 
                        ORDER BY mr.reading_date
                    )
                END), 0) as previous_month_consumption,
                
                -- Average monthly consumption (last 6 months)
                COALESCE(AVG(CASE 
                    WHEN mr.reading_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    THEN mr.value - LAG(mr.value) OVER (
                        PARTITION BY mr.meter_id 
                        ORDER BY mr.reading_date
                    )
                END), 0) as avg_monthly_consumption
                
            FROM meters m
            JOIN properties p ON m.property_id = p.id
            LEFT JOIN meter_readings mr ON m.id = mr.meter_id
            WHERE m.tenant_id = ?
            GROUP BY m.id, m.serial_number, m.type, p.name
        ");

        $results = DB::select("SELECT * FROM temp_consumption_summary");
        
        DB::statement("DROP TEMPORARY TABLE temp_consumption_summary");
        
        return $results;
    }

    /**
     * Recursive Subqueries (for hierarchical data)
     */
    public function getBuildingHierarchy(int $tenantId): array
    {
        // Using Common Table Expression (CTE) for recursive queries
        return DB::select("
            WITH RECURSIVE building_hierarchy AS (
                -- Base case: root buildings
                SELECT 
                    id,
                    name,
                    parent_building_id,
                    0 as level,
                    CAST(name AS CHAR(1000)) as path
                FROM buildings 
                WHERE tenant_id = ? AND parent_building_id IS NULL
                
                UNION ALL
                
                -- Recursive case: child buildings
                SELECT 
                    b.id,
                    b.name,
                    b.parent_building_id,
                    bh.level + 1,
                    CONCAT(bh.path, ' > ', b.name)
                FROM buildings b
                JOIN building_hierarchy bh ON b.parent_building_id = bh.id
                WHERE b.tenant_id = ?
            )
            SELECT * FROM building_hierarchy ORDER BY path
        ", [$tenantId, $tenantId]);
    }

    /**
     * Performance Tips for Subqueries:
     * 
     * 1. Use EXISTS instead of IN for large datasets
     * 2. Avoid correlated subqueries in SELECT clause
     * 3. Consider window functions for ranking/aggregation
     * 4. Use LIMIT in subqueries when possible
     * 5. Index columns used in subquery WHERE clauses
     * 6. Use UNION instead of OR when appropriate
     * 7. Consider materialized views for complex recurring subqueries
     */
}