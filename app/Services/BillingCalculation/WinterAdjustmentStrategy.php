<?php

declare(strict_types=1);

namespace App\Services\BillingCalculation;

use Carbon\Carbon;

/**
 * Strategy interface for winter adjustment calculations.
 */
interface WinterAdjustmentStrategy
{
    public function calculateAdjustment(Carbon $month): float;
    public function getDescription(): string;
}