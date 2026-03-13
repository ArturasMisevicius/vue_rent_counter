<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\IntegrationException;
use App\Models\Organization;
use App\Services\Integration\IntegrationResilienceHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job to retry failed integration operations when external services become available.
 * 
 * This job is queued when external service operations fail and need to be retried
 * later when the service becomes available again.
 * 
 * @package App\Jobs
 * @author Laravel Development Team
 * @since 1.0.0
 */
final class RetryIntegrationOperation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $timeout = 300; // 5 minutes
    public int $backoff = 60; // 1 minute

    /**
     * Create a new job instance.
     * 
     * @param string $serviceName The external service name
     * @param array<string, mixed> $operationData Operation data to retry
     * @param int|null $tenantId Optional tenant ID for scoping
     * @param string $jobId Unique job identifier
     */
    public function __construct(
        private readonly string $serviceName,
        private readonly array $operationData,
        private readonly ?int $tenantId,
        private readonly string $jobId,
    ) {
        $this->onQueue('integrations');
    }

    /**
     * Execute the job.
     */
    public function handle(IntegrationResilienceHandler $resilienceHandler): void
    {
        Log::info("Retrying integration operation", [
            'service' => $this->serviceName,
            'tenant_id' => $this->tenantId,
            'job_id' => $this->jobId,
            'attempt' => $this->attempts(),
        ]);

        $tenant = $this->tenantId ? Organization::find($this->tenantId) : null;

        try {
            // Check if service is still unavailable
            if ($resilienceHandler->isInMaintenanceMode($this->serviceName)) {
                Log::info("Service in maintenance mode, releasing job", [
                    'service' => $this->serviceName,
                    'job_id' => $this->jobId,
                ]);
                
                $this->release(300); // Retry in 5 minutes
                return;
            }

            // Perform health check
            $healthStatus = $resilienceHandler->performHealthCheck($this->serviceName);
            
            if (!$healthStatus['status']->isAvailable()) {
                Log::warning("Service still unavailable, releasing job", [
                    'service' => $this->serviceName,
                    'status' => $healthStatus['status']->value,
                    'job_id' => $this->jobId,
                ]);
                
                $this->release($this->calculateBackoffDelay());
                return;
            }

            // Execute the operation
            $result = $this->executeOperation($resilienceHandler, $tenant);

            Log::info("Integration operation retry successful", [
                'service' => $this->serviceName,
                'tenant_id' => $this->tenantId,
                'job_id' => $this->jobId,
                'result' => $result,
            ]);
        } catch (IntegrationException $e) {
            Log::error("Integration operation retry failed", [
                'service' => $this->serviceName,
                'tenant_id' => $this->tenantId,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'is_retryable' => $e->isRetryable(),
                'attempt' => $this->attempts(),
            ]);

            if ($e->isRetryable() && $this->attempts() < $this->tries) {
                $this->release($e->getRetryDelay() ?: $this->calculateBackoffDelay());
                return;
            }

            // Non-retryable error or max attempts reached
            $this->fail($e);
        } catch (Throwable $e) {
            Log::error("Unexpected error in integration retry job", [
                'service' => $this->serviceName,
                'tenant_id' => $this->tenantId,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->calculateBackoffDelay());
                return;
            }

            $this->fail($e);
        }
    }

    /**
     * Execute the integration operation.
     * 
     * @param IntegrationResilienceHandler $resilienceHandler
     * @param Organization|null $tenant
     * 
     * @return array<string, mixed> Operation result
     */
    private function executeOperation(
        IntegrationResilienceHandler $resilienceHandler,
        ?Organization $tenant
    ): array {
        $operation = $this->operationData['operation'] ?? 'unknown';

        return match ($operation) {
            'meter_reading_sync' => $this->executeMeterReadingSync($resilienceHandler, $tenant),
            'billing_calculation' => $this->executeBillingCalculation($resilienceHandler, $tenant),
            'provider_data_sync' => $this->executeProviderDataSync($resilienceHandler, $tenant),
            'ocr_processing' => $this->executeOcrProcessing($resilienceHandler, $tenant),
            default => throw IntegrationException::operationFailed(
                $this->serviceName,
                new \InvalidArgumentException("Unknown operation: {$operation}")
            ),
        };
    }

    /**
     * Execute meter reading synchronization.
     * 
     * @param IntegrationResilienceHandler $resilienceHandler
     * @param Organization|null $tenant
     * 
     * @return array<string, mixed> Sync result
     */
    private function executeMeterReadingSync(
        IntegrationResilienceHandler $resilienceHandler,
        ?Organization $tenant
    ): array {
        return $resilienceHandler->synchronizeOfflineData($this->serviceName, $tenant);
    }

    /**
     * Execute billing calculation.
     * 
     * @param IntegrationResilienceHandler $resilienceHandler
     * @param Organization|null $tenant
     * 
     * @return array<string, mixed> Calculation result
     */
    private function executeBillingCalculation(
        IntegrationResilienceHandler $resilienceHandler,
        ?Organization $tenant
    ): array {
        $calculationData = $this->operationData['calculation_data'] ?? [];
        
        return $resilienceHandler->executeWithResilience(
            $this->serviceName,
            function () use ($calculationData) {
                // Placeholder for billing calculation logic
                return [
                    'calculation_id' => $calculationData['id'] ?? null,
                    'status' => 'completed',
                    'result' => $calculationData,
                ];
            },
            [],
            false // Don't allow offline for billing calculations
        );
    }

    /**
     * Execute provider data synchronization.
     * 
     * @param IntegrationResilienceHandler $resilienceHandler
     * @param Organization|null $tenant
     * 
     * @return array<string, mixed> Sync result
     */
    private function executeProviderDataSync(
        IntegrationResilienceHandler $resilienceHandler,
        ?Organization $tenant
    ): array {
        $providerData = $this->operationData['provider_data'] ?? [];
        
        return $resilienceHandler->executeWithResilience(
            $this->serviceName,
            function () use ($providerData) {
                // Placeholder for provider data sync logic
                return [
                    'provider_id' => $providerData['id'] ?? null,
                    'status' => 'synchronized',
                    'data' => $providerData,
                ];
            }
        );
    }

    /**
     * Execute OCR processing.
     * 
     * @param IntegrationResilienceHandler $resilienceHandler
     * @param Organization|null $tenant
     * 
     * @return array<string, mixed> OCR result
     */
    private function executeOcrProcessing(
        IntegrationResilienceHandler $resilienceHandler,
        ?Organization $tenant
    ): array {
        $ocrData = $this->operationData['ocr_data'] ?? [];
        
        return $resilienceHandler->executeWithResilience(
            $this->serviceName,
            function () use ($ocrData) {
                // Placeholder for OCR processing logic
                return [
                    'image_path' => $ocrData['image_path'] ?? null,
                    'extracted_text' => $ocrData['extracted_text'] ?? '',
                    'confidence' => $ocrData['confidence'] ?? 0.0,
                    'status' => 'processed',
                ];
            }
        );
    }

    /**
     * Calculate backoff delay with exponential backoff.
     */
    private function calculateBackoffDelay(): int
    {
        $attempt = $this->attempts();
        return min(300, $this->backoff * (2 ** ($attempt - 1))); // Max 5 minutes
    }

    /**
     * Handle job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("Integration retry job failed permanently", [
            'service' => $this->serviceName,
            'tenant_id' => $this->tenantId,
            'job_id' => $this->jobId,
            'attempts' => $this->attempts(),
            'error' => $exception?->getMessage(),
        ]);

        // Optionally notify administrators about permanent failure
        // This could trigger alerts or create support tickets
    }

    /**
     * Get the tags that should be assigned to the job.
     * 
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'integration',
            "service:{$this->serviceName}",
            "tenant:{$this->tenantId}",
            "job:{$this->jobId}",
        ];
    }
}