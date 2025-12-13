<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GyvatukasCalculatorInterface;
use App\Enums\DistributionMethod;
use App\Enums\GyvatukasCalculationType;
use App\Models\Building;
use App\Services\BillingCalculation\CirculationCostDistributor;
use App\Services\BillingCalculation\GyvatukasCacheManager;
use App\Services\BillingCalculation\WinterAdjustmentStrategy;
use App\ValueObjects\CalculationResult;
use App\ValueObjects\ConsumptionData;
use App\ValueObjects\SummerPeriod;
use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Modern GyvatukasCalculator following SOLID principles.
 * 
 * This refactored service separates concerns:
 * - Calculation logic (this class)
 * - Caching (GyvatukasCacheManager)
 * - Distribution (CirculationCostDistributor)
 * - Winter adjustments (WinterAdjustmentStrategy)
 */
final readonly class GyvatukasCalculatorService implements GyvatukasCalculatorInterface
{
    private const MIN_CIRCULATION_ENERGY = 0.0;

    public function __construct(
        private GyvatukasCacheManager $cacheManager,
        private CirculationCostDistributor $distributor,
        private WinterAdjustmentStrategy $winterStrategy,
        private ConfigRepository $config,
        private LoggerInterface $logger,
    ) {}

    public function isHeatingSeason(Carbon $date): bool
    {
        $summerMonths = $this->getSummerMonths();
        return !in_array($date->month, $summerMonths, true);
    }

    public function isSummerPeriod(Carbon $date): bool
    {
        $summerMonths = $this->getSummerMonths();
        return in_array($date->month, $summerMonths, true);
    }

    public function calculateSummerGyvatukas(Building $building, Carbon $month): float
    {
        if ($this->isHeatingSeason($month)) {
            $this->logger->warning('Summer gyvatukas calculation requested for heating season month', [
                'building_id' => $building->id,
                'month' => $month->format('Y-m'),
            ]);
            return 0.0;
        }

        $result = $this->cacheManager->remember(
            GyvatukasCalculationType::SUMMER,
            $building,
            $month,
            fn () => $this->performSummerCalculation($building, $month)
        );

        return $result->energy;
    }

    public function calculateWinterGyvatukas(Building $building, Carbon $month): float
    {
        if (!$this->isHeatingSeason($month)) {
            $this->logger->warning('Winter gyvatukas calculation requested for summer month', [
                'building_id' => $building->id,
                'month' => $month->format('Y-m'),
            ]);
            return 0.0;
        }

        $result = $this->cacheManager->remember(
            GyvatukasCalculationType::WINTER,
            $building,
            $month,
            fn () => $this->performWinterCalculation($building, $month)
        );

        return $result->energy;
    }

    public function getSummerAverage(Building $building): float
    {
        if ($this->isSummerAverageValid($building)) {
            return (float) $building->gyvatukas_summer_average;
        }

        return $this->calculateAndStoreSummerAverage($building);
    }

    public function calculateAndStoreSummerAverage(Building $building): float
    {
        $summerPeriod = $this->getLastCompleteSummerPeriod();
        
        $totalCirculation = 0.0;
        $monthCount = 0;
        
        $currentMonth = $summerPeriod->startDate->copy();
        
        while ($currentMonth->lte($summerPeriod->endDate)) {
            if ($this->isSummerPeriod($currentMonth)) {
                $result = $this->performSummerCalculation($building, $currentMonth);
                $totalCirculation += $result->energy;
                $monthCount++;
            }
            
            $currentMonth->addMonth();
        }
        
        $average = $monthCount > 0 
            ? round($totalCirculation / $monthCount, 2) 
            : $this->getDefaultCirculationRate();
        
        $building->update([
            'gyvatukas_summer_average' => $average,
            'gyvatukas_last_calculated' => now(),
        ]);
        
        $this->logger->info('Summer average calculated and stored', [
            'building_id' => $building->id,
            'summer_average' => $average,
            'month_count' => $monthCount,
            'period' => $summerPeriod->description(),
        ]);
        
        return $average;
    }

    public function clearBuildingCache(Building $building): void
    {
        $this->cacheManager->clearBuildingCache($building);
    }

    public function clearAllCache(): void
    {
        $this->cacheManager->clearAllCache();
    }

    public function calculate(Building $building, Carbon $month): float
    {
        return $this->isSummerPeriod($month)
            ? $this->calculateSummerGyvatukas($building, $month)
            : $this->calculateWinterGyvatukas($building, $month);
    }

    public function distributeCirculationCost(
        Building $building,
        float $totalCost,
        string $method = 'equal'
    ): array {
        $distributionMethod = DistributionMethod::from($method);
        return $this->distributor->distribute($building, $totalCost, $distributionMethod);
    }

    private function performSummerCalculation(Building $building, Carbon $month): CalculationResult
    {
        $this->validateBuildingForCalculation($building);
        
        $consumptionData = ConsumptionData::fromBuilding(
            $building,
            $this->getDefaultCirculationRate()
        );
        
        $adjustedEnergy = $consumptionData->calculateAdjustedEnergy();
        $finalEnergy = max($adjustedEnergy, self::MIN_CIRCULATION_ENERGY);
        
        return CalculationResult::create(
            energy: $finalEnergy,
            calculationType: GyvatukasCalculationType::SUMMER->value,
            buildingId: $building->id,
            metadata: [
                'base_energy' => $consumptionData->calculateBaseEnergy(),
                'efficiency_factor' => $consumptionData->buildingEfficiencyFactor,
                'apartments' => $consumptionData->totalApartments,
            ]
        );
    }

    private function performWinterCalculation(Building $building, Carbon $month): CalculationResult
    {
        $this->validateBuildingForCalculation($building);
        
        $summerAverage = $this->getSummerAverage($building);
        $winterAdjustment = $this->winterStrategy->calculateAdjustment($month);
        
        $consumptionData = ConsumptionData::fromBuilding(
            $building,
            $this->getDefaultCirculationRate()
        );
        
        $adjustedEnergy = $summerAverage * $winterAdjustment * $consumptionData->buildingEfficiencyFactor;
        $finalEnergy = max($adjustedEnergy, self::MIN_CIRCULATION_ENERGY);
        
        return CalculationResult::create(
            energy: $finalEnergy,
            calculationType: GyvatukasCalculationType::WINTER->value,
            buildingId: $building->id,
            metadata: [
                'summer_average' => $summerAverage,
                'winter_adjustment' => $winterAdjustment,
                'efficiency_factor' => $consumptionData->buildingEfficiencyFactor,
                'apartments' => $consumptionData->totalApartments,
            ]
        );
    }

    private function getLastCompleteSummerPeriod(): SummerPeriod
    {
        $now = now();
        $year = ($now->month >= 5 && $now->month <= 10) 
            ? $now->year - 1 
            : $now->year - 1;
        
        return new SummerPeriod($year);
    }

    private function validateBuildingForCalculation(Building $building): void
    {
        if ($building->total_apartments <= 0) {
            throw new InvalidArgumentException(
                "Building {$building->id} has invalid apartment count: {$building->total_apartments}"
            );
        }

        $maxApartments = $this->config->get('gyvatukas.validation.max_apartments', 1000);
        if ($building->total_apartments > $maxApartments) {
            throw new InvalidArgumentException(
                "Building {$building->id} exceeds maximum apartment limit: {$building->total_apartments} > {$maxApartments}"
            );
        }
    }

    private function isSummerAverageValid(Building $building): bool
    {
        if ($building->gyvatukas_summer_average === null || $building->gyvatukas_last_calculated === null) {
            return false;
        }

        $validityPeriod = $this->config->get('gyvatukas.summer_average_validity_months', 12);
        $cutoffDate = now()->subMonths($validityPeriod);

        return $building->gyvatukas_last_calculated->isAfter($cutoffDate);
    }

    private function getSummerMonths(): array
    {
        return $this->config->get('gyvatukas.summer_months', [5, 6, 7, 8, 9]);
    }

    private function getDefaultCirculationRate(): float
    {
        return $this->config->get('gyvatukas.default_circulation_rate', 15.0);
    }
}