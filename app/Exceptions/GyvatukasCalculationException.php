<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Building;
use Exception;

/**
 * Exception thrown when gyvatukas calculation fails.
 */
final class GyvatukasCalculationException extends Exception
{
    public static function invalidBuildingData(Building $building, string $reason): self
    {
        return new self(
            "Invalid building data for gyvatukas calculation (Building ID: {$building->id}): {$reason}"
        );
    }

    public static function calculationFailed(Building $building, string $calculationType, string $reason): self
    {
        return new self(
            "Gyvatukas calculation failed for building {$building->id} ({$calculationType}): {$reason}"
        );
    }

    public static function cacheFailure(Building $building, string $operation): self
    {
        return new self(
            "Cache operation failed for building {$building->id} during {$operation}"
        );
    }
}