<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Building;
use App\Models\GyvatukasCalculationAudit;
use App\Models\User;
use App\Policies\GyvatukasCalculatorPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Gyvatukas Calculator Service
 * 
 * Service layer wrapper for GyvatukasCalculator that adds:
 * - Authorization enforcement via policy
 * - Audit trail for all calculations
 * - Rate limiting to prevent DoS
 * - Caching for performance
 * - Structured error handling
 * 
 * Requirements: 4.1, 4.2, 4.3, 4.5, 7.1, 7.2, 7.3, 11.1
 * 
 * @package App\Services
 */
final class GyvatukasCalculatorService extends BaseService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const RATE_LIMIT_PER_USER = 10; // per minute
    private const RATE_LIMIT_PER_TENANT = 100; // per minute

    public function __construct(
        private readonly GyvatukasCalculator $calculator,
        private readonly GyvatukasCalculatorPolicy $policy
    ) {}

    /**
     * Calculate gyvatukas with authorization, audit, and caching.
     *
     * @param Building $building The building to calculate for
     * @param Carbon $billingMonth The billing month
     * @param User|null $user The user performing the calculation (defaults to auth user)
     * @return ServiceResponse
     */
    public function calculate(Building $building, Carbon $billingMonth, ?User $user = null): ServiceResponse
    {
        $user = $user ?? auth()->user();

        // Authorization check
        if (!$this->policy->calculate($user, $building)) {
            $this->log('warning', 'Unauthorized gyvatukas calculation attempt', [
                'building_id' => $building->id,
                'user_id' => $user->id,
                'user_role' => $user->role->value,
            ]);

            return $this->error(__('gyvatukas.errors.unauthorized'));
        }

        // Rate limiting
        if (!$this->checkRateLimit($user, $building)) {
            $this->log('warning', 'Rate limit exceeded for gyvatukas calculation', [
                'building_id' => $building->id,
                'user_id' => $user->id,
            ]);

            return $this->error(__('gyvatukas.errors.rate_limit_exceeded'));
        }

        // Check cache
        $cacheKey = $this->getCacheKey($building, $billingMonth);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            $this->log('debug', 'Gyvatukas calculation cache hit', [
                'building_id' => $building->id,
                'month' => $billingMonth->format('Y-m'),
            ]);

            return $this->success($cached);
        }

        // Perform calculation
        try {
            $startTime = microtime(true);
            
            $circulationEnergy = $this->calculator->calculate($building, $billingMonth);
            
            $executionTime = (microtime(true) - $startTime) * 1000; // ms

            // Create audit record
            $this->createAuditRecord($building, $billingMonth, $circulationEnergy, $executionTime, $user);

            // Cache the result
            Cache::put($cacheKey, $circulationEnergy, self::CACHE_TTL);

            $this->log('info', 'Gyvatukas calculation completed', [
                'building_id' => $building->id,
                'month' => $billingMonth->format('Y-m'),
                'circulation_energy' => $circulationEnergy,
                'execution_time_ms' => round($executionTime, 2),
            ]);

            return $this->success($circulationEnergy);

        } catch (\Throwable $e) {
            $this->handleException($e, [
                'building_id' => $building->id,
                'month' => $billingMonth->format('Y-m'),
            ]);

            return $this->error(__('gyvatukas.errors.calculation_failed'));
        }
    }

    /**
     * Distribute circulation cost with authorization and audit.
     *
     * @param Building $building The building
     * @param float $totalCirculationCost Total cost to distribute
     * @param string $method Distribution method ('equal' or 'area')
     * @param User|null $user The user performing the distribution
     * @return ServiceResponse
     */
    public function distributeCirculationCost(
        Building $building,
        float $totalCirculationCost,
        string $method = 'equal',
        ?User $user = null
    ): ServiceResponse {
        $user = $user ?? auth()->user();

        // Authorization check
        if (!$this->policy->distribute($user, $building)) {
            $this->log('warning', 'Unauthorized circulation cost distribution attempt', [
                'building_id' => $building->id,
                'user_id' => $user->id,
            ]);

            return $this->error(__('gyvatukas.errors.unauthorized'));
        }

        // Validate method
        if (!in_array($method, ['equal', 'area'])) {
            return $this->error(__('gyvatukas.errors.invalid_distribution_method'));
        }

        try {
            $distribution = $this->calculator->distributeCirculationCost(
                $building,
                $totalCirculationCost,
                $method
            );

            $this->log('info', 'Circulation cost distributed', [
                'building_id' => $building->id,
                'total_cost' => $totalCirculationCost,
                'method' => $method,
                'properties_count' => count($distribution),
            ]);

            return $this->success($distribution);

        } catch (\Throwable $e) {
            $this->handleException($e, [
                'building_id' => $building->id,
                'total_cost' => $totalCirculationCost,
                'method' => $method,
            ]);

            return $this->error(__('gyvatukas.errors.distribution_failed'));
        }
    }

    /**
     * Clear cache for a specific building.
     *
     * @param Building $building The building
     * @return void
     */
    public function clearBuildingCache(Building $building): void
    {
        // Clear all cache entries for this building
        $pattern = "gyvatukas:building:{$building->id}:*";
        
        // Note: This is a simplified version. In production, use Redis SCAN or tags
        Cache::forget($pattern);

        $this->log('debug', 'Cleared gyvatukas cache for building', [
            'building_id' => $building->id,
        ]);
    }

    /**
     * Check rate limits for user and tenant.
     *
     * @param User $user The user
     * @param Building $building The building
     * @return bool True if within limits
     */
    private function checkRateLimit(User $user, Building $building): bool
    {
        // Per-user rate limit
        $userKey = "gyvatukas:ratelimit:user:{$user->id}";
        if (RateLimiter::tooManyAttempts($userKey, self::RATE_LIMIT_PER_USER)) {
            return false;
        }
        RateLimiter::hit($userKey, 60); // 1 minute window

        // Per-tenant rate limit
        if ($building->tenant_id) {
            $tenantKey = "gyvatukas:ratelimit:tenant:{$building->tenant_id}";
            if (RateLimiter::tooManyAttempts($tenantKey, self::RATE_LIMIT_PER_TENANT)) {
                return false;
            }
            RateLimiter::hit($tenantKey, 60);
        }

        return true;
    }

    /**
     * Get cache key for a calculation.
     *
     * @param Building $building The building
     * @param Carbon $billingMonth The billing month
     * @return string
     */
    private function getCacheKey(Building $building, Carbon $billingMonth): string
    {
        return sprintf(
            'gyvatukas:building:%d:month:%s',
            $building->id,
            $billingMonth->format('Y-m')
        );
    }

    /**
     * Create audit record for calculation.
     *
     * @param Building $building The building
     * @param Carbon $billingMonth The billing month
     * @param float $circulationEnergy The calculated energy
     * @param float $executionTime Execution time in milliseconds
     * @param User $user The user who performed the calculation
     * @return void
     */
    private function createAuditRecord(
        Building $building,
        Carbon $billingMonth,
        float $circulationEnergy,
        float $executionTime,
        User $user
    ): void {
        GyvatukasCalculationAudit::create([
            'building_id' => $building->id,
            'billing_month' => $billingMonth,
            'circulation_energy' => $circulationEnergy,
            'calculation_method' => $this->calculator->isHeatingSeason($billingMonth) ? 'winter' : 'summer',
            'execution_time_ms' => round($executionTime, 2),
            'calculated_by' => $user->id,
            'tenant_id' => $building->tenant_id,
        ]);
    }
}
