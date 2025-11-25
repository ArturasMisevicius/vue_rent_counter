<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MeterType;
use App\Models\Building;
use App\Models\GyvatukasCalculationAudit;
use App\Models\MeterReading;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Secure GyvatukasCalculator Service
 * 
 * Implements seasonal circulation fee (gyvatukas) calculations for Lithuanian
 * hot water circulation systems with comprehensive security controls.
 * 
 * ## Security Features
 * - Authorization via GyvatukasCalculatorPolicy
 * - Multi-tenancy enforcement via TenantContext
 * - Rate limiting per user/tenant
 * - Audit trail for all calculations
 * - PII-safe logging with hashed identifiers
 * - Input validation and sanitization
 * - BCMath for financial precision
 * 
 * ## Performance Features
 * - Eager loading to prevent N+1 queries
 * - Multi-level caching (calculation + consumption)
 * - Selective column loading
 * - Query count monitoring
 * 
 * Requirements: 4.1, 4.2, 4.3, 4.5, 7.1, 7.2, 7.3, 8.1, 8.2, 11.1
 * 
 * @package App\Services
 */
final class GyvatukasCalculatorSecure
{
    /**
     * Specific heat capacity of water (kWh/m³·°C) - stored as string for BCMath
     */
    private string $waterSpecificHeat;

    /**
     * Temperature difference for hot water heating (°C) - stored as string for BCMath
     */
    private string $temperatureDelta;

    /**
     * Heating season start month (October = 10)
     */
    private int $heatingSeasonStartMonth;

    /**
     * Heating season end month (April = 4)
     */
    private int $heatingSeasonEndMonth;

    /**
     * Decimal precision for monetary calculations
     */
    private const DECIMAL_PRECISION = 2;

    /**
     * Decimal precision for volume measurements
     */
    private const VOLUME_PRECISION = 3;

    /**
     * Cache for calculation results
     */
    private array $calculationCache = [];

    /**
     * Cache for meter consumption results
     */
    private array $consumptionCache = [];

    /**
     * Query count for monitoring
     */
    private int $queryCount = 0;

    /**
     * Calculation start time for performance monitoring
     */
    private float $calculationStartTime = 0;

    public function __construct()
    {
        // Validate and load configuration with acceptable ranges
        $this->waterSpecificHeat = $this->validateConfigValue(
            config('gyvatukas.water_specific_heat', 1.163),
            0.5,
            2.0,
            'water_specific_heat'
        );

        $this->temperatureDelta = $this->validateConfigValue(
            config('gyvatukas.temperature_delta', 45.0),
            20.0,
            80.0,
            'temperature_delta'
        );

        $this->heatingSeasonStartMonth = (int) config('gyvatukas.heating_season_start_month', 10);
        $this->heatingSeasonEndMonth = (int) config('gyvatukas.heating_season_end_month', 4);

        // Validate month ranges
        if ($this->heatingSeasonStartMonth < 1 || $this->heatingSeasonStartMonth > 12) {
            throw new \InvalidArgumentException('Invalid heating season start month');
        }

        if ($this->heatingSeasonEndMonth < 1 || $this->heatingSeasonEndMonth > 12) {
            throw new \InvalidArgumentException('Invalid heating season end month');
        }
    }

    /**
     * Validate configuration value is within acceptable range.
     *
     * @param  mixed  $value
     * @param  float  $min
     * @param  float  $max
     * @param  string  $name
     * @return string
     * @throws \InvalidArgumentException
     */
    private function validateConfigValue($value, float $min, float $max, string $name): string
    {
        $floatValue = (float) $value;

        if ($floatValue < $min || $floatValue > $max) {
            throw new \InvalidArgumentException(
                "Configuration value '{$name}' must be between {$min} and {$max}, got {$floatValue}"
            );
        }

        return (string) $floatValue;
    }

