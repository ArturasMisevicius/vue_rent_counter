<?php

declare(strict_types=1);

namespace App\Services\ServiceRegistration;

use App\Contracts\ServiceRegistration\ErrorHandlingStrategyInterface;
use App\Contracts\ServiceRegistration\PolicyRegistryInterface;
use App\Services\PolicyRegistryMonitoringService;
use App\ValueObjects\ServiceRegistration\RegistrationResult;
use Illuminate\Contracts\Foundation\Application;

/**
 * Orchestrates service registration with comprehensive error handling and monitoring
 */
final readonly class ServiceRegistrationOrchestrator
{
    public function __construct(
        private Application $app,
        private ErrorHandlingStrategyInterface $errorHandler,
        private ?PolicyRegistryMonitoringService $monitoringService = null,
    ) {}

    /**
     * Register all policies and gates with comprehensive error handling
     */
    public function registerPolicies(): void
    {
        try {
            $policyRegistry = $this->app->make(PolicyRegistryInterface::class);
            
            $policyResults = $this->registerModelPolicies($policyRegistry);
            $gateResults = $this->registerSettingsGates($policyRegistry);
            
            $this->logCombinedResults($policyResults, $gateResults);
            $this->recordMetrics($policyResults, $gateResults);
            
        } catch (\Throwable $e) {
            $this->errorHandler->handleCriticalFailure($e, 'policy_orchestration');
        }
    }

    /**
     * Register model policies with error handling
     */
    private function registerModelPolicies(PolicyRegistryInterface $registry): RegistrationResult
    {
        return $this->errorHandler->handleRegistration(
            operation: fn() => $registry->registerModelPolicies(),
            context: 'model_policies'
        );
    }

    /**
     * Register settings gates with error handling
     */
    private function registerSettingsGates(PolicyRegistryInterface $registry): RegistrationResult
    {
        return $this->errorHandler->handleRegistration(
            operation: fn() => $registry->registerSettingsGates(),
            context: 'settings_gates'
        );
    }

    /**
     * Log combined results from both registration operations
     */
    private function logCombinedResults(RegistrationResult $policyResults, RegistrationResult $gateResults): void
    {
        $combinedResult = new RegistrationResult(
            registered: $policyResults->registered + $gateResults->registered,
            skipped: $policyResults->skipped + $gateResults->skipped,
            errors: [...$policyResults->errors, ...$gateResults->errors],
            durationMs: $policyResults->durationMs + $gateResults->durationMs,
        );

        $this->errorHandler->logResults($combinedResult, 'combined_registration');
    }

    /**
     * Record metrics for monitoring if service is available
     */
    private function recordMetrics(RegistrationResult $policyResults, RegistrationResult $gateResults): void
    {
        if ($this->monitoringService === null) {
            return;
        }

        $totalDuration = ($policyResults->durationMs + $gateResults->durationMs) / 1000; // Convert to seconds
        $totalErrors = $policyResults->getErrorCount() + $gateResults->getErrorCount();

        $this->monitoringService->recordRegistrationMetrics($totalDuration, $totalErrors);
    }

    /**
     * Validate configuration without performing registration
     */
    public function validateConfiguration(): array
    {
        try {
            $policyRegistry = $this->app->make(PolicyRegistryInterface::class);
            return $policyRegistry->validateConfiguration();
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'policies' => ['valid' => 0, 'invalid' => 0, 'errors' => []],
                'gates' => ['valid' => 0, 'invalid' => 0, 'errors' => []],
            ];
        }
    }
}