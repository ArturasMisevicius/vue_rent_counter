<?php

declare(strict_types=1);

namespace App\Services\Integration;

/**
 * Minimal stub for data synchronization.
 * 
 * This is a simple stub implementation to ensure project compilation.
 * No complex logic implemented - just basic method signatures.
 * 
 * @package App\Services\Integration
 */
final readonly class DataSynchronizationService
{
    public function __construct() {}

    /**
     * Synchronize data with external service.
     */
    public function synchronize(string $serviceName, array $data): array
    {
        // Stub: Return empty sync result
        return [
            'synchronized' => 0,
            'errors' => 0,
            'skipped' => 0,
        ];
    }

    /**
     * Get synchronization status.
     */
    public function getSyncStatus(string $serviceName): array
    {
        // Stub: Return basic status
        return [
            'service' => $serviceName,
            'status' => 'idle',
            'last_sync' => null,
            'pending_items' => 0,
        ];
    }

    /**
     * Queue data for synchronization.
     */
    public function queueForSync(string $serviceName, array $data): string
    {
        // Stub: Return dummy queue ID
        return uniqid('sync_', true);
    }

    /**
     * Check if synchronization is in progress.
     */
    public function isSyncInProgress(string $serviceName): bool
    {
        // Stub: Always return false
        return false;
    }

    /**
     * Cancel pending synchronization.
     */
    public function cancelSync(string $serviceName): void
    {
        // Stub: Do nothing
    }
}