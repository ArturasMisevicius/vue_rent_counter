<?php

declare(strict_types=1);

namespace App\Services\Optimized;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database-Specific Optimization Service
 * 
 * Provides optimizations tailored to specific database engines
 */
final readonly class DatabaseSpecificOptimizations
{
    /**
     * POSTGRESQL OPTIMIZATIONS
     */
    
    /**
     * Partial Indexes for PostgreSQL
     * Only index rows that meet certain conditions
     */
    public function createPostgreSQLPartialIndexes(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Partial index for active meter readings only
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_meter_readings_active_validated
            ON meter_readings (tenant_id, reading_date, meter_id)
            WHERE validation_status = 'validated' AND created_at >= NOW() - INTERVAL '1 year'
        ");

        // Partial index for pending validations only
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_meter_readings_pending_validation
            ON meter_readings (tenant_id, created_at, entered_by)
            WHERE validation_status IN ('pending', 'requires_review')
        ");

        // Partial index for recent invoices
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_invoices_recent_unpaid
            ON invoices (tenant_id, due_date, total_amount)
            WHERE status IN ('finalized', 'overdue') AND created_at >= NOW() - INTERVAL '2 years'
        ");
    }

    /**
     * Expression Indexes for PostgreSQL
     * Index computed values for faster queries
     */
    public function createPostgreSQLExpressionIndexes(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Index on month/year for time-based grouping
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_meter_readings_month_year
            ON meter_readings (tenant_id, EXTRACT(YEAR FROM reading_date), EXTRACT(MONTH FROM reading_date))
        ");

        // Index on consumption calculation
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_meter_readings_consumption_calc
            ON meter_readings (meter_id, (value - LAG(value) OVER (PARTITION BY meter_id ORDER BY reading_date)))
            WHERE validation_status = 'validated'
        ");

        // Index on invoice total with tax
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_invoices_total_with_tax
            ON invoices (tenant_id, (total_amount * (1 + COALESCE(tax_rate, 0))))
            WHERE status != 'draft'
        ");
    }

    /**
     * GIN/GiST Indexes for Full-Text Search
     */
    public function createPostgreSQLFullTextIndexes(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Add tsvector column for full-text search
        DB::statement("
            ALTER TABLE meter_readings 
            ADD COLUMN IF NOT EXISTS search_vector tsvector
        ");

        // Update search vector with relevant data
        DB::statement("
            UPDATE meter_readings 
            SET search_vector = to_tsvector('english', 
                COALESCE(validation_notes, '') || ' ' ||
                COALESCE(zone, '') || ' ' ||
                COALESCE(input_method::text, '')
            )
        ");

        // Create GIN index for fast full-text search
        DB::statement("
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_meter_readings_search
            ON meter_readings USING gin(search_vector)
        ");

        // Create trigger to maintain search vector
        DB::statement("
            CREATE OR REPLACE FUNCTION update_meter_reading_search_vector()
            RETURNS trigger AS $$
            BEGIN
                NEW.search_vector := to_tsvector('english',
                    COALESCE(NEW.validation_notes, '') || ' ' ||
                    COALESCE(NEW.zone, '') || ' ' ||
                    COALESCE(NEW.input_method::text, '')
                );
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            DROP TRIGGER IF EXISTS trigger_update_meter_reading_search_vector ON meter_readings;
            CREATE TRIGGER trigger_update_meter_reading_search_vector
                BEFORE INSERT OR UPDATE ON meter_readings
                FOR EACH ROW EXECUTE FUNCTION update_meter_reading_search_vector();
        ");
    }

    /**
     * Materialized Views for PostgreSQL
     */
    public function createPostgreSQLMaterializedViews(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Materialized view for consumption summaries
        DB::statement("
            CREATE MATERIALIZED VIEW IF NOT EXISTS mv_meter_consumption_summary AS
            SELECT 
                m.tenant_id,
                m.id as meter_id,
                m.serial_number,
                m.type,
                p.name as property_name,
                
                -- Last 30 days consumption
                COALESCE(SUM(CASE 
                    WHEN mr.reading_date >= CURRENT_DATE - INTERVAL '30 days'
                    AND mr.consumption > 0
                    THEN mr.consumption 
                END), 0) as consumption_30d,
                
                -- Last 90 days consumption  
                COALESCE(SUM(CASE 
                    WHEN mr.reading_date >= CURRENT_DATE - INTERVAL '90 days'
                    AND mr.consumption > 0
                    THEN mr.consumption 
                END), 0) as consumption_90d,
                
                -- Average monthly consumption
                ROUND(
                    COALESCE(SUM(CASE 
                        WHEN mr.reading_date >= CURRENT_DATE - INTERVAL '365 days'
                        AND mr.consumption > 0
                        THEN mr.consumption 
                    END), 0) / 12, 2
                ) as avg_monthly_consumption,
                
                -- Latest reading
                MAX(mr.reading_date) as latest_reading_date,
                MAX(mr.value) as latest_reading_value,
                
                -- Statistics
                COUNT(mr.id) as total_readings,
                CURRENT_TIMESTAMP as last_updated
                
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
            
            GROUP BY m.tenant_id, m.id, m.serial_number, m.type, p.name
            WITH DATA;
        ");

        // Create unique index on materialized view
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS idx_mv_meter_consumption_summary_pk
            ON mv_meter_consumption_summary (tenant_id, meter_id)
        ");

        // Refresh materialized view (should be scheduled)
        DB::statement("REFRESH MATERIALIZED VIEW CONCURRENTLY mv_meter_consumption_summary");
    }

    /**
     * Table Partitioning for PostgreSQL
     */
    public function createPostgreSQLPartitioning(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Create partitioned table for meter readings (by date)
        DB::statement("
            CREATE TABLE IF NOT EXISTS meter_readings_partitioned (
                LIKE meter_readings INCLUDING ALL
            ) PARTITION BY RANGE (reading_date);
        ");

        // Create monthly partitions for current and next year
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;

        for ($year = $currentYear; $year <= $nextYear; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $startDate = sprintf('%d-%02d-01', $year, $month);
                $endDate = date('Y-m-01', strtotime($startDate . ' +1 month'));
                
                DB::statement("
                    CREATE TABLE IF NOT EXISTS meter_readings_y{$year}m{$month}
                    PARTITION OF meter_readings_partitioned
                    FOR VALUES FROM ('{$startDate}') TO ('{$endDate}')
                ");
            }
        }
    }

    /**
     * MYSQL OPTIMIZATIONS
     */

    /**
     * MySQL Index Hints and Query Optimization
     */
    public function optimizeMySQLQueries(): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return [];
        }

        // Use index hints for complex queries
        $sql = "
            SELECT /*+ USE_INDEX(mr, mr_tenant_date_meter_idx) */
                mr.id,
                mr.reading_date,
                mr.value,
                m.serial_number
            FROM meter_readings mr
            FORCE INDEX (mr_tenant_date_meter_idx)
            JOIN meters m ON mr.meter_id = m.id
            WHERE mr.tenant_id = ?
              AND mr.reading_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY mr.reading_date DESC
            LIMIT 100
        ";

        return DB::select($sql, [1]);
    }

    /**
     * MySQL Covering Indexes
     */
    public function createMySQLCoveringIndexes(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Covering index for dashboard queries
        DB::statement("
            CREATE INDEX idx_meter_readings_dashboard_covering
            ON meter_readings (tenant_id, reading_date, validation_status, value, meter_id)
        ");

        // Covering index for meter listing with latest reading
        DB::statement("
            CREATE INDEX idx_meters_with_latest_reading
            ON meters (tenant_id, property_id, type, serial_number, id)
        ");

        // Covering index for invoice queries
        DB::statement("
            CREATE INDEX idx_invoices_tenant_status_covering
            ON invoices (tenant_id, status, due_date, total_amount, property_id, created_at)
        ");
    }

    /**
     * MySQL Table Partitioning
     */
    public function createMySQLPartitioning(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Create partitioned table for audit logs
        DB::statement("
            CREATE TABLE IF NOT EXISTS meter_reading_audits_partitioned (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                meter_reading_id BIGINT UNSIGNED NOT NULL,
                changed_by BIGINT UNSIGNED NOT NULL,
                change_type VARCHAR(50) NOT NULL,
                old_values JSON,
                new_values JSON,
                change_reason TEXT,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id, created_at),
                KEY idx_reading_id (meter_reading_id),
                KEY idx_changed_by (changed_by),
                KEY idx_change_type (change_type)
            )
            PARTITION BY RANGE (YEAR(created_at)) (
                PARTITION p2023 VALUES LESS THAN (2024),
                PARTITION p2024 VALUES LESS THAN (2025),
                PARTITION p2025 VALUES LESS THAN (2026),
                PARTITION p_future VALUES LESS THAN MAXVALUE
            )
        ");
    }

    /**
     * SQLITE OPTIMIZATIONS (for development/testing)
     */

    /**
     * SQLite-specific optimizations
     */
    public function optimizeSQLite(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        // Enable WAL mode for better concurrency
        DB::statement("PRAGMA journal_mode=WAL");
        
        // Optimize for performance
        DB::statement("PRAGMA synchronous=NORMAL");
        DB::statement("PRAGMA cache_size=10000");
        DB::statement("PRAGMA temp_store=MEMORY");
        
        // Enable foreign key constraints
        DB::statement("PRAGMA foreign_keys=ON");
        
        // Analyze tables for better query planning
        DB::statement("ANALYZE");
    }

    /**
     * GENERAL DATABASE OPTIMIZATIONS
     */

    /**
     * Update table statistics for better query planning
     */
    public function updateTableStatistics(): void
    {
        $driver = DB::getDriverName();
        
        match ($driver) {
            'mysql' => DB::statement("ANALYZE TABLE meter_readings, meters, invoices, properties"),
            'pgsql' => DB::statement("ANALYZE meter_readings, meters, invoices, properties"),
            'sqlite' => DB::statement("ANALYZE"),
            default => null
        };
    }

    /**
     * Check and optimize table fragmentation
     */
    public function optimizeTableFragmentation(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            // Check fragmentation
            $fragmented = DB::select("
                SELECT table_name, 
                       ROUND(data_free/1024/1024, 2) as fragmented_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                  AND data_free > 100*1024*1024  -- More than 100MB fragmented
            ");
            
            // Optimize fragmented tables
            foreach ($fragmented as $table) {
                DB::statement("OPTIMIZE TABLE {$table->table_name}");
            }
        }
    }

    /**
     * Database-specific configuration recommendations
     */
    public function getDatabaseConfigRecommendations(): array
    {
        $driver = DB::getDriverName();
        
        return match ($driver) {
            'mysql' => [
                'innodb_buffer_pool_size' => '70% of available RAM',
                'innodb_log_file_size' => '256M or higher',
                'innodb_flush_log_at_trx_commit' => '2 for better performance',
                'query_cache_size' => '0 (disabled in MySQL 8.0+)',
                'max_connections' => 'Based on application needs',
            ],
            'pgsql' => [
                'shared_buffers' => '25% of available RAM',
                'effective_cache_size' => '75% of available RAM',
                'work_mem' => '4MB per connection',
                'maintenance_work_mem' => '256MB or higher',
                'checkpoint_completion_target' => '0.9',
                'wal_buffers' => '16MB',
            ],
            'sqlite' => [
                'journal_mode' => 'WAL',
                'synchronous' => 'NORMAL',
                'cache_size' => '10000 pages',
                'temp_store' => 'MEMORY',
            ],
            default => []
        };
    }

    /**
     * Monitor database performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $driver = DB::getDriverName();
        
        return match ($driver) {
            'mysql' => $this->getMySQLMetrics(),
            'pgsql' => $this->getPostgreSQLMetrics(),
            'sqlite' => $this->getSQLiteMetrics(),
            default => []
        };
    }

    private function getMySQLMetrics(): array
    {
        $status = collect(DB::select("SHOW STATUS"))->pluck('Value', 'Variable_name');
        
        return [
            'queries_per_second' => $status['Queries'] ?? 0,
            'slow_queries' => $status['Slow_queries'] ?? 0,
            'connections' => $status['Threads_connected'] ?? 0,
            'innodb_buffer_pool_hit_rate' => $this->calculateMySQLBufferPoolHitRate($status),
        ];
    }

    private function getPostgreSQLMetrics(): array
    {
        $stats = DB::select("
            SELECT 
                sum(numbackends) as connections,
                sum(xact_commit) as commits,
                sum(xact_rollback) as rollbacks,
                sum(blks_read) as blocks_read,
                sum(blks_hit) as blocks_hit
            FROM pg_stat_database
        ")[0];
        
        return [
            'connections' => $stats->connections,
            'commits' => $stats->commits,
            'rollbacks' => $stats->rollbacks,
            'cache_hit_rate' => $stats->blocks_hit / ($stats->blocks_hit + $stats->blocks_read) * 100,
        ];
    }

    private function getSQLiteMetrics(): array
    {
        return [
            'page_count' => DB::select("PRAGMA page_count")[0]->page_count ?? 0,
            'page_size' => DB::select("PRAGMA page_size")[0]->page_size ?? 0,
            'cache_size' => DB::select("PRAGMA cache_size")[0]->cache_size ?? 0,
        ];
    }

    private function calculateMySQLBufferPoolHitRate(object $status): float
    {
        $reads = $status['Innodb_buffer_pool_reads'] ?? 0;
        $requests = $status['Innodb_buffer_pool_read_requests'] ?? 0;
        
        if ($requests == 0) {
            return 0;
        }
        
        return (1 - ($reads / $requests)) * 100;
    }
}