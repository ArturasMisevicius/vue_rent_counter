<?php

declare(strict_types=1);

namespace App\Services\BillingCalculation;

use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Standard winter adjustment strategy based on Lithuanian regulations.
 */
final readonly class StandardWinterAdjustmentStrategy implements WinterAdjustmentStrategy
{
    public function __construct(
        private ConfigRepository $config,
    ) {}

    public function calculateAdjustment(Carbon $month): float
    {
        $peakWinterMonths = $this->config->get('gyvatukas.peak_winter_months', [12, 1, 2]);
        $shoulderMonths = $this->config->get('gyvatukas.shoulder_months', [10, 11, 3, 4]);

        return match (true) {
            in_array($month->month, $peakWinterMonths, true) => 
                $this->config->get('gyvatukas.peak_winter_adjustment', 1.3),
            in_array($month->month, $shoulderMonths, true) => 
                $this->config->get('gyvatukas.shoulder_adjustment', 1.15),
            default => 
                $this->config->get('gyvatukas.default_winter_adjustment', 1.2),
        };
    }

    public function getDescription(): string
    {
        return 'Standard Lithuanian winter adjustment factors';
    }
}