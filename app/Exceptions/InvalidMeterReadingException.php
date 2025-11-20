<?php

namespace App\Exceptions;

use Exception;

class InvalidMeterReadingException extends Exception
{
    /**
     * Create exception for monotonicity violation.
     */
    public static function monotonicity(float $value, float $previousValue): self
    {
        return new self(
            "Reading value {$value} cannot be lower than previous reading {$previousValue}"
        );
    }

    /**
     * Create exception for future date.
     */
    public static function futureDate(): self
    {
        return new self("Reading date cannot be in the future");
    }

    /**
     * Create exception for zone mismatch.
     */
    public static function zoneNotSupported(string $meterSerial): self
    {
        return new self(
            "Meter {$meterSerial} does not support zone-based readings"
        );
    }

    /**
     * Create exception for missing zone.
     */
    public static function zoneRequired(string $meterSerial): self
    {
        return new self(
            "Zone is required for meter {$meterSerial} that supports multiple zones"
        );
    }
}