    /**
     * Calculate gyvatukas (circulation fee) for a building in a given billing month.
     * 
     * Routes to summer or winter calculation based on the season.
     * 
     * ## Security
     * - Checks authorization via policy
     * - Enforces rate limiting
     * - Validates input
     * - Creates audit trail
     * - Logs with PII redaction
     *
     * @param  Building  $building  The building to calculate for
     * @param  Carbon  $billingMonth  The billing period month
     * @param  User|null  $user  The user performing calculation (defaults to auth user)
     * @return float Circulation energy in kWh
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    public function calculate(Building $building, Carbon $billingMonth, ?User $user = null): float
    {
        $this->calculationStartTime = microtime(true);
        $this->queryCount = 0;

        // Get authenticated user
        $user = $user ?? Auth::user();

        if (!$user) {
            throw new \Illuminate\Auth\Access\AuthorizationException('User must be authenticated');
        }

        // Check authorization
        Gate::forUser($user)->authorize('calculate', [GyvatukasCalculatorSecure::class, $building]);

        // Rate limiting
        $this->enforceRateLimits($user, $building);

        // Validate input
        $this->validateInput($building, $billingMonth);

        // Check cache first
        $cacheKey = $this->getCacheKey($building, $billingMonth);
        if (isset($this->calculationCache[$cacheKey])) {
            return $this->calculationCache[$cacheKey];
        }

        // Enable query counting
        DB::enableQueryLog();

        try {
            // Perform calculation
            $season = $this->isHeatingSeason($billingMonth) ? 'winter' : 'summer';
            $circulationEnergy = $season === 'winter'
                ? $this->calculateWinterGyvatukas($building)
                : $this->calculateSummerGyvatukas($building, $billingMonth);

            // Get query count
            $this->queryCount = count(DB::getQueryLog());

            // Cache result
            $this->calculationCache[$cacheKey] = $circulationEnergy;

            // Create audit trail
            $this->createAuditTrail($building, $billingMonth, $season, $circulationEnergy, $user);

            // Log calculation (PII-safe)
            $this->logCalculation($building, $billingMonth, $circulationEnergy, $season);

            return $circulationEnergy;
        } finally {
            DB::disableQueryLog();
        }
    }

    /**
     * Enforce rate limits for calculations.
     *
     * @param  User  $user
     * @param  Building  $building
     * @return void
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    private function enforceRateLimits(User $user, Building $building): void
    {
        // Per-user rate limit: 10 calculations per minute
        $userKey = 'gyvatukas:user:' . $user->id;
        if (RateLimiter::tooManyAttempts($userKey, 10)) {
            $seconds = RateLimiter::availableIn($userKey);
            throw new \Illuminate\Http\Exceptions\ThrottleRequestsException(
                "Too many calculations. Please try again in {$seconds} seconds."
            );
        }
        RateLimiter::hit($userKey, 60);

        // Per-tenant rate limit: 100 calculations per minute
        if ($user->tenant_id) {
            $tenantKey = 'gyvatukas:tenant:' . $user->tenant_id;
            if (RateLimiter::tooManyAttempts($tenantKey, 100)) {
                $seconds = RateLimiter::availableIn($tenantKey);
                throw new \Illuminate\Http\Exceptions\ThrottleRequestsException(
                    "Tenant rate limit exceeded. Please try again in {$seconds} seconds."
                );
            }
            RateLimiter::hit($tenantKey, 60);
        }
    }

    /**
     * Validate input parameters.
     *
     * @param  Building  $building
     * @param  Carbon  $billingMonth
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateInput(Building $building, Carbon $billingMonth): void
    {
        // Validate building has properties
        if ($building->properties()->count() === 0) {
            throw new \InvalidArgumentException('Building must have at least one property');
        }

        // Validate billing month is not in future
        if ($billingMonth->isFuture()) {
            throw new \InvalidArgumentException('Billing month cannot be in the future');
        }

        // Validate billing month is not too old (reasonable limit)
        if ($billingMonth->isBefore(Carbon::parse('2020-01-01'))) {
            throw new \InvalidArgumentException('Billing month is too far in the past');
        }
    }

    /**
     * Get cache key for calculation.
     *
     * @param  Building  $building
     * @param  Carbon  $month
     * @return string
     */
    private function getCacheKey(Building $building, Carbon $month): string
    {
        return sprintf('calc_%d_%s', $building->id, $month->format('Y-m'));
    }

