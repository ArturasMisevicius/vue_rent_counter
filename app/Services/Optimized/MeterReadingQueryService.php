<?php

declare(strict_types=1);

namespace App\Services\Optimized;

use App\Models\MeterReading;
use App\Models\Meter;
use App\Enums\ValidationStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Optimized Meter Reading Query Service
 * 
 * Demonstrates three optimization approaches:
 * 1. Eloquent with eager loading
 * 2. Query Builder with selective columns
 * 3. Raw SQL for maximum performance
 */
final readonly class MeterReadingQueryService
{
    public function __construct(
        private int $cacheTtl = 300, // 5 minutes
    ) {}

    /**
     * OPTIMIZED VERSION 1: Better Eloquent (using Eloquent features)
     * 
     * IMPROVEMENTS:
     * - Eager loading to prevent N+1 queries
     * - Selective column loading
     * - Query scopes for reusability
     * - Caching for repeated queries
     * 
     * WHEN TO USE: When you need full model functionality and relationships
     */
    public function getReadingsWithRelationsEloquent(int $tenantId, string $startDate, string $endDate): Collection
    {
        $cacheKey = "readings_eloquent_{$tenantId}_{$startDate}_{$endDate}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tenantId, $startDate, $endDate) {
            return MeterReading::query()
                // Tenant scoping (automatically handled by BelongsToTenant trait)
                ->forPeriod($startDate, $endDate)
                
                // Eager load relationships with selective columns
                ->with([
                    'meter:id,serial_number,type,property_id,supports_zones',
                    'meter.property:id,name,building_id',
                    'meter.property.building:id,name,address',
                    'enteredBy:id,name,email',
                    'validatedBy:id,name,email',
                ])
                
                // Select only needed columns
                ->select([
                    'id', 'meter_id', 'reading_date', 'value', 'zone',
                    'validation_status', 'input_method', 'entered_by', 'validated_by',
                    'created_at'
                ])
                
                // Use database-level ordering
                ->latest('reading_date')
                
                // Limit result set
                ->limit(1000)
                
                ->get();
        });
    }

    /**
     * OPTIMIZED VERSION 2: Query Builder (using DB facade for performance)
     * 
     * IMPROVEMENTS:
     * - Manual JOINs for better control
     * - Selective column aliasing
     * - Subquery optimization
     * - No model overhead
     * 
     * WHEN TO USE: When you need specific data without model overhead
     */
    public function getReadingsWithRelationsQueryBuilder(int $tenantId, string $startDate, string $endDate): Collection
    {
        $cacheKey = "readings_qb_{$tenantId}_{$startDate}_{$endDate}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tenantId, $startDate, $endDate) {
            return DB::table('meter_readings as mr')
                ->join('meters as m', 'mr.meter_id', '=', 'm.id')
                ->join('properties as p', 'm.property_id', '=', 'p.id')
                ->join('buildings as b', 'p.building_id', '=', 'b.id')
                ->leftJoin('users as entered_user', 'mr.entered_by', '=', 'entered_user.id')
                ->leftJoin('users as validated_user', 'mr.validated_by', '=', 'validated_user.id')
                
                ->select([
                    'mr.id',
                    'mr.reading_date',
                    'mr.value',
                    'mr.zone',
                    'mr.validation_status',
                    'mr.input_method',
                    'mr.created_at',
                    
                    // Meter info
                    'm.serial_number as meter_serial',
                    'm.type as meter_type',
                    'm.supports_zones',
                    
                    // Property info
                    'p.name as property_name',
                    'b.name as building_name',
                    'b.address as building_address',
                    
                    // User info
                    'entered_user.name as entered_by_name',
                    'validated_user.name as validated_by_name',
                ])
                
                ->where('mr.tenant_id', $tenantId)
                ->whereBetween('mr.reading_date', [$startDate, $endDate])
                
                ->orderByDesc('mr.reading_date')
                ->limit(1000)
                
                ->get();
        });
    }

    /**
     * OPTIMIZED VERSION 3: Raw SQL (direct SQL for maximum performance)
     * 
     * IMPROVEMENTS:
     * - Hand-optimized SQL
     * - Covering indexes utilization
     * - Minimal data transfer
     * - Database-specific optimizations
     * 
     * WHEN TO USE: For high-performance reporting and analytics
     */
    public function getReadingsWithRelationsRawSQL(int $tenantId, string $startDate, string $endDate): Collection
    {
        $cacheKey = "readings_raw_{$tenantId}_{$startDate}_{$endDate}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tenantId, $startDate, $endDate) {
            $sql = "
                SELECT /*+ USE_INDEX(mr, mr_tenant_date_meter_idx) */
                    mr.id,
                    mr.reading_date,
                    mr.value,
                    mr.zone,
                    mr.validation_status,
                    mr.input_method,
                    mr.created_at,
                    
                    -- Meter information
                    m.serial_number as meter_serial,
                    m.type as meter_type,
                    m.supports_zones,
                    
                    -- Property information
                    p.name as property_name,
                    b.name as building_name,
                    b.address as building_address,
                    
                    -- User information
                    eu.name as entered_by_name,
                    vu.name as validated_by_name,
                    
                    -- Calculated consumption (optimized)
                    COALESCE(
                        mr.value - LAG(mr.value) OVER (
                            PARTITION BY mr.meter_id, mr.zone 
                            ORDER BY mr.reading_date
                        ), 
                        0
                    ) as consumption
                    
                FROM meter_readings mr
                
                -- Use STRAIGHT_JOIN for MySQL to force join order
                STRAIGHT_JOIN meters m ON mr.meter_id = m.id
                STRAIGHT_JOIN properties p ON m.property_id = p.id
                STRAIGHT_JOIN buildings b ON p.building_id = b.id
                LEFT JOIN users eu ON mr.entered_by = eu.id
                LEFT JOIN users vu ON mr.validated_by = vu.id
                
                WHERE mr.tenant_id = ?
                  AND mr.reading_date BETWEEN ? AND ?
                  
                ORDER BY mr.reading_date DESC
                LIMIT 1000
            ";
            
            return collect(DB::select($sql, [$tenantId, $startDate, $endDate]));
        });
    }

    /**
     * Dashboard Aggregations - Optimized for Widget Performance
     */
    public function getDashboardMetrics(int $tenantId, int $days = 30): array
    {
        $cacheKey = "dashboard_metrics_{$tenantId}_{$days}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tenantId, $days) {
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            
            // Use raw SQL for maximum performance on aggregations
            $sql = "
                SELECT 
                    COUNT(*) as total_readings,
                    COUNT(CASE WHEN validation_status = 'validated' THEN 1 END) as validated_count,
                    COUNT(CASE WHEN validation_status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN validation_status = 'rejected' THEN 1 END) as rejected_count,
                    
                    -- Consumption metrics
                    ROUND(AVG(value), 2) as avg_reading,
                    ROUND(SUM(value), 2) as total_consumption,
                    
                    -- Input method breakdown
                    COUNT(CASE WHEN input_method = 'manual' THEN 1 END) as manual_count,
                    COUNT(CASE WHEN input_method = 'photo_ocr' THEN 1 END) as photo_count,
                    COUNT(CASE WHEN input_method = 'api_integration' THEN 1 END) as api_count,
                    
                    -- Date range
                    MIN(reading_date) as earliest_reading,
                    MAX(reading_date) as latest_reading
                    
                FROM meter_readings 
                WHERE tenant_id = ? 
                  AND created_at >= ?
            ";
            
            $result = DB::selectOne($sql, [$tenantId, $startDate]);
            
            return [
                'total_readings' => (int) $result->total_readings,
                'validated_count' => (int) $result->validated_count,
                'pending_count' => (int) $result->pending_count,
                'rejected_count' => (int) $result->rejected_count,
                'validation_rate' => $result->total_readings > 0 
                    ? round(($result->validated_count / $result->total_readings) * 100, 1)
                    : 0,
                'avg_reading' => (float) $result->avg_reading,
                'total_consumption' => (float) $result->total_consumption,
                'manual_count' => (int) $result->manual_count,
                'photo_count' => (int) $result->photo_count,
                'api_count' => (int) $result->api_count,
                'earliest_reading' => $result->earliest_reading,
                'latest_reading' => $result->latest_reading,
            ];
        });
    }

    /**
     * Consumption Calculation - Batch Optimized
     */
    public function calculateConsumptionBatch(Collection $readings): Collection
    {
        // Group readings by meter and zone for efficient calculation
        $grouped = $readings->groupBy(function ($reading) {
            return $reading->meter_id . '_' . ($reading->zone ?? 'default');
        });
        
        return $grouped->map(function ($meterReadings) {
            // Sort by date for proper consumption calculation
            $sorted = $meterReadings->sortBy('reading_date');
            $previous = null;
            
            return $sorted->map(function ($reading) use (&$previous) {
                $consumption = $previous ? $reading->value - $previous->value : 0;
                $reading->calculated_consumption = max(0, $consumption); // Prevent negative
                $previous = $reading;
                return $reading;
            });
        })->flatten();
    }

    /**
     * Clear related caches when data changes
     */
    public function clearCache(int $tenantId): void
    {
        $patterns = [
            "readings_eloquent_{$tenantId}_*",
            "readings_qb_{$tenantId}_*",
            "readings_raw_{$tenantId}_*",
            "dashboard_metrics_{$tenantId}_*",
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}