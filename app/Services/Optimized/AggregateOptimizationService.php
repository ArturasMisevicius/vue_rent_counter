<?php

declare(strict_types=1);

namespace App\Services\Optimized;

use App\Models\MeterReading;
use App\Models\Meter;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Aggregate Optimization Service
 * 
 * Optimizes COUNT, SUM, AVG operations for dashboard widgets and reports
 */
final readonly class AggregateOptimizationService
{
    public function __construct(
        private int $cacheTtl = 600, // 10 minutes for aggregates
    ) {}

    /**
     * BAD: Multiple separate queries for dashboard
     */
    public function getDashboardStatsBad(int $tenantId): array
    {
        // This creates 6 separate database queries
        $totalReadings = MeterReading::where('tenant_id', $tenantId)->count();
        $validatedReadings = MeterReading::where('tenant_id', $tenantId)
            ->where('validation_status', 'validated')->count();
        $pendingReadings = MeterReading::where('tenant_id', $tenantId)
            ->where('validation_status', 'pending')->count();
        $avgConsumption = MeterReading::where('tenant_id', $tenantId)->avg('value');
        $totalConsumption = MeterReading::where('tenant_id', $tenantId)->sum('value');
        $meterCount = Meter::where('tenant_id', $tenantId)->count();

        return compact(
            'totalReadings', 'validatedReadings', 'pendingReadings',
            'avgConsumption', 'totalConsumption', 'meterCount'
        );
    }

    /**
     * GOOD: Single query with conditional aggregation
     */
    public function getDashboardStatsGood(int $tenantId): array
    {
        $cacheKey = "dashboard_stats_{$tenantId}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tenantId) {
            // Single query with multiple aggregations
            $readingStats = DB::table('meter_readings')
                ->selectRaw("
                    COUNT(*) as total_readings,
                    COUNT(CASE WHEN validation_status = 'validated' THEN 1 END) as validated_readings,
                    COUNT(CASE WHEN validation_status = 'pending' THEN 1 END) as pending_readings,
                    COUNT(CASE WHEN validation_status = 'rejected' THEN 1 END) as rejected_readings,
                    ROUND(AVG(value), 2) as avg_consumption,
                    ROUND(SUM(value), 2) as total_consumption,
                    MIN(reading_date) as earliest_reading,
                    MAX(reading_date) as latest_reading
                ")
                ->where('tenant_id', $tenantId)
                ->first();

            // Separate query for meter count (different table)
            $meterCount = DB::table('meters')
                ->where('tenant_id', $tenantId)
                ->count();

            return [
                'total_readings' => (int) $readingStats->total_readings,
                'validated_readings' => (int) $readingStats->validated_readings,
                'pending_readings' => (int) $readingStats->pending_readings,
                'rejected_readings' => (int) $readingStats->rejected_readings,
                'validation_rate' => $readingStats->total_readings > 0 
                    ? round(($readingStats->validated_readings / $readingStats->total_readings) * 100, 1)
                    : 0,
                'avg_consumption' => (float) $readingStats->avg_consumption,
                'total_consumption' => (float) $readingStats->total_consumption,
                'meter_count' => $meterCount,
                'earliest_reading' => $readingStats->earliest_reading,
                'latest_reading' => $readingStats->latest_reading,
            ];
        });
    }

    /**
     * Optimized withCount() vs counting related records
     */
    public function getMetersWithReadingCounts(int $tenantId): array
    {
        // BAD: N+1 queries
        // $meters = Meter::where('tenant_id', $tenantId)->get();
        // foreach ($meters as $meter) {
        //     $meter->readings_count = $meter->readings()->count();
        //     $meter->validated_count = $meter->readings()->where('validation_status', 'validated')->count();
        // }

        // GOOD: Using withCount() with conditions
        return Meter::where('tenant_id', $tenantId)
            ->withCount([
                'readings',
                'readings as validated_count' => function ($query) {
                    $query->where('validation_status', 'validated');
                },
                'readings as pending_count' => function ($query) {
                    $query->where('validation_status', 'pending');
                },
                'readings as recent_count' => function ($query) {
                    $query->where('created_at', '>=', now()->subDays(30));
                },
            ])
            ->get()
            ->toArray();
    }

    /**
     * Database aggregates vs application logic
     */
    public function getConsumptionTrends(int $tenantId, int $months = 6): array
    {
        $cacheKey = "consumption_trends_{$tenantId}_{$months}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tenantId, $months) {
            // Use database aggregation with window functions
            $sql = "
                SELECT 
                    DATE_FORMAT(reading_date, '%Y-%m') as month,
                    meter_id,
                    m.serial_number,
                    m.type as meter_type,
                    p.name as property_name,
                    
                    -- Monthly consumption calculation
                    SUM(
                        CASE 
                            WHEN prev_value IS NOT NULL 
                            THEN GREATEST(0, value - prev_value)
                            ELSE 0 
                        END
                    ) as monthly_consumption,
                    
                    -- Average daily consumption
                    ROUND(
                        SUM(
                            CASE 
                                WHEN prev_value IS NOT NULL 
                                THEN GREATEST(0, value - prev_value)
                                ELSE 0 
                            END
                        ) / DAY(LAST_DAY(reading_date)), 2
                    ) as avg_daily_consumption,
                    
                    -- Reading count for the month
                    COUNT(*) as reading_count,
                    
                    -- Min/Max values for the month
                    MIN(value) as min_reading,
                    MAX(value) as max_reading
                    
                FROM (
                    SELECT 
                        mr.*,
                        LAG(mr.value) OVER (
                            PARTITION BY mr.meter_id, mr.zone 
                            ORDER BY mr.reading_date
                        ) as prev_value
                    FROM meter_readings mr
                    WHERE mr.tenant_id = ?
                      AND mr.reading_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                      AND mr.validation_status = 'validated'
                ) mr_with_prev
                JOIN meters m ON mr_with_prev.meter_id = m.id
                JOIN properties p ON m.property_id = p.id
                
                GROUP BY 
                    DATE_FORMAT(reading_date, '%Y-%m'),
                    meter_id,
                    m.serial_number,
                    m.type,
                    p.name
                    
                ORDER BY month DESC, property_name, meter_id
            ";
            
            return DB::select($sql, [$tenantId, $months]);
        });
    }

    /**
     * Optimized invoice aggregations
     */
    public function getInvoiceMetrics(int $tenantId): array
    {
        $cacheKey = "invoice_metrics_{$tenantId}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tenantId) {
            return DB::table('invoices')
                ->selectRaw("
                    -- Status breakdown
                    COUNT(*) as total_invoices,
                    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
                    COUNT(CASE WHEN status = 'finalized' THEN 1 END) as finalized_count,
                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                    COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count,
                    
                    -- Financial metrics
                    ROUND(SUM(total_amount), 2) as total_amount,
                    ROUND(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 2) as paid_amount,
                    ROUND(SUM(CASE WHEN status = 'overdue' THEN total_amount ELSE 0 END), 2) as overdue_amount,
                    ROUND(AVG(total_amount), 2) as avg_invoice_amount,
                    
                    -- Timing metrics
                    ROUND(AVG(DATEDIFF(paid_at, created_at)), 1) as avg_payment_days,
                    MIN(created_at) as earliest_invoice,
                    MAX(created_at) as latest_invoice,
                    
                    -- Current month metrics
                    COUNT(CASE WHEN created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 END) as current_month_count,
                    ROUND(SUM(CASE WHEN created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN total_amount ELSE 0 END), 2) as current_month_amount
                ")
                ->where('tenant_id', $tenantId)
                ->first();
        });
    }

    /**
     * Materialized aggregates for heavy calculations
     */
    public function createMaterializedAggregates(int $tenantId): void
    {
        // Create a materialized view for complex aggregations
        // This would be run as a scheduled job
        
        DB::statement("
            CREATE OR REPLACE VIEW meter_consumption_summary AS
            SELECT 
                m.id as meter_id,
                m.tenant_id,
                m.serial_number,
                m.type,
                p.id as property_id,
                p.name as property_name,
                
                -- Last 30 days
                COALESCE(SUM(CASE 
                    WHEN mr.reading_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND mr.consumption > 0
                    THEN mr.consumption 
                END), 0) as consumption_30d,
                
                -- Last 90 days
                COALESCE(SUM(CASE 
                    WHEN mr.reading_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                    AND mr.consumption > 0
                    THEN mr.consumption 
                END), 0) as consumption_90d,
                
                -- Last 365 days
                COALESCE(SUM(CASE 
                    WHEN mr.reading_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)
                    AND mr.consumption > 0
                    THEN mr.consumption 
                END), 0) as consumption_365d,
                
                -- Average monthly consumption
                ROUND(
                    COALESCE(SUM(CASE 
                        WHEN mr.reading_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)
                        AND mr.consumption > 0
                        THEN mr.consumption 
                    END), 0) / 12, 2
                ) as avg_monthly_consumption,
                
                -- Latest reading info
                MAX(mr.reading_date) as latest_reading_date,
                MAX(mr.value) as latest_reading_value,
                
                -- Reading frequency
                COUNT(mr.id) as total_readings,
                COUNT(CASE WHEN mr.reading_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as readings_30d
                
            FROM meters m
            JOIN properties p ON m.property_id = p.id
            LEFT JOIN (
                SELECT 
                    meter_id,
                    reading_date,
                    value,
                    GREATEST(0, 
                        value - LAG(value) OVER (
                            PARTITION BY meter_id, zone 
                            ORDER BY reading_date
                        )
                    ) as consumption
                FROM meter_readings
                WHERE validation_status = 'validated'
            ) mr ON m.id = mr.meter_id
            
            WHERE m.tenant_id = ?
            GROUP BY m.id, m.tenant_id, m.serial_number, m.type, p.id, p.name
        ");
    }

    /**
     * Real-time vs cached aggregations strategy
     */
    public function getAggregateStrategy(string $type, int $tenantId): array
    {
        return match ($type) {
            // Real-time for critical business metrics
            'invoice_totals' => $this->getRealTimeInvoiceTotals($tenantId),
            
            // Cached for dashboard widgets
            'dashboard_stats' => $this->getDashboardStatsGood($tenantId),
            
            // Pre-calculated for reports
            'consumption_report' => $this->getPreCalculatedConsumption($tenantId),
            
            default => []
        };
    }

    private function getRealTimeInvoiceTotals(int $tenantId): array
    {
        // No caching for financial data
        return DB::table('invoices')
            ->selectRaw("
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_total,
                SUM(CASE WHEN status = 'overdue' THEN total_amount ELSE 0 END) as overdue_total,
                SUM(CASE WHEN status IN ('finalized', 'overdue') THEN total_amount ELSE 0 END) as outstanding_total
            ")
            ->where('tenant_id', $tenantId)
            ->first();
    }

    private function getPreCalculatedConsumption(int $tenantId): array
    {
        // Use materialized view for complex reports
        return DB::table('meter_consumption_summary')
            ->where('tenant_id', $tenantId)
            ->get()
            ->toArray();
    }

    /**
     * Clear aggregate caches when data changes
     */
    public function clearAggregateCache(int $tenantId): void
    {
        $patterns = [
            "dashboard_stats_{$tenantId}",
            "consumption_trends_{$tenantId}_*",
            "invoice_metrics_{$tenantId}",
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}