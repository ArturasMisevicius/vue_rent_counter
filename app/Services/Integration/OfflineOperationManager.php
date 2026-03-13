<?php

declare(strict_types=1);

namespace App\Services\Integration;

/**
 * Minimal stub for offline operation management.
 * 
 * This is a simple stub implementation to ensure project compilation.
 * No complex logic implemented - just basic method signatures.
 * 
 * @package App\Services\Integration
 */
final readonly class OfflineOperationManager
{
    public function __construct() {}

    /**
     * Store operation for offline execution.
     */
    public function storeOperation(string $operationType, array $data): string
    {
        // Stub: Return dummy operation ID
        return uniqid('offline_', true);
    }

    /**
     * Get pending offline operations.
     */
    public function getPendingOperations(): array
    {
        // Stub: Return empty array
        return [];
    }

    /**
     * Mark operation as completed.
     */
    public function markCompleted(string $operationId): void
    {
        // Stub: Do nothing
    }

    /**
     * Check if offline mode is enabled.
     */
    public function isOfflineModeEnabled(): bool
    {
        // Stub: Always return false
        return false;
    }

    /**
     * Enable offline mode.
     */
    public function enableOfflineMode(): void
    {
        // Stub: Do nothing
    }

    /**
     * Disable offline mode.
     */
    public function disableOfflineMode(): void
    {
        // Stub: Do nothing
    }
}