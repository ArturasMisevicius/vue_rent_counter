<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Integration\IntegrationResilienceHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly string $serviceName,
        private readonly string $errorMessage
    ) {}

    public function handle(IntegrationResilienceHandler $resilienceHandler): void
    {
        Log::info('Retrying failed integration', [
            'service' => $this->serviceName,
            'original_error' => $this->errorMessage,
            'attempt' => $this->attempts() + 1,
        ]);

        try {
            // Check if service is healthy before retrying
            if (!$resilienceHandler->isServiceHealthy($this->serviceName)) {
                Log::warning('Service still unhealthy, skipping retry', [
                    'service' => $this->serviceName,
                ]);
                return;
            }

            // Attempt to synchronize any pending offline data
            $syncResults = $resilienceHandler->synchronizeOfflineData();
            
            Log::info('Integration retry completed', [
                'service' => $this->serviceName,
                'sync_results' => $syncResults,
            ]);

        } catch (\Exception $e) {
            Log::error('Integration retry failed', [
                'service' => $this->serviceName,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts() + 1,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Integration retry job failed permanently', [
            'service' => $this->serviceName,
            'error' => $exception->getMessage(),
            'total_attempts' => $this->attempts(),
        ]);
    }
}