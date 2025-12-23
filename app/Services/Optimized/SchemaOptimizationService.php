<?php

declare(strict_types=1);

namespace App\Services\Optimized;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema Optimization Service
 * 
 * Provides recommendations and implementations for database schema optimizations
 */
final readonly class SchemaOptimizationService
{
    /**
     * 1. DENORMALIZATION STRATEGIES
     */

    /**
     * Add denormalized columns for frequently accessed data
     */
    public function addDenormalizedColumns(): void
    {
        // Add meter info to readings for faster queries
        if (!Schema::hasColumn('meter_readings', 'meter_serial_number')) {
            Schema::table('meter_readings', function ($table) {
                $table->string('meter_serial_number', 50)->nullable()->after('meter_id');
                $table->string('meter_type', 20)->nullable()->after('meter_serial_number');
                $table->string('property_name', 255)->nullable()->after('meter_type');
                
                // Index for denormalized search
                $table->index(['tenant_id', 'meter_serial_number'], 'idx_readings_meter_serial');
                $table->index(['tenant_id', 'property_name'], 'idx_readings_property_name');
            });
        }

        // Add consumption calculation to readings
        if (!Schema::hasColumn('meter_readings', 'calculated_consumption')) {
            Schema::table('meter_readings', function ($table) {
                $table->decimal('calculated_consumption', 10, 2)->nullable()->after('value');
                $table->decimal('previous_reading_value', 10, 2)->nullable()->after('calculated_consumption');
                
                // Index for consumption queries
                $table->index(['tenant_id', 'calculated_consumption'], 'idx_readings_consumption');
            });
        }

        // Add invoice totals to properties for dashboard
        if (!Schema::hasColumn('properties', 'total_outstanding_amount')) {
            Schema::table('properties', function ($table) {
                $table->decimal('total_outstanding_amount', 12, 2)->default(0)->after('name');
                $table->decimal('last_month_consumption', 10, 2)->default(0)->after('total_outstanding_amount');
                $table->integer('active_meters_count')->default(0)->after('last_month_consumption');
                $table->timestamp('last_reading_date')->nullable()->after('active_meters_count');
                
                // Index for property summaries
                $table->index(['tenant_id', 'total_outstanding_amount'], 'idx_properties_outstanding');
            });
        }
    }

    /**
     * Update denormalized data (should be called via observers/jobs)
     */
    public function updateDenormalizedData(int $tenantId): void
    {
        // Update meter info in readings
        DB::statement("
            UPDATE meter_readings mr
            JOIN meters m ON mr.meter_id = m.id
            JOIN properties p ON m.property_id = p.id
            SET 
                mr.meter_serial_number = m.serial_number,
                mr.meter_type = m.type,
                mr.property_name = p.name
            WHERE mr.tenant_id = ? 
              AND (mr.meter_serial_number IS NULL OR mr.property_name IS NULL)
        ", [$tenantId]);

        // Update consumption calculations
        DB::statement("
            UPDATE meter_readings mr1
            JOIN (
                SELECT 
                    mr.id,
                    mr.value - COALESCE(prev_mr.value, 0) as consumption,
                    prev_mr.value as prev_value
                FROM meter_readings mr
                LEFT JOIN meter_readings prev_mr ON (
                    prev_mr.meter_id = mr.meter_id 
                    AND prev_mr.zone <=> mr.zone
                    AND prev_mr.reading_date < mr.reading_date
                    AND prev_mr.id = (
                        SELECT MAX(id) 
                        FROM meter_readings 
                        WHERE meter_id = mr.meter_id 
                          AND zone <=> mr.zone
                          AND reading_date < mr.reading_date
                    )
                )
                WHERE mr.tenant_id = ?
            ) calc ON mr1.id = calc.id
            SET 
                mr1.calculated_consumption = GREATEST(0, calc.consumption),
                mr1.previous_reading_value = calc.prev_value
        ", [$tenantId]);

        // Update property summaries
        DB::statement("
            UPDATE properties p
            JOIN (
                SELECT 
                    p.id as property_id,
                    COALESCE(SUM(CASE WHEN i.status IN ('finalized', 'overdue') THEN i.total_amount END), 0) as outstanding,
                    COALESCE(SUM(CASE WHEN mr.reading_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN mr.calculated_consumption END), 0) as monthly_consumption,
                    COUNT(DISTINCT m.id) as meter_count,
                    MAX(mr.reading_date) as last_reading
                FROM properties p
                LEFT JOIN meters m ON p.id = m.property_id
                LEFT JOIN meter_readings mr ON m.id = mr.meter_id AND mr.validation_status = 'validated'
                LEFT JOIN invoices i ON p.id = i.property_id
                WHERE p.tenant_id = ?
                GROUP BY p.id
            ) summary ON p.id = summary.property_id
            SET 
                p.total_outstanding_amount = summary.outstanding,
                p.last_month_consumption = summary.monthly_consumption,
                p.active_meters_count = summary.meter_count,
                p.last_reading_date = summary.last_reading
        ", [$tenantId]);
    }

    /**
     * 2. COMPUTED/GENERATED COLUMNS
     */

    /**
     * Add computed columns for MySQL 5.7+ / PostgreSQL
     */
    public function addComputedColumns(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Add computed column for invoice total with tax
            DB::statement("
                ALTER TABLE invoices 
                ADD COLUMN total_with_tax DECIMAL(12,2) 
                GENERATED ALWAYS AS (total_amount * (1 + COALESCE(tax_rate, 0))) STORED
            ");

            // Add computed column for reading month/year
            DB::statement("
                ALTER TABLE meter_readings 
                ADD COLUMN reading_year_month VARCHAR(7) 
                GENERATED ALWAYS AS (DATE_FORMAT(reading_date, '%Y-%m')) STORED
            ");

            // Index on computed columns
            DB::statement("CREATE INDEX idx_invoices_total_with_tax ON invoices (total_with_tax)");
            DB::statement("CREATE INDEX idx_readings_year_month ON meter_readings (tenant_id, reading_year_month)");
        }

        if ($driver === 'pgsql') {
            // PostgreSQL generated columns (v12+)
            DB::statement("
                ALTER TABLE invoices 
                ADD COLUMN IF NOT EXISTS total_with_tax DECIMAL(12,2) 
                GENERATED ALWAYS AS (total_amount * (1 + COALESCE(tax_rate, 0))) STORED
            ");

            DB::statement("
                ALTER TABLE meter_readings 
                ADD COLUMN IF NOT EXISTS reading_year_month VARCHAR(7) 
                GENERATED ALWAYS AS (TO_CHAR(reading_date, 'YYYY-MM')) STORED
            ");
        }
    }

    /**
     * 3. COLUMN TYPE OPTIMIZATION
     */

    /**
     * Optimize column types for better performance and storage
     */
    public function optimizeColumnTypes(): array
    {
        $recommendations = [];

        // Analyze current column usage
        $columnStats = $this->analyzeColumnUsage();

        foreach ($columnStats as $table => $columns) {
            foreach ($columns as $column => $stats) {
                $recommendation = $this->getColumnTypeRecommendation($stats);
                if ($recommendation) {
                    $recommendations[] = [
                        'table' => $table,
                        'column' => $column,
                        'current_type' => $stats['current_type'],
                        'recommended_type' => $recommendation,
                        'reason' => $stats['reason'],
                    ];
                }
            }
        }

        return $recommendations;
    }

    /**
     * Apply column type optimizations
     */
    public function applyColumnOptimizations(): void
    {
        // Optimize ID columns - use BIGINT only when necessary
        $this->optimizeIdColumns();

        // Optimize string columns - use appropriate VARCHAR lengths
        $this->optimizeStringColumns();

        // Optimize numeric columns - use appropriate precision
        $this->optimizeNumericColumns();

        // Optimize date/time columns
        $this->optimizeDateTimeColumns();
    }

    private function optimizeIdColumns(): void
    {
        // Check if any tables have more than 2 billion records (INT limit)
        $largeTables = DB::select("
            SELECT table_name, table_rows
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
              AND table_rows > 1000000000
        ");

        // Most utility management systems won't need BIGINT for all IDs
        // Keep BIGINT for high-volume tables like meter_readings, audit logs
        $highVolumeTables = ['meter_readings', 'meter_reading_audits', 'audit_logs'];

        foreach ($largeTables as $table) {
            if (!in_array($table->table_name, $highVolumeTables)) {
                // Consider if BIGINT is really needed
                echo "Consider if BIGINT is necessary for {$table->table_name} (current rows: {$table->table_rows})\n";
            }
        }
    }

    private function optimizeStringColumns(): void
    {
        // Analyze VARCHAR column usage
        $stringColumns = [
            'meters.serial_number' => 50,  // Usually 20-30 chars
            'properties.name' => 255,      // Usually under 100 chars
            'users.email' => 255,          // Standard email length
            'meter_readings.zone' => 20,   // 'day', 'night', etc.
        ];

        foreach ($stringColumns as $column => $recommendedLength) {
            [$table, $col] = explode('.', $column);
            
            // Check actual usage
            $maxLength = DB::selectOne("
                SELECT MAX(LENGTH({$col})) as max_length 
                FROM {$table} 
                WHERE {$col} IS NOT NULL
            ")->max_length ?? 0;

            if ($maxLength > 0 && $maxLength < $recommendedLength * 0.7) {
                echo "Consider reducing {$column} from current size to {$recommendedLength}\n";
            }
        }
    }

    private function optimizeNumericColumns(): void
    {
        // Check decimal precision usage
        $numericColumns = [
            'meter_readings.value' => ['precision' => 10, 'scale' => 2],
            'invoices.total_amount' => ['precision' => 12, 'scale' => 2],
            'tariffs.rate' => ['precision' => 8, 'scale' => 4],
        ];

        foreach ($numericColumns as $column => $config) {
            [$table, $col] = explode('.', $column);
            
            // Analyze actual precision needs
            $stats = DB::selectOne("
                SELECT 
                    MAX({$col}) as max_value,
                    MIN({$col}) as min_value,
                    AVG(LENGTH(SUBSTRING_INDEX({$col}, '.', -1))) as avg_decimal_places
                FROM {$table} 
                WHERE {$col} IS NOT NULL
            ");

            if ($stats && $stats->max_value < pow(10, $config['precision'] - $config['scale'] - 2)) {
                echo "Consider reducing precision for {$column}\n";
            }
        }
    }

    private function optimizeDateTimeColumns(): void
    {
        // Use DATE instead of DATETIME when time is not needed
        $dateOnlyColumns = [
            'meters.installation_date',
            'invoices.due_date',
        ];

        foreach ($dateOnlyColumns as $column) {
            [$table, $col] = explode('.', $column);
            
            // Check if time component is actually used
            $hasTime = DB::selectOne("
                SELECT COUNT(*) as count
                FROM {$table}
                WHERE TIME({$col}) != '00:00:00'
            ")->count ?? 0;

            if ($hasTime == 0) {
                echo "Consider changing {$column} from DATETIME to DATE\n";
            }
        }
    }

    /**
     * 4. JSON COLUMN OPTIMIZATION
     */

    /**
     * Optimize JSON column queries
     */
    public function optimizeJsonColumns(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Create functional indexes on JSON paths
            DB::statement("
                CREATE INDEX idx_meter_readings_json_primary 
                ON meter_readings ((CAST(reading_values->>'$.primary' AS DECIMAL(10,2))))
            ");

            DB::statement("
                CREATE INDEX idx_service_config_pricing_model
                ON service_configurations ((rate_schedules->>'$.pricing_model'))
            ");
        }

        if ($driver === 'pgsql') {
            // PostgreSQL GIN indexes for JSON
            DB::statement("
                CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_meter_readings_json_gin
                ON meter_readings USING gin(reading_values)
            ");

            DB::statement("
                CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_service_config_json_gin
                ON service_configurations USING gin(rate_schedules)
            ");

            // Specific path indexes
            DB::statement("
                CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_readings_json_primary
                ON meter_readings ((reading_values->>'primary'))
            ");
        }
    }

    /**
     * 5. STORAGE ENGINE OPTIMIZATION (MySQL)
     */

    /**
     * Optimize MySQL storage engines and settings
     */
    public function optimizeMySQLStorage(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Check current storage engines
        $tables = DB::select("
            SELECT table_name, engine, table_rows, 
                   ROUND(data_length/1024/1024, 2) as data_mb,
                   ROUND(index_length/1024/1024, 2) as index_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");

        foreach ($tables as $table) {
            // Recommend InnoDB for transactional tables
            if ($table->engine !== 'InnoDB' && in_array($table->table_name, [
                'meter_readings', 'invoices', 'users', 'properties'
            ])) {
                echo "Recommend converting {$table->table_name} to InnoDB\n";
            }

            // Check for tables that might benefit from compression
            if ($table->data_mb > 100) {
                echo "Consider ROW_FORMAT=COMPRESSED for {$table->table_name} ({$table->data_mb}MB)\n";
            }
        }
    }

    /**
     * Helper methods
     */
    private function analyzeColumnUsage(): array
    {
        // This would analyze actual column usage patterns
        // Return mock data for example
        return [
            'meter_readings' => [
                'id' => [
                    'current_type' => 'BIGINT',
                    'max_value' => 1000000,
                    'reason' => 'Could use INT if under 2B records'
                ],
                'value' => [
                    'current_type' => 'DECIMAL(10,2)',
                    'max_value' => 99999.99,
                    'reason' => 'Precision appropriate'
                ]
            ]
        ];
    }

    private function getColumnTypeRecommendation(array $stats): ?string
    {
        // Logic to recommend optimal column type based on usage
        if ($stats['current_type'] === 'BIGINT' && $stats['max_value'] < 2000000000) {
            return 'INT';
        }

        return null;
    }

    /**
     * 6. CONSTRAINT OPTIMIZATION
     */

    /**
     * Add appropriate constraints for data integrity and performance
     */
    public function addOptimalConstraints(): void
    {
        // Add check constraints for data validation
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE meter_readings 
                ADD CONSTRAINT chk_reading_value_positive 
                CHECK (value >= 0)
            ");

            DB::statement("
                ALTER TABLE invoices 
                ADD CONSTRAINT chk_invoice_amount_positive 
                CHECK (total_amount >= 0)
            ");
        }

        // Add partial unique constraints
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                CREATE UNIQUE INDEX CONCURRENTLY IF NOT EXISTS idx_meter_readings_unique_per_date
                ON meter_readings (meter_id, reading_date, zone)
                WHERE validation_status != 'rejected'
            ");
        }
    }
}