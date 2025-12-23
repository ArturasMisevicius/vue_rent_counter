<?php

declare(strict_types=1);

namespace App\Services\Optimized;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Database Monitoring and Profiling Service
 * 
 * Provides comprehensive database performance monitoring and profiling
 */
final readonly class DatabaseMonitoringService
{
    public function __construct(
        private int $slowQueryThreshold = 1000, // 1 second in milliseconds
    ) {}

    /**
     * 1. LARAVEL TELESCOPE SETUP
     */

    /**
     * Configure Telescope for database monitoring
     */
    public function configureTelescope(): array
    {
        return [
            'telescope_config' => [
                'watchers' => [
                    'query' => [
                        'enabled' => true,
                        'slow' => 100, // Log queries slower than 100ms
                    ],
                    'request' => [
                        'enabled' => true,
                        'size_limit' => 64,
                    ],
                    'cache' => [
                        'enabled' => true,
                    ],
                ],
            ],
            'instructions' => [
                '1. Install Telescope: composer require laravel/telescope',
                '2. Publish config: php artisan telescope:install',
                '3. Run migrations: php artisan migrate',
                '4. Add to .env: TELESCOPE_ENABLED=true',
                '5. Access at: /telescope',
            ]
        ];
    }

    /**
     * 2. QUERY LOGGING AND ANALYSIS
     */

    /**
     * Enable comprehensive query logging
     */
    public function enableQueryLogging(): void
    {
        // Log all queries in development
        if (app()->environment('local')) {
            DB::listen(function ($query) {
                $this->logQuery($query);
            });
        }

        // Log only slow queries in production
        if (app()->environment('production')) {
            DB::listen(function ($query) {
                if ($query->time > $this->slowQueryThreshold) {
                    $this->logSlowQuery($query);
                }
            });
        }
    }

    private function logQuery($query): void
    {
        $sql = str_replace(['%', '?'], ['%%', '%s'], $query->sql);
        $fullQuery = vsprintf($sql, $query->bindings);
        
        Log::channel('database')->info('Query executed', [
            'sql' => $fullQuery,
            'time' => $query->time . 'ms',
            'connection' => $query->connectionName,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ]);
    }

    private function logSlowQuery($query): void
    {
        $sql = str_replace(['%', '?'], ['%%', '%s'], $query->sql);
        $fullQuery = vsprintf($sql, $query->bindings);
        
        Log::channel('slow-queries')->warning('Slow query detected', [
            'sql' => $fullQuery,
            'time' => $query->time . 'ms',
            'connection' => $query->connectionName,
            'threshold' => $this->slowQueryThreshold . 'ms',
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
        ]);

        // Store in cache for dashboard
        $this->recordSlowQuery($fullQuery, $query->time);
    }

    /**
     * 3. PERFORMANCE METRICS COLLECTION
     */

    /**
     * Collect comprehensive database performance metrics
     */
    public function collectPerformanceMetrics(): array
    {
        $driver = DB::getDriverName();
        
        return [
            'timestamp' => now()->toISOString(),
            'driver' => $driver,
            'connection_stats' => $this->getConnectionStats(),
            'query_stats' => $this->getQueryStats(),
            'slow_queries' => $this->getSlowQueries(),
            'table_stats' => $this->getTableStats(),
            'index_usage' => $this->getIndexUsage(),
            'cache_stats' => $this->getCacheStats(),
            'recommendations' => $this->getPerformanceRecommendations(),
        ];
    }

    private function getConnectionStats(): array
    {
        $driver = DB::getDriverName();
        
        return match ($driver) {
            'mysql' => $this->getMySQLConnectionStats(),
            'pgsql' => $this->getPostgreSQLConnectionStats(),
            'sqlite' => $this->getSQLiteConnectionStats(),
            default => []
        };
    }

    private function getMySQLConnectionStats(): array
    {
        $status = collect(DB::select("SHOW STATUS"))->pluck('Value', 'Variable_name');
        
        return [
            'connections' => [
                'current' => $status['Threads_connected'] ?? 0,
                'max_used' => $status['Max_used_connections'] ?? 0,
                'total_created' => $status['Connections'] ?? 0,
                'aborted' => $status['Aborted_connects'] ?? 0,
            ],
            'queries' => [
                'total' => $status['Queries'] ?? 0,
                'slow' => $status['Slow_queries'] ?? 0,
                'qps' => $this->calculateQPS($status['Queries'] ?? 0),
            ],
            'innodb' => [
                'buffer_pool_hit_rate' => $this->calculateBufferPoolHitRate($status),
                'buffer_pool_size' => $status['Innodb_buffer_pool_size'] ?? 0,
                'buffer_pool_pages_free' => $status['Innodb_buffer_pool_pages_free'] ?? 0,
            ],
        ];
    }

    private function getPostgreSQLConnectionStats(): array
    {
        $stats = DB::select("
            SELECT 
                sum(numbackends) as connections,
                sum(xact_commit) as commits,
                sum(xact_rollback) as rollbacks,
                sum(blks_read) as blocks_read,
                sum(blks_hit) as blocks_hit,
                sum(tup_returned) as tuples_returned,
                sum(tup_fetched) as tuples_fetched
            FROM pg_stat_database
        ")[0];
        
        return [
            'connections' => $stats->connections,
            'transactions' => [
                'commits' => $stats->commits,
                'rollbacks' => $stats->rollbacks,
                'commit_ratio' => $stats->commits / ($stats->commits + $stats->rollbacks) * 100,
            ],
            'cache' => [
                'hit_rate' => $stats->blocks_hit / ($stats->blocks_hit + $stats->blocks_read) * 100,
                'blocks_read' => $stats->blocks_read,
                'blocks_hit' => $stats->blocks_hit,
            ],
        ];
    }

    private function getSQLiteConnectionStats(): array
    {
        return [
            'page_count' => DB::select("PRAGMA page_count")[0]->page_count ?? 0,
            'page_size' => DB::select("PRAGMA page_size")[0]->page_size ?? 0,
            'cache_size' => DB::select("PRAGMA cache_size")[0]->cache_size ?? 0,
            'journal_mode' => DB::select("PRAGMA journal_mode")[0]->journal_mode ?? 'unknown',
        ];
    }

    private function getQueryStats(): array
    {
        // Get query statistics from cache (populated by query listener)
        return Cache::get('db_query_stats', [
            'total_queries' => 0,
            'slow_queries' => 0,
            'avg_query_time' => 0,
            'queries_per_minute' => 0,
        ]);
    }

    private function getSlowQueries(int $limit = 10): array
    {
        return Cache::get('slow_queries', []);
    }

    private function getTableStats(): array
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            return DB::select("
                SELECT 
                    table_name,
                    table_rows,
                    ROUND(data_length/1024/1024, 2) as data_mb,
                    ROUND(index_length/1024/1024, 2) as index_mb,
                    ROUND((data_length + index_length)/1024/1024, 2) as total_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ");
        }

        if ($driver === 'pgsql') {
            return DB::select("
                SELECT 
                    schemaname,
                    tablename as table_name,
                    n_tup_ins as inserts,
                    n_tup_upd as updates,
                    n_tup_del as deletes,
                    seq_scan,
                    seq_tup_read,
                    idx_scan,
                    idx_tup_fetch
                FROM pg_stat_user_tables
                ORDER BY seq_tup_read DESC
                LIMIT 10
            ");
        }

        return [];
    }

    private function getIndexUsage(): array
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            return DB::select("
                SELECT 
                    table_name,
                    index_name,
                    column_name,
                    cardinality,
                    CASE 
                        WHEN cardinality = 0 THEN 'Unused'
                        WHEN cardinality < 10 THEN 'Low Selectivity'
                        ELSE 'Good'
                    END as status
                FROM information_schema.statistics 
                WHERE table_schema = DATABASE()
                  AND index_name != 'PRIMARY'
                ORDER BY cardinality ASC
                LIMIT 20
            ");
        }

        if ($driver === 'pgsql') {
            return DB::select("
                SELECT 
                    schemaname,
                    tablename,
                    indexname,
                    idx_scan,
                    idx_tup_read,
                    idx_tup_fetch,
                    CASE 
                        WHEN idx_scan = 0 THEN 'Unused'
                        WHEN idx_scan < 100 THEN 'Low Usage'
                        ELSE 'Active'
                    END as status
                FROM pg_stat_user_indexes
                ORDER BY idx_scan ASC
                LIMIT 20
            ");
        }

        return [];
    }

    private function getCacheStats(): array
    {
        // Laravel cache statistics
        return [
            'redis_info' => $this->getRedisInfo(),
            'application_cache' => [
                'hits' => Cache::get('cache_hits', 0),
                'misses' => Cache::get('cache_misses', 0),
                'hit_rate' => $this->calculateCacheHitRate(),
            ],
        ];
    }

    /**
     * 4. AUTOMATED PERFORMANCE RECOMMENDATIONS
     */
    private function getPerformanceRecommendations(): array
    {
        $recommendations = [];
        
        // Check for missing indexes
        $recommendations = array_merge($recommendations, $this->checkMissingIndexes());
        
        // Check for unused indexes
        $recommendations = array_merge($recommendations, $this->checkUnusedIndexes());
        
        // Check for slow queries
        $recommendations = array_merge($recommendations, $this->checkSlowQueries());
        
        // Check table sizes
        $recommendations = array_merge($recommendations, $this->checkTableSizes());
        
        return $recommendations;
    }

    private function checkMissingIndexes(): array
    {
        $recommendations = [];
        
        // Check for foreign keys without indexes
        $unindexedFKs = DB::select("
            SELECT 
                table_name,
                column_name,
                referenced_table_name,
                referenced_column_name
            FROM information_schema.key_column_usage
            WHERE referenced_table_name IS NOT NULL
              AND table_schema = DATABASE()
              AND (table_name, column_name) NOT IN (
                  SELECT table_name, column_name
                  FROM information_schema.statistics
                  WHERE table_schema = DATABASE()
              )
        ");
        
        foreach ($unindexedFKs as $fk) {
            $recommendations[] = [
                'type' => 'missing_index',
                'priority' => 'high',
                'message' => "Add index on {$fk->table_name}.{$fk->column_name} (foreign key)",
                'sql' => "CREATE INDEX idx_{$fk->table_name}_{$fk->column_name} ON {$fk->table_name} ({$fk->column_name})",
            ];
        }
        
        return $recommendations;
    }

    private function checkUnusedIndexes(): array
    {
        $recommendations = [];
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            // Check for unused indexes in MySQL
            $unusedIndexes = DB::select("
                SELECT 
                    table_name,
                    index_name
                FROM information_schema.statistics s
                WHERE table_schema = DATABASE()
                  AND index_name != 'PRIMARY'
                  AND NOT EXISTS (
                      SELECT 1 FROM performance_schema.table_io_waits_summary_by_index_usage u
                      WHERE u.object_schema = s.table_schema
                        AND u.object_name = s.table_name
                        AND u.index_name = s.index_name
                        AND u.count_star > 0
                  )
                GROUP BY table_name, index_name
            ");
            
            foreach ($unusedIndexes as $index) {
                $recommendations[] = [
                    'type' => 'unused_index',
                    'priority' => 'medium',
                    'message' => "Consider dropping unused index {$index->index_name} on {$index->table_name}",
                    'sql' => "DROP INDEX {$index->index_name} ON {$index->table_name}",
                ];
            }
        }
        
        return $recommendations;
    }

    private function checkSlowQueries(): array
    {
        $recommendations = [];
        $slowQueries = $this->getSlowQueries(5);
        
        foreach ($slowQueries as $query) {
            if ($query['count'] > 10) { // Frequently slow query
                $recommendations[] = [
                    'type' => 'slow_query',
                    'priority' => 'high',
                    'message' => "Optimize frequently slow query (executed {$query['count']} times, avg {$query['avg_time']}ms)",
                    'query' => substr($query['sql'], 0, 200) . '...',
                ];
            }
        }
        
        return $recommendations;
    }

    private function checkTableSizes(): array
    {
        $recommendations = [];
        $tableStats = $this->getTableStats();
        
        foreach ($tableStats as $table) {
            if (isset($table->total_mb) && $table->total_mb > 1000) { // > 1GB
                $recommendations[] = [
                    'type' => 'large_table',
                    'priority' => 'medium',
                    'message' => "Large table {$table->table_name} ({$table->total_mb}MB) - consider partitioning or archiving",
                ];
            }
        }
        
        return $recommendations;
    }

    /**
     * 5. REAL-TIME MONITORING DASHBOARD
     */

    /**
     * Get real-time metrics for dashboard
     */
    public function getRealTimeMetrics(): array
    {
        return [
            'current_connections' => $this->getCurrentConnections(),
            'queries_per_second' => $this->getQueriesPerSecond(),
            'slow_query_count' => $this->getSlowQueryCount(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'active_transactions' => $this->getActiveTransactions(),
            'lock_waits' => $this->getLockWaits(),
        ];
    }

    /**
     * 6. AUTOMATED ALERTS
     */

    /**
     * Check for performance issues and send alerts
     */
    public function checkPerformanceAlerts(): void
    {
        $metrics = $this->getRealTimeMetrics();
        
        // Alert on high connection count
        if ($metrics['current_connections'] > 80) {
            $this->sendAlert('high_connections', "High connection count: {$metrics['current_connections']}");
        }
        
        // Alert on low cache hit rate
        if ($metrics['cache_hit_rate'] < 90) {
            $this->sendAlert('low_cache_hit_rate', "Low cache hit rate: {$metrics['cache_hit_rate']}%");
        }
        
        // Alert on many slow queries
        if ($metrics['slow_query_count'] > 10) {
            $this->sendAlert('many_slow_queries', "High slow query count: {$metrics['slow_query_count']}");
        }
    }

    /**
     * Helper methods
     */
    private function recordSlowQuery(string $sql, float $time): void
    {
        $slowQueries = Cache::get('slow_queries', []);
        $queryHash = md5($sql);
        
        if (isset($slowQueries[$queryHash])) {
            $slowQueries[$queryHash]['count']++;
            $slowQueries[$queryHash]['total_time'] += $time;
            $slowQueries[$queryHash]['avg_time'] = $slowQueries[$queryHash]['total_time'] / $slowQueries[$queryHash]['count'];
        } else {
            $slowQueries[$queryHash] = [
                'sql' => $sql,
                'count' => 1,
                'total_time' => $time,
                'avg_time' => $time,
                'first_seen' => now()->toISOString(),
            ];
        }
        
        // Keep only top 50 slow queries
        if (count($slowQueries) > 50) {
            uasort($slowQueries, fn($a, $b) => $b['count'] <=> $a['count']);
            $slowQueries = array_slice($slowQueries, 0, 50, true);
        }
        
        Cache::put('slow_queries', $slowQueries, 3600); // 1 hour
    }

    private function calculateQPS(int $totalQueries): float
    {
        $uptime = Cache::get('db_uptime', 1);
        return $uptime > 0 ? round($totalQueries / $uptime, 2) : 0;
    }

    private function calculateBufferPoolHitRate(object $status): float
    {
        $reads = $status['Innodb_buffer_pool_reads'] ?? 0;
        $requests = $status['Innodb_buffer_pool_read_requests'] ?? 0;
        
        if ($requests == 0) return 0;
        return round((1 - ($reads / $requests)) * 100, 2);
    }

    private function calculateCacheHitRate(): float
    {
        $hits = Cache::get('cache_hits', 0);
        $misses = Cache::get('cache_misses', 0);
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    private function getCurrentConnections(): int
    {
        $driver = DB::getDriverName();
        
        return match ($driver) {
            'mysql' => DB::selectOne("SHOW STATUS LIKE 'Threads_connected'")->Value ?? 0,
            'pgsql' => DB::selectOne("SELECT sum(numbackends) as connections FROM pg_stat_database")->connections ?? 0,
            default => 0
        };
    }

    private function getQueriesPerSecond(): float
    {
        return Cache::get('queries_per_second', 0.0);
    }

    private function getSlowQueryCount(): int
    {
        return count($this->getSlowQueries());
    }

    private function getCacheHitRate(): float
    {
        return $this->calculateCacheHitRate();
    }

    private function getActiveTransactions(): int
    {
        $driver = DB::getDriverName();
        
        return match ($driver) {
            'mysql' => DB::selectOne("SELECT COUNT(*) as count FROM information_schema.innodb_trx")->count ?? 0,
            'pgsql' => DB::selectOne("SELECT COUNT(*) as count FROM pg_stat_activity WHERE state = 'active'")->count ?? 0,
            default => 0
        };
    }

    private function getLockWaits(): int
    {
        $driver = DB::getDriverName();
        
        return match ($driver) {
            'mysql' => DB::selectOne("SELECT COUNT(*) as count FROM information_schema.innodb_lock_waits")->count ?? 0,
            'pgsql' => DB::selectOne("SELECT COUNT(*) as count FROM pg_locks WHERE NOT granted")->count ?? 0,
            default => 0
        };
    }

    private function getRedisInfo(): array
    {
        try {
            return [
                'connected_clients' => 0, // Would get from Redis
                'used_memory' => 0,
                'keyspace_hits' => 0,
                'keyspace_misses' => 0,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function sendAlert(string $type, string $message): void
    {
        Log::warning("Database performance alert: {$type}", [
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ]);
        
        // Could integrate with notification services here
    }
}