    /**
     * Create audit trail for calculation.
     *
     * @param  Building  $building
     * @param  Carbon  $billingMonth
     * @param  string  $season
     * @param  float  $circulationEnergy
     * @param  User  $user
     * @return void
     */
    private function createAuditTrail(
        Building $building,
        Carbon $billingMonth,
        string $season,
        float $circulationEnergy,
        User $user
    ): void {
        $duration = (microtime(true) - $this->calculationStartTime) * 1000;

        GyvatukasCalculationAudit::create([
            'building_id' => $building->id,
            'tenant_id' => $building->tenant_id,
            'calculated_by_user_id' => $user->id,
            'billing_month' => $billingMonth->format('Y-m-d'),
            'season' => $season,
            'circulation_energy' => $circulationEnergy,
            'calculation_metadata' => [
                'duration_ms' => round($duration, 2),
                'query_count' => $this->queryCount,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
        ]);
    }

    /**
     * Log calculation with PII redaction.
     *
     * @param  Building  $building
     * @param  Carbon  $billingMonth
     * @param  float  $circulationEnergy
     * @param  string  $season
     * @return void
     */
    private function logCalculation(
        Building $building,
        Carbon $billingMonth,
        float $circulationEnergy,
        string $season
    ): void {
        // Hash building ID for privacy
        $buildingHash = substr(hash('sha256', (string) $building->id), 0, 8);

        Log::info('Gyvatukas calculation completed', [
            'building_hash' => $buildingHash,
            'month' => $billingMonth->format('Y-m'),
            'season' => $season,
            'circulation_energy' => $circulationEnergy,
            'query_count' => $this->queryCount,
            'duration_ms' => round((microtime(true) - $this->calculationStartTime) * 1000, 2),
        ]);
    }

    /**
     * Determine if a given date falls within the heating season.
     * 
     * Heating season is October through April (months 10, 11, 12, 1, 2, 3, 4).
     * 
     * Requirement: 4.1, 4.2
     *
     * @param  Carbon  $date  The date to check
     * @return bool True if in heating season, false otherwise
     */
    public function isHeatingSeason(Carbon $date): bool
    {
        $month = $date->month;
        return $month >= $this->heatingSeasonStartMonth || $month <= $this->heatingSeasonEndMonth;
    }

    /**
     * Calculate summer gyvatukas using the formula:
     * Q_circ = Q_total - (V_water × c × ΔT)
     * 
     * Uses BCMath for financial precision.
     * 
     * Requirement: 4.1, 4.3
     *
     * @param  Building  $building  The building to calculate for
     * @param  Carbon  $month  The billing month
     * @return float Circulation energy in kWh
     */
    public function calculateSummerGyvatukas(Building $building, Carbon $month): float
    {
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();

        // Fetch total heating energy for the building (Q_total)
        $totalHeatingEnergy = $this->getBuildingHeatingEnergy($building, $periodStart, $periodEnd);

        // Fetch hot water consumption for the building (V_water)
        $hotWaterVolume = $this->getBuildingHotWaterVolume($building, $periodStart, $periodEnd);

        // Calculate energy used for heating water using BCMath: V_water × c × ΔT
        $waterHeatingEnergy = bcmul(
            bcmul((string) $hotWaterVolume, $this->waterSpecificHeat, self::VOLUME_PRECISION),
            $this->temperatureDelta,
            self::DECIMAL_PRECISION
        );

        // Calculate circulation energy: Q_circ = Q_total - (V_water × c × ΔT)
        $circulationEnergy = bcsub(
            (string) $totalHeatingEnergy,
            $waterHeatingEnergy,
            self::DECIMAL_PRECISION
        );

        // Ensure non-negative result
        if (bccomp($circulationEnergy, '0', self::DECIMAL_PRECISION) < 0) {
            $buildingHash = substr(hash('sha256', (string) $building->id), 0, 8);
            
            Log::warning('Negative circulation energy calculated', [
                'building_hash' => $buildingHash,
                'month' => $month->format('Y-m'),
                'total_heating' => $totalHeatingEnergy,
                'water_heating' => $waterHeatingEnergy,
            ]);

            return 0.0;
        }

        return (float) $circulationEnergy;
    }

    /**
     * Calculate winter gyvatukas using the stored summer average.
     * 
     * Requirement: 4.2
     *
     * @param  Building  $building  The building to calculate for
     * @return float Circulation energy in kWh (from stored average)
     */
    public function calculateWinterGyvatukas(Building $building): float
    {
        $summerAverage = $building->gyvatukas_summer_average;

        if ($summerAverage === null || $summerAverage <= 0) {
            $buildingHash = substr(hash('sha256', (string) $building->id), 0, 8);
            
            Log::warning('Missing or invalid summer average for building', [
                'building_hash' => $buildingHash,
                'summer_average' => $summerAverage,
            ]);

            return 0.0;
        }

        return (float) $summerAverage;
    }

    /**
     * Distribute circulation cost among apartments in a building.
     * 
     * Requirement: 4.5
     *
     * @param  Building  $building  The building containing the apartments
     * @param  float  $totalCirculationCost  Total circulation cost to distribute
     * @param  string  $method  Distribution method: 'equal' or 'area'
     * @param  User|null  $user  The user performing distribution
     * @return array<int, float> Array mapping property_id to allocated cost
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function distributeCirculationCost(
        Building $building,
        float $totalCirculationCost,
        string $method = 'equal',
        ?User $user = null
    ): array {
        // Get authenticated user
        $user = $user ?? Auth::user();

        if (!$user) {
            throw new \Illuminate\Auth\Access\AuthorizationException('User must be authenticated');
        }

        // Check authorization
        Gate::forUser($user)->authorize('distribute', [GyvatukasCalculatorSecure::class, $building]);

        // Validate method
        if (!in_array($method, ['equal', 'area'], true)) {
            throw new \InvalidArgumentException("Invalid distribution method: {$method}");
        }

        $properties = $building->properties;

        if ($properties->isEmpty()) {
            $buildingHash = substr(hash('sha256', (string) $building->id), 0, 8);
            
            Log::warning('No properties found for building', [
                'building_hash' => $buildingHash,
            ]);

            return [];
        }

        $distribution = [];

        if ($method === 'equal') {
            // Equal distribution using BCMath
            $costPerProperty = bcdiv(
                (string) $totalCirculationCost,
                (string) $properties->count(),
                self::DECIMAL_PRECISION
            );

            foreach ($properties as $property) {
                $distribution[$property->id] = (float) $costPerProperty;
            }
        } else {
            // Area-based distribution
            $totalArea = $properties->sum('area_sqm');

            if ($totalArea <= 0) {
                $buildingHash = substr(hash('sha256', (string) $building->id), 0, 8);
                
                Log::warning('Total area is zero or negative', [
                    'building_hash' => $buildingHash,
                    'total_area' => $totalArea,
                ]);

                // Fall back to equal distribution
                return $this->distributeCirculationCost($building, $totalCirculationCost, 'equal', $user);
            }

            foreach ($properties as $property) {
                $propertyArea = (string) $property->area_sqm;
                $proportion = bcdiv($propertyArea, (string) $totalArea, 6);
                $cost = bcmul($proportion, (string) $totalCirculationCost, self::DECIMAL_PRECISION);
                $distribution[$property->id] = (float) $cost;
            }
        }

        return $distribution;
    }

    /**
     * Get total heating energy consumption for a building in a period.
     * 
     * Uses eager loading to prevent N+1 queries.
     *
     * @param  Building  $building  The building
     * @param  Carbon  $periodStart  Start of period
     * @param  Carbon  $periodEnd  End of period
     * @return float Total heating energy in kWh
     */
    private function getBuildingHeatingEnergy(Building $building, Carbon $periodStart, Carbon $periodEnd): float
    {
        // Check consumption cache
        $cacheKey = sprintf('heating_%d_%s_%s', 
            $building->id, 
            $periodStart->format('Y-m-d'), 
            $periodEnd->format('Y-m-d')
        );
        
        if (isset($this->consumptionCache[$cacheKey])) {
            return $this->consumptionCache[$cacheKey];
        }

        $totalEnergy = '0';

        // Eager load properties with heating meters and their readings
        $building->load([
            'properties.meters' => fn($q) => $q->where('type', MeterType::HEATING)
                ->select('id', 'property_id', 'type'),
            'properties.meters.readings' => fn($q) => $q
                ->whereBetween('reading_date', [$periodStart, $periodEnd])
                ->orderBy('reading_date')
                ->select('id', 'meter_id', 'reading_date', 'value')
        ]);

        foreach ($building->properties as $property) {
            foreach ($property->meters as $meter) {
                $readings = $meter->readings;

                if ($readings->count() >= 2) {
                    $firstReading = $readings->first();
                    $lastReading = $readings->last();
                    $consumption = bcsub(
                        (string) $lastReading->value,
                        (string) $firstReading->value,
                        self::DECIMAL_PRECISION
                    );
                    
                    // Ensure non-negative
                    if (bccomp($consumption, '0', self::DECIMAL_PRECISION) > 0) {
                        $totalEnergy = bcadd($totalEnergy, $consumption, self::DECIMAL_PRECISION);
                    }
                }
            }
        }

        $result = (float) $totalEnergy;
        $this->consumptionCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Get total hot water volume consumption for a building in a period.
     * 
     * Uses eager loading to prevent N+1 queries.
     *
     * @param  Building  $building  The building
     * @param  Carbon  $periodStart  Start of period
     * @param  Carbon  $periodEnd  End of period
     * @return float Total hot water volume in m³
     */
    private function getBuildingHotWaterVolume(Building $building, Carbon $periodStart, Carbon $periodEnd): float
    {
        // Check consumption cache
        $cacheKey = sprintf('water_%d_%s_%s', 
            $building->id, 
            $periodStart->format('Y-m-d'), 
            $periodEnd->format('Y-m-d')
        );
        
        if (isset($this->consumptionCache[$cacheKey])) {
            return $this->consumptionCache[$cacheKey];
        }

        $totalVolume = '0';

        // Eager load properties with hot water meters and their readings
        $building->load([
            'properties.meters' => fn($q) => $q->where('type', MeterType::WATER_HOT)
                ->select('id', 'property_id', 'type'),
            'properties.meters.readings' => fn($q) => $q
                ->whereBetween('reading_date', [$periodStart, $periodEnd])
                ->orderBy('reading_date')
                ->select('id', 'meter_id', 'reading_date', 'value')
        ]);

        foreach ($building->properties as $property) {
            foreach ($property->meters as $meter) {
                $readings = $meter->readings;

                if ($readings->count() >= 2) {
                    $firstReading = $readings->first();
                    $lastReading = $readings->last();
                    $consumption = bcsub(
                        (string) $lastReading->value,
                        (string) $firstReading->value,
                        self::VOLUME_PRECISION
                    );
                    
                    // Ensure non-negative
                    if (bccomp($consumption, '0', self::VOLUME_PRECISION) > 0) {
                        $totalVolume = bcadd($totalVolume, $consumption, self::VOLUME_PRECISION);
                    }
                }
            }
        }

        $result = (float) $totalVolume;
        $this->consumptionCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Clear all internal caches.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->calculationCache = [];
        $this->consumptionCache = [];
    }

    /**
     * Clear cache for a specific building.
     *
     * @param  int  $buildingId  The building ID to clear cache for
     * @return void
     */
    public function clearBuildingCache(int $buildingId): void
    {
        $this->calculationCache = array_filter(
            $this->calculationCache,
            fn($key) => !str_starts_with($key, 'calc_' . $buildingId . '_'),
            ARRAY_FILTER_USE_KEY
        );

        $this->consumptionCache = array_filter(
            $this->consumptionCache,
            fn($key) => !str_contains($key, '_' . $buildingId . '_'),
            ARRAY_FILTER_USE_KEY
        );
    }
}
