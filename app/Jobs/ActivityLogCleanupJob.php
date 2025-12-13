<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OrganizationActivityLog;
use Carbon\Carbon;

/**
 * Job for cleaning up old activity logs
 * 
 * Removes activity logs older than the configured retention period
 * to prevent database bloat while maintaining audit trail integrity
 */
class ActivityLogCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $retentionDays = 365,
        public int $batchSize = 1000
    ) {
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoffDate = Carbon::now()->subDays($this->retentionDays);
        
        Log::info('Starting activity log cleanup', [
            'retention_days' => $this->retentionDays,
            'cutoff_date' => $cutoffDate->toDateString(),
            'batch_size' => $this->batchSize,
        ]);

        $totalDeleted = 0;
        $batchCount = 0;

        do {
            // Delete in batches to avoid long-running transactions
            $deleted = DB::table('organization_activity_log')
                ->where('created_at', '<', $cutoffDate)
                ->limit($this->batchSize)
                ->delete();

            $totalDeleted += $deleted;
            $batchCount++;

            Log::debug('Activity log cleanup batch completed', [
                'batch' => $batchCount,
                'deleted_in_batch' => $deleted,
                'total_deleted' => $totalDeleted,
            ]);

            // Small delay between batches to reduce database load
            if ($deleted > 0) {
                usleep(100000); // 100ms
            }

        } while ($deleted > 0);

        Log::info('Activity log cleanup completed', [
            'total_deleted' => $totalDeleted,
            'batches_processed' => $batchCount,
            'retention_days' => $this->retentionDays,
        ]);

        // Update statistics
        $this->updateCleanupStats($totalDeleted);
    }

    /**
     * Update cleanup statistics
     */
    private function updateCleanupStats(int $deletedCount): void
    {
        try {
            // Store cleanup statistics for monitoring
            DB::table('system_configurations')->updateOrInsert(
                ['key' => 'activity_log_cleanup_stats'],
                [
                    'value' => json_encode([
                        'last_cleanup' => now()->toISOString(),
                        'records_deleted' => $deletedCount,
                        'retention_days' => $this->retentionDays,
                    ]),
                    'updated_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to update cleanup stats', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get cleanup statistics
     */
    public static function getCleanupStats(): ?array
    {
        try {
            $config = DB::table('system_configurations')
                ->where('key', 'activity_log_cleanup_stats')
                ->first();

            return $config ? json_decode($config->value, true) : null;
        } catch (\Exception $e) {
            Log::warning('Failed to get cleanup stats', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Activity log cleanup job failed', [
            'retention_days' => $this->retentionDays,
            'batch_size' => $this->batchSize,
            'error' => $exception->getMessage(),
        ]);
    }
}