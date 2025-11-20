<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\Tariff;
use App\Services\TariffCalculation\TariffCalculationStrategy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TariffResolver
{
    /**
     * @var array<TariffCalculationStrategy>
     */
    private array $strategies;

    /**
     * Create a new TariffResolver instance.
     *
     * @param array<TariffCalculationStrategy> $strategies
     */
    public function __construct(array $strategies = [])
    {
        if (empty($strategies)) {
            $strategies = [
                new \App\Services\TariffCalculation\FlatRateStrategy(),
                new \App\Services\TariffCalculation\TimeOfUseStrategy(),
            ];
        }
        
        $this->strategies = $strategies;
    }

    /**
     * Resolve the active tariff for a provider on a given date.
     *
     * @param Provider $provider
     * @param Carbon $date
     * @return Tariff
     * @throws ModelNotFoundException
     */
    public function resolve(Provider $provider, Carbon $date): Tariff
    {
        return $provider->tariffs()
            ->where('active_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('active_until')
                    ->orWhere('active_until', '>=', $date);
            })
            ->orderBy('active_from', 'desc')
            ->firstOrFail();
    }

    /**
     * Calculate the cost for a given tariff and consumption.
     *
     * @param Tariff $tariff
     * @param float $consumption
     * @param Carbon|null $timestamp
     * @return float
     */
    public function calculateCost(Tariff $tariff, float $consumption, ?Carbon $timestamp = null): float
    {
        $config = $tariff->configuration;
        $timestamp = $timestamp ?? now();
        $tariffType = $config['type'] ?? '';

        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($tariffType)) {
                return $strategy->calculate($tariff, $consumption, $timestamp);
            }
        }

        return 0.0;
    }
}

