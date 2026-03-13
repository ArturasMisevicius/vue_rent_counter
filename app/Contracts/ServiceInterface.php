<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Base contract for application service classes.
 *
 * Keeps a minimal surface area for common service capabilities:
 * - Identification (for logging/metrics)
 * - Input validation
 * - Availability toggles / feature flags
 */
interface ServiceInterface
{
    public function getServiceName(): string;

    /**
     * Validate input data before processing.
     *
     * Implementations may throw \InvalidArgumentException on invalid input.
     *
     * @param array<string, mixed> $data
     */
    public function validateInput(array $data): bool;

    /**
     * Check if the service is enabled/available.
     */
    public function isAvailable(): bool;
}

