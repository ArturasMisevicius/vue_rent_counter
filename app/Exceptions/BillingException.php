<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Base exception for billing-related errors.
 * 
 * @package App\Exceptions
 */
class BillingException extends Exception
{
    public static function invalidSchedule(string $schedule, array $supported): self
    {
        return new self(
            "Unsupported billing schedule: {$schedule}. Supported: " . implode(', ', $supported)
        );
    }

    public static function tenantProcessingFailed(int $tenantId, string $reason): self
    {
        return new self("Tenant {$tenantId} billing failed: {$reason}");
    }

    public static function propertyProcessingFailed(int $propertyId, string $reason): self
    {
        return new self("Property {$propertyId} billing failed: {$reason}");
    }

    public static function readingCollectionFailed(string $reason): self
    {
        return new self("Reading collection failed: {$reason}");
    }

    public static function sharedServiceProcessingFailed(int $serviceId, string $reason): self
    {
        return new self("Shared service {$serviceId} processing failed: {$reason}");
    }
}