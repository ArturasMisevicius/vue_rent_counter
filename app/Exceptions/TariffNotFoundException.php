<?php

namespace App\Exceptions;

use Exception;

class TariffNotFoundException extends Exception
{
    /**
     * Create exception for missing active tariff.
     */
    public static function forProvider(int $providerId, string $date): self
    {
        return new self(
            "No active tariff found for provider {$providerId} on date {$date}"
        );
    }

    /**
     * Create exception for invalid tariff configuration.
     */
    public static function invalidConfiguration(string $reason): self
    {
        return new self("Invalid tariff configuration: {$reason}");
    }
}
