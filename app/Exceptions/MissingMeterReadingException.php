<?php

namespace App\Exceptions;

use Carbon\Carbon;

/**
 * Exception thrown when required meter readings are not available.
 */
class MissingMeterReadingException extends BillingException
{
    public function __construct(int $meterId, Carbon $date, ?string $zone = null)
    {
        $zoneText = $zone ? " (zone: {$zone})" : '';
        parent::__construct(
            "Missing meter reading for meter #{$meterId} at or before {$date->toDateString()}{$zoneText}."
        );
    }
}
