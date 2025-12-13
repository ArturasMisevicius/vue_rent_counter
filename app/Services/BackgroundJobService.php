<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\BulkOperationJob;
use App\Jobs\ExportGenerationJob;
use App\Jobs\ActivityLogCleanupJob;
use App\Jobs\SubscriptionExpiryCheckJob;
use Illuminate\Support\Facades\Log;

/**
 * BackgroundJobService handles dispatching background jobs for superadmin operations
 * 
 * Provides a centralized interface for queuing long-running operations
 * that should not block the user interface
 */
class BackgroundJobService
{
    /**
     * Dispatch bulk operation job
     */
    public function dispatchBulkOperation(
        string $operation,
        array $recordIds,
        array $parameters = [],
        ?int $userId = null
    ): void {
        Log::info('Dispatching bulk operation job', [
            'operation' => $operation,
            'record_count' => count($recordIds),
            'user_id' => $userId,
        ]);

        BulkOperationJob::dispatch($operation, $recordIds, $parameters, $userId);
    }

    /**
     * Dispatch export generation job
     */
    public function dispatchExportGeneration(
        string $exportType,
        string $format,
        array $filters = [],
        ?int $userId = null,
        bool $emailResults = false
    ): void {
        Log::info('Dispatching export generation job', [
            'type' => $exportType,
            'format' => $format,
            'user_id' => $userId,
            'email_results' => $emailResults,
        ]);

        ExportGenerationJob::dispatch($exportType, $format, $filters, $userId, $emailResults);
    }

    /**
     * Dispatch activity log cleanup job
     */
    public function dispatchActivityLogCleanup(int $retentionDays = 365, int $batchSize = 1000): void
    {
        Log::info('Dispatching activity log cleanup job', [
            'retention_days' => $retentionDays,
            'batch_size' => $batchSize,
        ]);

        ActivityLogCleanupJob::dispatch($retentionDays, $batchSize);
    }

    /**
     * Dispatch subscription expiry check job
     */
    public function dispatchSubscriptionExpiryCheck(): void
    {
        Log::info('Dispatching subscription expiry check job');

        SubscriptionExpiryCheckJob::dispatch();
    }

    /**
     * Get job queue statistics
     */
    public function getQueueStats(): array
    {
        try {
            // Get pending jobs count by queue
            $stats = [
                'bulk_operations' => $this->getQueueJobCount('bulk-operations'),
                'exports' => $this->getQueueJobCount('exports'),
                'maintenance' => $this->getQueueJobCount('maintenance'),
                'subscriptions' => $this->getQueueJobCount('subscriptions'),
                'default' => $this->getQueueJobCount('default'),
            ];

            // Get failed jobs count
            $stats['failed_jobs'] = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();

            return $stats;
        } catch (\Exception $e) {
            Log::error('Failed to get queue stats', [
                'error' => $e->getMessage(),
            ]);

            return [
                'bulk_operations' => 0,
                'exports' => 0,
                'maintenance' => 0,
                'subscriptions' => 0,
                'default' => 0,
                'failed_jobs' => 0,
            ];
        }
    }

    /**
     * Get job count for specific queue
     */
    private function getQueueJobCount(string $queue): int
    {
        try {
            return \Illuminate\Support\Facades\DB::table('jobs')
                ->where('queue', $queue)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Retry failed jobs
     */
    public function retryFailedJobs(?array $jobIds = null): int
    {
        try {
            if ($jobIds) {
                // Retry specific jobs
                $retried = 0;
                foreach ($jobIds as $jobId) {
                    \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => $jobId]);
                    $retried++;
                }
                return $retried;
            } else {
                // Retry all failed jobs
                \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => 'all']);
                return \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
            }
        } catch (\Exception $e) {
            Log::error('Failed to retry jobs', [
                'job_ids' => $jobIds,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Clear failed jobs
     */
    public function clearFailedJobs(): int
    {
        try {
            $count = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
            \Illuminate\Support\Facades\Artisan::call('queue:flush');
            return $count;
        } catch (\Exception $e) {
            Log::error('Failed to clear failed jobs', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get recent job history
     */
    public function getRecentJobHistory(int $limit = 50): array
    {
        try {
            // This is a simplified implementation
            // In production, you might want to use a package like Laravel Horizon
            // or implement proper job tracking
            
            $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')
                ->select('id', 'queue', 'payload', 'exception', 'failed_at')
                ->orderBy('failed_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'job_class' => $payload['displayName'] ?? 'Unknown',
                        'status' => 'failed',
                        'failed_at' => $job->failed_at,
                        'exception' => $job->exception,
                    ];
                });

            return $failedJobs->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get job history', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Check if queue workers are running
     */
    public function areWorkersRunning(): bool
    {
        try {
            // Simple check - in production you might want more sophisticated monitoring
            $recentJobs = \Illuminate\Support\Facades\DB::table('jobs')
                ->where('created_at', '>', now()->subMinutes(5))
                ->count();

            $processingJobs = \Illuminate\Support\Facades\DB::table('jobs')
                ->whereNotNull('reserved_at')
                ->count();

            // If there are recent jobs but none processing, workers might be down
            return $recentJobs === 0 || $processingJobs > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}