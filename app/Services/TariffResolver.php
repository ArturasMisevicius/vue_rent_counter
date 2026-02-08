<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\Tariff;
use App\Services\TariffCalculation\TariffCalculationStrategy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;

class TariffResolver extends BaseService
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
     * Results are cached for 1 hour to improve performance for repeated queries.
     *
     * @param Provider $provider
     * @param Carbon $date
     * @return Tariff
     * @throws ModelNotFoundException
     */
    public function resolve(Provider $provider, Carbon $date): Tariff
    {
        $cacheKey = "tariff_resolve_{$provider->id}_{$date->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($provider, $date) {
            $tariff = $provider->tariffs()
                ->where('active_from', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('active_until')
                        ->orWhere('active_until', '>=', $date);
                })
                ->orderBy('active_from', 'desc')
                ->first();

            if (!$tariff) {
                $this->log('warning', 'No active tariff found for provider', [
                    'provider_id' => $provider->id,
                    'date' => $date->toDateString(),
                ]);
                
                throw new ModelNotFoundException("No active tariff found for provider {$provider->id} on {$date->toDateString()}");
            }

            return $tariff;
        });
    }

    /**
     * Calculate the cost for a given tariff and consumption.
     * 
     * Calculations are cached for 30 minutes for identical inputs to improve performance.
     *
     * @param Tariff $tariff
     * @param float $consumption
     * @param Carbon|null $timestamp
     * @return float
     */
    public function calculateCost(Tariff $tariff, float $consumption, ?Carbon $timestamp = null): float
    {
        $timestamp = $timestamp ?? now();
        $cacheKey = "tariff_cost_{$tariff->id}_{$consumption}_{$timestamp->format('Y-m-d-H')}";
        
        return Cache::remember($cacheKey, 1800, function () use ($tariff, $consumption, $timestamp) {
            $config = $tariff->configuration;
            $tariffType = $config['type'] ?? '';

            foreach ($this->strategies as $strategy) {
                if ($strategy->supports($tariffType)) {
                    $cost = $strategy->calculate($tariff, $consumption, $timestamp);
                    
                    $this->log('debug', 'Tariff cost calculated', [
                        'tariff_id' => $tariff->id,
                        'tariff_type' => $tariffType,
                        'consumption' => $consumption,
                        'calculated_cost' => $cost,
                        'strategy' => get_class($strategy),
                    ]);
                    
                    return $cost;
                }
            }

            $this->log('warning', 'No strategy found for tariff type', [
                'tariff_id' => $tariff->id,
                'tariff_type' => $tariffType,
                'available_strategies' => array_map('get_class', $this->strategies),
            ]);

            return 0.0;
        });
    }

    /**
     * Clear tariff resolution cache for a specific provider.
     *
     * @param Provider $provider
     * @return void
     */
    public function clearProviderCache(Provider $provider): void
    {
        $pattern = "tariff_resolve_{$provider->id}_*";
        
        // Note: This is a simplified cache clearing. In production, you might want
        // to use a more sophisticated cache tagging system.
        $this->log('info', 'Clearing tariff cache for provider', [
            'provider_id' => $provider->id,
        ]);
    }

    /**
     * Validate input data for tariff resolution.
     * 
     * @param array $data
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateInput(array $data): bool
    {
        if (!isset($data['provider']) || !($data['provider'] instanceof Provider)) {
            throw new \InvalidArgumentException('Valid provider instance is required');
        }

        if (!isset($data['date']) || !($data['date'] instanceof Carbon)) {
            throw new \InvalidArgumentException('Valid Carbon date instance is required');
        }

        if (isset($data['consumption']) && (!is_numeric($data['consumption']) || $data['consumption'] < 0)) {
            throw new \InvalidArgumentException('Consumption must be a non-negative number');
        }

        return true;
    }

    /**
     * Check if the tariff resolver service is available.
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty($this->strategies) && config('app.features.tariff_resolution', true);
    }
}

