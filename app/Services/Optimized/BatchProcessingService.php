<?php

declare(strict_types=1);

namespace App\Services\Optimized;

use App\Models\MeterReading;
use App\Models\Meter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Batch Processing and Connection Optimization Service
 * 
 * Handles large dataset processing efficiently with memory management
 */
final readonly class BatchProcessingService
{
    public function __construct(
        private int $chunkSize = 1000,
        private int $memoryLimit = 128, // MB
    ) {}

    /**
     * 1. CHUNK QUERIES FOR LARGE DATASETS
     */

    /**
     * Process large datasets in chunks to avoid memory issues
     */
    public function processLargeDatasetChunked(int $tenantId, callable $processor): void
    {
        MeterReading::where('tenant_id', $tenantId)
            ->with(['meter:id,serial_number,type'])
            ->chunk($this->chunkSize, function (Collection $readings) use ($processor) {
                $processor($readings);
                
                // Force garbage collection after each chunk
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
                
                // Check memory usage
                $this->checkMemoryUsage();
            });
    }

    /**
     * Chunk with custom ordering for consistent results
     */
    public function processChunkedWithOrdering(int $tenantId, callable $processor): void
    {
        MeterReading::where('tenant_id', $tenantId)
            ->orderBy('id') // Consistent ordering for chunking
            ->chunk($this->chunkSize, function (Collection $readings) use ($processor) {
                $processor($readings);
                $this->checkMemoryUsage();
            });
    }

    /**
     * 2. LAZY COLLECTIONS FOR MEMORY EFFICIENCY
     */

    /**
     * Use lazy collections for streaming large datasets
     */
    public function processWithLazyCollection(int $tenantId): LazyCollection
    {
        return MeterReading::where('tenant_id', $tenantId)
            ->cursor() // Returns LazyCollection
            ->map(function (MeterReading $reading) {
                // Process each reading individually
                return $this->processReading($reading);
            })
            ->filter(function ($result) {
                // Filter results without loading all into memory
                return $result !== null;
            });
    }

    /**
     * Lazy collection with chunked processing
     */
    public function processLazyChunked(int $tenantId, int $chunkSize = 100): LazyCollection
    {
        return MeterReading::where('tenant_id', $tenantId)
            ->cursor()
            ->chunk($chunkSize)
            ->map(function ($chunk) {
                // Process chunk and return results
                return $this->processReadingChunk($chunk);
            })
            ->flatten();
    }

    /**
     * 3. CURSOR ITERATION FOR VERY LARGE DATASETS
     */

    /**
     * Use database cursor for streaming without memory buildup
     */
    public function processWithCursor(int $tenantId): \Generator
    {
        $cursor = DB::table('meter_readings')
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->cursor();

        foreach ($cursor as $reading) {
            yield $this->processRawReading($reading);
            
            // Yield control back to prevent memory buildup
            if (memory_get_usage(true) > $this->memoryLimit * 1024 * 1024) {
                gc_collect_cycles();
            }
        }
    }

    /**
     * 4. BATCH INSERTS AND UPDATES
     */

    /**
     * Efficient batch insert for large datasets
     */
    public function batchInsertReadings(array $readingsData): void
    {
        $chunks = array_chunk($readingsData, $this->chunkSize);
        
        DB::transaction(function () use ($chunks) {
            foreach ($chunks as $chunk) {
                // Add timestamps to each record
                $chunk = array_map(function ($reading) {
                    $reading['created_at'] = now();
                    $reading['updated_at'] = now();
                    return $reading;
                }, $chunk);
                
                // Batch insert
                DB::table('meter_readings')->insert($chunk);
            }
        });
    }

    /**
     * Batch update with case statements for better performance
     */
    public function batchUpdateReadings(array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        $ids = array_keys($updates);
        $cases = [];
        $bindings = [];

        // Build CASE statements for each field
        foreach (['validation_status', 'validated_by', 'validated_at'] as $field) {
            $case = "CASE id ";
            foreach ($updates as $id => $data) {
                if (isset($data[$field])) {
                    $case .= "WHEN ? THEN ? ";
                    $bindings[] = $id;
                    $bindings[] = $data[$field];
                }
            }
            $case .= "ELSE {$field} END";
            $cases[$field] = $case;
        }

        // Execute batch update
        $sql = "UPDATE meter_readings SET ";
        $setClauses = [];
        foreach ($cases as $field => $case) {
            $setClauses[] = "{$field} = {$case}";
        }
        $sql .= implode(', ', $setClauses);
        $sql .= " WHERE id IN (" . str_repeat('?,', count($ids) - 1) . "?)";
        
        $bindings = array_merge($bindings, $ids);
        
        DB::update($sql, $bindings);
    }

    /**
     * 5. UPSERT OPERATIONS (INSERT OR UPDATE)
     */

    /**
     * Efficient upsert for MySQL
     */
    public function upsertReadingsMySQL(array $readingsData): void
    {
        $chunks = array_chunk($readingsData, $this->chunkSize);
        
        foreach ($chunks as $chunk) {
            $values = [];
            $bindings = [];
            
            foreach ($chunk as $reading) {
                $values[] = "(?, ?, ?, ?, ?, ?, ?)";
                $bindings = array_merge($bindings, [
                    $reading['tenant_id'],
                    $reading['meter_id'],
                    $reading['reading_date'],
                    $reading['value'],
                    $reading['zone'],
                    $reading['entered_by'],
                    now(),
                ]);
            }
            
            $sql = "
                INSERT INTO meter_readings (tenant_id, meter_id, reading_date, value, zone, entered_by, created_at)
                VALUES " . implode(', ', $values) . "
                ON DUPLICATE KEY UPDATE
                    value = VALUES(value),
                    entered_by = VALUES(entered_by),
                    updated_at = NOW()
            ";
            
            DB::statement($sql, $bindings);
        }
    }

    /**
     * Efficient upsert for PostgreSQL
     */
    public function upsertReadingsPostgreSQL(array $readingsData): void
    {
        $chunks = array_chunk($readingsData, $this->chunkSize);
        
        foreach ($chunks as $chunk) {
            $values = [];
            $bindings = [];
            
            foreach ($chunk as $reading) {
                $values[] = "(?, ?, ?, ?, ?, ?, ?)";
                $bindings = array_merge($bindings, [
                    $reading['tenant_id'],
                    $reading['meter_id'],
                    $reading['reading_date'],
                    $reading['value'],
                    $reading['zone'],
                    $reading['entered_by'],
                    now(),
                ]);
            }
            
            $sql = "
                INSERT INTO meter_readings (tenant_id, meter_id, reading_date, value, zone, entered_by, created_at)
                VALUES " . implode(', ', $values) . "
                ON CONFLICT (meter_id, reading_date, zone)
                DO UPDATE SET
                    value = EXCLUDED.value,
                    entered_by = EXCLUDED.entered_by,
                    updated_at = NOW()
            ";
            
            DB::statement($sql, $bindings);
        }
    }

    /**
     * 6. MEMORY MANAGEMENT
     */

    /**
     * Monitor and manage memory usage during batch operations
     */
    private function checkMemoryUsage(): void
    {
        $currentMemory = memory_get_usage(true) / 1024 / 1024; // MB
        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024; // MB
        
        if ($currentMemory > $this->memoryLimit) {
            // Force garbage collection
            gc_collect_cycles();
            
            // Log memory warning
            \Log::warning('High memory usage during batch processing', [
                'current_memory_mb' => $currentMemory,
                'peak_memory_mb' => $peakMemory,
                'limit_mb' => $this->memoryLimit,
            ]);
        }
    }

    /**
     * Clear model cache to free memory
     */
    private function clearModelCache(): void
    {
        // Clear Eloquent model cache
        if (method_exists(MeterReading::class, 'flushEventListeners')) {
            MeterReading::flushEventListeners();
        }
        
        // Clear any static caches
        gc_collect_cycles();
    }

    /**
     * 7. CONNECTION POOLING AND MANAGEMENT
     */

    /**
     * Configure connection pooling for high-traffic scenarios
     */
    public function configureConnectionPooling(): array
    {
        return [
            'mysql' => [
                'pool_size' => 20,
                'max_connections' => 100,
                'connection_timeout' => 30,
                'idle_timeout' => 600,
                'settings' => [
                    'wait_timeout' => 28800,
                    'interactive_timeout' => 28800,
                    'max_allowed_packet' => '64M',
                ]
            ],
            'postgresql' => [
                'pool_size' => 20,
                'max_connections' => 100,
                'connection_timeout' => 30,
                'idle_timeout' => 600,
                'settings' => [
                    'statement_timeout' => '30s',
                    'idle_in_transaction_session_timeout' => '60s',
                ]
            ],
            'redis' => [
                'pool_size' => 10,
                'max_connections' => 50,
                'connection_timeout' => 5,
                'read_timeout' => 10,
            ]
        ];
    }

    /**
     * 8. READ/WRITE SPLITTING
     */

    /**
     * Use read replicas for heavy read operations
     */
    public function processWithReadReplica(int $tenantId, callable $processor): void
    {
        // Use read connection for data retrieval
        $readings = DB::connection('mysql_read')
            ->table('meter_readings')
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->cursor();

        $batch = [];
        $batchCount = 0;

        foreach ($readings as $reading) {
            $batch[] = $reading;
            $batchCount++;

            if ($batchCount >= $this->chunkSize) {
                $processor($batch);
                $batch = [];
                $batchCount = 0;
            }
        }

        // Process remaining items
        if (!empty($batch)) {
            $processor($batch);
        }
    }

    /**
     * 9. PARALLEL PROCESSING
     */

    /**
     * Process data in parallel using multiple database connections
     */
    public function processInParallel(int $tenantId, int $workers = 4): void
    {
        // Get total count
        $totalCount = MeterReading::where('tenant_id', $tenantId)->count();
        $chunkSize = ceil($totalCount / $workers);

        $processes = [];
        
        for ($i = 0; $i < $workers; $i++) {
            $offset = $i * $chunkSize;
            
            // In a real implementation, you'd use process forking or job queues
            $this->processChunk($tenantId, $offset, $chunkSize);
        }
    }

    private function processChunk(int $tenantId, int $offset, int $limit): void
    {
        MeterReading::where('tenant_id', $tenantId)
            ->offset($offset)
            ->limit($limit)
            ->chunk($this->chunkSize, function (Collection $readings) {
                foreach ($readings as $reading) {
                    $this->processReading($reading);
                }
            });
    }

    /**
     * 10. STREAMING EXPORTS
     */

    /**
     * Stream large exports without memory buildup
     */
    public function streamExport(int $tenantId): \Generator
    {
        $query = DB::table('meter_readings as mr')
            ->join('meters as m', 'mr.meter_id', '=', 'm.id')
            ->join('properties as p', 'm.property_id', '=', 'p.id')
            ->where('mr.tenant_id', $tenantId)
            ->select([
                'mr.reading_date',
                'mr.value',
                'mr.zone',
                'm.serial_number',
                'p.name as property_name'
            ])
            ->orderBy('mr.reading_date');

        // Stream results
        foreach ($query->cursor() as $row) {
            yield [
                'date' => $row->reading_date,
                'value' => $row->value,
                'zone' => $row->zone,
                'meter' => $row->serial_number,
                'property' => $row->property_name,
            ];
        }
    }

    /**
     * Helper methods
     */
    private function processReading(MeterReading $reading): ?array
    {
        // Example processing logic
        if ($reading->validation_status === 'validated') {
            return [
                'id' => $reading->id,
                'consumption' => $reading->calculated_consumption,
                'processed_at' => now(),
            ];
        }
        
        return null;
    }

    private function processReadingChunk($chunk): array
    {
        return $chunk->map(function ($reading) {
            return $this->processReading($reading);
        })->filter()->toArray();
    }

    private function processRawReading(object $reading): array
    {
        return [
            'id' => $reading->id,
            'value' => $reading->value,
            'processed_at' => now()->toISOString(),
        ];
    }
}