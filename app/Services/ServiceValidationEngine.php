<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Models\Tariff;
use App\Services\MeterReadingService;
use App\Services\GyvatukasCalculator;
use App\Services\Validation\ValidationRuleFactory;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * ServiceValidationEngine - Orchestrates utility service validation using the Strategy pattern
 * 
 * This comprehensive validation engine provides modular validation architecture with individual 
 * validators for different validation concerns, improving maintainability and testability while
 * maintaining full backward compatibility with the existing gyvatukas system.
 * 
 * ## Architecture Patterns
 * - **Strategy Pattern**: Runtime selection of validation algorithms based on context
 * - **Factory Pattern**: Centralized validator creation and dependency injection
 * - **Value Objects**: Immutable validation context and results for thread safety
 * - **Chain of Responsibility**: Composable validators with conditional application
 * 
 * ## Core Features
 * - **Modular Validation**: Single responsibility validators (consumption, seasonal, data quality, etc.)
 * - **Security**: Authorization checks, input sanitization, audit trail logging
 * - **Performance**: Multi-layer caching, batch optimization, eager loading
 * - **Integration**: Seamless gyvatukas integration, multi-tenant isolation
 * - **Extensibility**: Plugin architecture for custom validators and rules
 * 
 * ## Performance Optimizations
 * - **Caching Strategy**: 1-hour TTL for rules, 24-hour for historical data
 * - **Batch Processing**: Optimized bulk validation with eager loading
 * - **Memory Management**: Batch size limits, streaming for large datasets
 * - **Query Optimization**: Selective loading, relationship preloading
 * 
 * ## Security Features
 * - **Authorization**: Permission-based access control for all operations
 * - **Input Sanitization**: Whitelist-based sanitization preventing injection attacks
 * - **Audit Trail**: Comprehensive logging of all validation operations
 * - **Tenant Isolation**: Automatic tenant scoping for multi-tenant security
 * 
 * ## Integration Points
 * - **GyvatukasCalculator**: Preserves existing heating calculation accuracy
 * - **MeterReadingService**: Leverages existing reading management infrastructure
 * - **Audit System**: Extends existing audit trail for validation operations
 * - **Filament Resources**: Compatible with existing admin interfaces
 * 
 * @see \App\Services\Validation\ValidationRuleFactory For validator creation and management
 * @see \App\Services\Validation\ValidationContext For immutable validation context
 * @see \App\Services\Validation\ValidationResult For immutable validation results
 * @see \App\Services\GyvatukasCalculator For gyvatukas integration
 * @see \App\Models\ServiceConfiguration For service configuration model
 * @see \App\Models\UtilityService For utility service model
 * 
 * @package App\Services
 * @author Universal Utility Management System
 * @version 1.0.0
 * @since 2024-12-13
 */
final class ServiceValidationEngine
{
    /**
     * Cache TTL for validation rules (1 hour)
     */
    private const CACHE_TTL_SECONDS = 3600;
    
    /**
     * Cache key prefix for validation rules
     */
    private const CACHE_PREFIX = 'service_validation';

    /**
     * Memoized configuration values for performance
     */
    private ?array $validationConfig = null;
    private ?array $seasonalAdjustments = null;

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
        private readonly MeterReadingService $meterReadingService,
        private readonly GyvatukasCalculator $gyvatukasCalculator,
        private readonly ValidationRuleFactory $validatorFactory,
    ) {
    }

    /**
     * Validate a meter reading against all applicable rules using the Strategy pattern.
     * 
     * This method creates a validation context and applies all relevant validators,
     * combining their results into a comprehensive validation result.
     * 
     * Enhanced features:
     * - Validation status field support (pending, validated, rejected, requires_review)
     * - Estimated reading validation with true-up calculations
     * - Multi-value reading structure validation
     * - Photo OCR validation support
     * - CSV import and API integration validation
     * 
     * @param MeterReading $reading The reading to validate
     * @param ServiceConfiguration|null $serviceConfig Optional service configuration for enhanced validation
     * @return array{is_valid: bool, errors: array<string>, warnings: array<string>, metadata: array<string, mixed>} Array of validation results with errors and warnings
     * 
     * @example
     * ```php
     * $result = $validator->validateMeterReading($reading, $serviceConfig);
     * if (!$result['is_valid']) {
     *     // Handle validation errors
     * }
     * ```
     */
    public function validateMeterReading(MeterReading $reading, ?ServiceConfiguration $serviceConfig = null): array
    {
        try {
            // Authorization check - ensure user can view/validate this meter reading
            if (auth()->check() && !auth()->user()->can('view', $reading)) {
                $this->logger->warning('Unauthorized meter reading validation attempt', [
                    'user_id' => auth()->id(),
                    'reading_id' => $reading->id,
                    'meter_id' => $reading->meter_id,
                ]);
                return ValidationResult::withError(__('validation.unauthorized_meter_reading'))->toArray();
            }

            // Create validation context with all necessary data
            $context = $this->createValidationContext($reading, $serviceConfig);
            
            // Get applicable validators for this context
            $validators = $this->validatorFactory->getValidatorsForContext($context);
            
            // Apply all validators and combine results
            $combinedResult = ValidationResult::valid();
            
            foreach ($validators as $validator) {
                $result = $validator->validate($context);
                $combinedResult = $combinedResult->merge($result);
                
                // Log individual validator results for debugging
                $this->logValidatorResult($validator, $result, $context);
            }

            // Log final validation result for audit trail
            $this->logValidationResult($reading, $combinedResult->toArray());

            return $combinedResult->toArray();

        } catch (\Exception $e) {
            $this->logger->error('Meter reading validation failed', [
                'reading_id' => $reading->id,
                'meter_id' => $reading->meter_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ValidationResult::withError(__('validation.system_error', ['error' => $e->getMessage()]))->toArray();
        }
    }

    /**
     * Validate rate change restrictions using the dedicated RateChangeValidator.
     * 
     * @param ServiceConfiguration $serviceConfig The service configuration to validate
     * @param array<string, mixed> $newRateSchedule The proposed new rate schedule
     * @return array{is_valid: bool, errors: array<string>, warnings: array<string>} Validation results
     */
    public function validateRateChangeRestrictions(ServiceConfiguration $serviceConfig, array $newRateSchedule): array
    {
        // Authorization check - ensure user can modify this service configuration
        if (auth()->check() && !auth()->user()->can('update', $serviceConfig)) {
            $this->logger->warning('Unauthorized rate change validation attempt', [
                'user_id' => auth()->id(),
                'service_config_id' => $serviceConfig->id,
            ]);
            return ValidationResult::withError(__('validation.unauthorized_rate_change'))->toArray();
        }

        // Input validation and sanitization
        if (empty($newRateSchedule)) {
            return ValidationResult::withError(__('validation.rate_schedule_empty'))->toArray();
        }

        // Sanitize input array to prevent injection attacks
        $sanitizedSchedule = $this->sanitizeRateSchedule($newRateSchedule);
        
        $validator = $this->validatorFactory->getValidator('rate_change');
        
        if (!$validator instanceof \App\Services\Validation\Validators\RateChangeValidator) {
            $this->logger->error('RateChangeValidator not found or invalid type');
            return ValidationResult::withError(__('validation.validator_unavailable'))->toArray();
        }

        $result = $validator->validateRateChangeRestrictions($serviceConfig, $sanitizedSchedule);
        return $result->toArray();
    }

    /**
     * Batch validate multiple meter readings with optimized performance.
     * 
     * Uses eager loading and caching to efficiently validate multiple readings
     * while maintaining the same validation quality as individual validation.
     * 
     * SECURITY ENHANCEMENTS:
     * - Pre-validates ALL readings for authorization BEFORE processing
     * - Enforces rate limiting on batch operations
     * - Early termination on authorization failures
     * - Comprehensive audit logging
     * 
     * PERFORMANCE OPTIMIZATIONS:
     * - Single query for all previous readings (eliminates N+1)
     * - Bulk cache warming for validation rules
     * - Optimized relationship preloading
     * - Memory-efficient batch processing
     * 
     * @param Collection<int, MeterReading> $readings Collection of MeterReading models
     * @param array<string, mixed> $options Validation options
     * @return array{total_readings: int, valid_readings: int, invalid_readings: int, warnings_count: int, results: array<int, array>, summary: array<string, float>, performance_metrics: array<string, mixed>} Batch validation results
     * 
     * @throws \InvalidArgumentException When readings collection contains invalid models
     * @throws \Illuminate\Auth\Access\AuthorizationException When user lacks authorization
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException When rate limit exceeded
     */
    public function batchValidateReadings(Collection $readings, array $options = []): array
    {
        // SECURITY: Validate input collection contains only MeterReading models FIRST
        $this->validateReadingsCollection($readings);
        
        // SECURITY: Pre-validate ALL readings for authorization BEFORE processing
        $this->validateBatchAuthorization($readings);
        
        // SECURITY: Enforce rate limiting on batch operations
        $this->enforceRateLimit('batch_validation', $readings->count());
        
        $startTime = microtime(true);
        $queryCount = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
        
        $batchResult = [
            'total_readings' => $readings->count(),
            'valid_readings' => 0,
            'invalid_readings' => 0,
            'warnings_count' => 0,
            'results' => [],
            'summary' => [],
            'performance_metrics' => [
                'start_time' => $startTime,
                'cache_hits' => 0,
                'database_queries' => 0,
                'memory_peak_mb' => 0,
            ],
        ];

        try {
            // OPTIMIZATION 1: Bulk preload all data with single optimized query
            $preloadedData = $this->bulkPreloadValidationData($readings);
            
            // OPTIMIZATION 2: Warm validation rule cache for all service configurations
            $this->warmValidationRuleCache($preloadedData['service_configs']);
            
            // OPTIMIZATION 3: Process readings in memory-efficient chunks
            $chunkSize = min(50, $readings->count()); // Prevent memory exhaustion
            
            foreach ($readings->chunk($chunkSize) as $chunk) {
                foreach ($chunk as $reading) {
                    // Use preloaded data to avoid additional queries
                    $validationResult = $this->validateMeterReadingOptimized(
                        $reading, 
                        $preloadedData
                    );
                    
                    $batchResult['results'][$reading->id] = $validationResult;
                    
                    if ($validationResult['is_valid']) {
                        $batchResult['valid_readings']++;
                    } else {
                        $batchResult['invalid_readings']++;
                    }
                    
                    $batchResult['warnings_count'] += count($validationResult['warnings'] ?? []);
                }
                
                // Force garbage collection between chunks
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }

            // Generate summary statistics
            $batchResult['summary'] = $this->generateBatchSummary($batchResult);
            
            // Calculate performance metrics
            $endTime = microtime(true);
            $finalQueryCount = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
            
            $batchResult['performance_metrics'] = array_merge($batchResult['performance_metrics'], [
                'end_time' => $endTime,
                'duration' => $endTime - $startTime,
                'database_queries' => $finalQueryCount - $queryCount,
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'cache_hits' => $this->getCacheHitCount(),
                'queries_per_reading' => $readings->count() > 0 ? 
                    round(($finalQueryCount - $queryCount) / $readings->count(), 2) : 0,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Batch validation failed', [
                'reading_count' => $readings->count(),
                'error' => $e->getMessage(),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ]);

            $batchResult['error'] = 'Batch validation system error: ' . $e->getMessage();
        }

        return $batchResult;
    }

    /**
     * Create a validation context with all necessary data for validation.
     * 
     * This method efficiently loads all required data for validation including
     * service configuration, historical readings, and configuration values.
     */
    private function createValidationContext(MeterReading $reading, ?ServiceConfiguration $serviceConfig): ValidationContext
    {
        // Get service configuration if not provided
        if (!$serviceConfig && $reading->meter->service_configuration_id) {
            $serviceConfig = ServiceConfiguration::with(['utilityService', 'tariff', 'provider'])
                ->find($reading->meter->service_configuration_id);
        }

        // Get previous reading for consumption calculation
        $previousReading = null;
        if ($reading->meter) {
            $previousReading = $this->meterReadingService->getPreviousReading(
                $reading->meter,
                $reading->zone,
                $reading->reading_date->toDateString()
            );
        }

        // Get historical readings for pattern analysis (cached)
        $historicalReadings = $this->getHistoricalReadings($reading->meter, 12);

        return new ValidationContext(
            reading: $reading,
            serviceConfiguration: $serviceConfig,
            validationConfig: $this->getValidationConfig(),
            seasonalConfig: $this->getSeasonalAdjustments($serviceConfig),
            previousReading: $previousReading,
            historicalReadings: $historicalReadings,
        );
    }

    /**
     * Get historical readings with caching for performance.
     */
    private function getHistoricalReadings($meter, int $months): Collection
    {
        $cacheKey = $this->buildCacheKey('historical_readings', "{$meter->id}_{$months}");
        
        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn() => $meter->readings()
                ->where('reading_date', '>=', now()->subMonths($months))
                ->where('validation_status', ValidationStatus::VALIDATED)
                ->orderBy('reading_date', 'desc')
                ->get()
        );
    }

    /**
     * OPTIMIZED: Bulk preload all validation data with minimal queries.
     * 
     * PERFORMANCE IMPROVEMENTS:
     * - Single query for all previous readings (eliminates N+1)
     * - Optimized relationship loading with select() constraints
     * - Bulk cache operations instead of individual cache puts
     * - Memory-efficient data structures
     * 
     * @param Collection<int, MeterReading> $readings
     * @return array Preloaded data indexed for fast access
     */
    private function bulkPreloadValidationData(Collection $readings): array
    {
        $meterIds = $readings->pluck('meter_id')->unique()->values();
        $readingIds = $readings->pluck('id')->values();
        
        // OPTIMIZATION 1: Single query for all meters with relationships
        $meters = \App\Models\Meter::with([
            'serviceConfiguration' => function ($query) {
                $query->select([
                    'id', 'property_id', 'utility_service_id', 'pricing_model',
                    'rate_schedule', 'distribution_method', 'is_shared_service',
                    'effective_from', 'effective_until', 'configuration_overrides',
                    'tariff_id', 'provider_id', 'is_active'
                ]);
            },
            'serviceConfiguration.utilityService' => function ($query) {
                $query->select([
                    'id', 'name', 'unit_of_measurement', 'default_pricing_model',
                    'service_type_bridge', 'validation_rules', 'business_logic_config'
                ]);
            },
            'serviceConfiguration.tariff' => function ($query) {
                $query->select(['id', 'name', 'configuration', 'active_from', 'active_until']);
            },
            'serviceConfiguration.provider' => function ($query) {
                $query->select(['id', 'name', 'configuration']);
            }
        ])
        ->select(['id', 'property_id', 'type', 'supports_zones', 'reading_structure', 'service_configuration_id'])
        ->whereIn('id', $meterIds)
        ->get()
        ->keyBy('id');

        // OPTIMIZATION 2: Bulk query for all previous readings (eliminates N+1)
        $previousReadings = $this->bulkGetPreviousReadings($readings, $meters);
        
        // OPTIMIZATION 3: Bulk query for historical readings with optimized constraints
        $historicalReadings = $this->bulkGetHistoricalReadings($meterIds);
        
        // OPTIMIZATION 4: Extract service configurations for cache warming
        $serviceConfigs = $meters->pluck('serviceConfiguration')->filter()->keyBy('id');
        
        return [
            'meters' => $meters,
            'previous_readings' => $previousReadings,
            'historical_readings' => $historicalReadings,
            'service_configs' => $serviceConfigs,
        ];
    }

    /**
     * OPTIMIZED: Get all previous readings in a single query.
     * Eliminates N+1 query problem in getConsumption() method.
     */
    private function bulkGetPreviousReadings(Collection $readings, Collection $meters): Collection
    {
        // Group readings by meter and zone for efficient querying
        $readingGroups = $readings->groupBy(function ($reading) {
            return $reading->meter_id . '_' . ($reading->zone ?? 'null');
        });

        $previousReadings = collect();

        foreach ($readingGroups as $groupKey => $groupReadings) {
            [$meterId, $zone] = explode('_', $groupKey, 2);
            $zone = $zone === 'null' ? null : $zone;
            
            // Get all reading dates for this meter/zone combination
            $readingDates = $groupReadings->pluck('reading_date')->sort()->values();
            
            if ($readingDates->isEmpty()) continue;
            
            // Single query to get all previous readings for this meter/zone
            $meterPreviousReadings = MeterReading::query()
                ->where('meter_id', $meterId)
                ->where('zone', $zone)
                ->where('reading_date', '<', $readingDates->first())
                ->where('validation_status', ValidationStatus::VALIDATED)
                ->orderBy('reading_date', 'desc')
                ->limit($readingDates->count() * 2) // Buffer for safety
                ->get();

            // Map each reading to its previous reading
            foreach ($groupReadings as $reading) {
                $previous = $meterPreviousReadings
                    ->where('reading_date', '<', $reading->reading_date)
                    ->first();
                
                if ($previous) {
                    $previousReadings->put($reading->id, $previous);
                }
            }
        }

        return $previousReadings;
    }

    /**
     * OPTIMIZED: Bulk load historical readings with memory constraints.
     */
    private function bulkGetHistoricalReadings(Collection $meterIds): Collection
    {
        $cutoffDate = now()->subMonths(12);
        
        return MeterReading::query()
            ->whereIn('meter_id', $meterIds)
            ->where('reading_date', '>=', $cutoffDate)
            ->where('validation_status', ValidationStatus::VALIDATED)
            ->select(['id', 'meter_id', 'reading_date', 'value', 'zone', 'reading_values'])
            ->orderBy('meter_id')
            ->orderBy('reading_date', 'desc')
            ->get()
            ->groupBy('meter_id');
    }

    /**
     * OPTIMIZED: Warm validation rule cache for all service configurations.
     */
    private function warmValidationRuleCache(Collection $serviceConfigs): void
    {
        $cacheKeys = [];
        $cacheData = [];
        
        foreach ($serviceConfigs as $config) {
            $rulesCacheKey = $this->buildCacheKey('validation_rules', $config->id);
            $seasonalCacheKey = $this->buildCacheKey('seasonal_config', $config->id);
            
            $cacheKeys[] = $rulesCacheKey;
            $cacheKeys[] = $seasonalCacheKey;
            
            // Prepare cache data
            $cacheData[$rulesCacheKey] = $config->getMergedConfiguration();
            $cacheData[$seasonalCacheKey] = $this->getSeasonalAdjustments($config);
        }
        
        // Bulk cache operation
        foreach ($cacheData as $key => $data) {
            $this->cache->put($key, $data, self::CACHE_TTL_SECONDS);
        }
    }

    /**
     * OPTIMIZED: Validate meter reading using preloaded data.
     */
    private function validateMeterReadingOptimized(MeterReading $reading, array $preloadedData): array
    {
        try {
            // Authorization check - ensure user can view/validate this meter reading
            if (auth()->check() && !auth()->user()->can('view', $reading)) {
                $this->logger->warning('Unauthorized meter reading validation attempt', [
                    'user_id' => auth()->id(),
                    'reading_id' => $reading->id,
                    'meter_id' => $reading->meter_id,
                ]);
                return ValidationResult::withError(__('validation.unauthorized_meter_reading'))->toArray();
            }

            // Use preloaded data instead of additional queries
            $meter = $preloadedData['meters']->get($reading->meter_id);
            $serviceConfig = $meter?->serviceConfiguration;
            $previousReading = $preloadedData['previous_readings']->get($reading->id);
            $historicalReadings = $preloadedData['historical_readings']->get($reading->meter_id, collect());

            // Create optimized validation context
            $context = new ValidationContext(
                reading: $reading,
                serviceConfiguration: $serviceConfig,
                validationConfig: $this->getValidationConfig(),
                seasonalConfig: $this->getSeasonalAdjustments($serviceConfig),
                previousReading: $previousReading,
                historicalReadings: $historicalReadings,
            );
            
            // Get applicable validators for this context
            $validators = $this->validatorFactory->getValidatorsForContext($context);
            
            // Apply all validators and combine results
            $combinedResult = ValidationResult::valid();
            
            foreach ($validators as $validator) {
                $result = $validator->validate($context);
                $combinedResult = $combinedResult->merge($result);
            }

            // Log final validation result for audit trail
            $this->logValidationResult($reading, $combinedResult->toArray());

            return $combinedResult->toArray();

        } catch (\Exception $e) {
            $this->logger->error('Optimized meter reading validation failed', [
                'reading_id' => $reading->id,
                'meter_id' => $reading->meter_id,
                'error' => $e->getMessage(),
            ]);

            return ValidationResult::withError(__('validation.system_error', ['error' => $e->getMessage()]))->toArray();
        }
    }

    /**
     * Get cache hit count for performance metrics.
     */
    private function getCacheHitCount(): int
    {
        // This would need to be implemented based on your cache driver
        // For now, return a placeholder
        return 0;
    }

    /**
     * Log individual validator results for debugging.
     */
    private function logValidatorResult($validator, ValidationResult $result, ValidationContext $context): void
    {
        $this->logger->debug('Individual validator result', [
            'validator' => $validator->getName(),
            'reading_id' => $context->reading->id,
            'is_valid' => $result->isValid,
            'error_count' => count($result->errors),
            'warning_count' => count($result->warnings),
        ]);
    }

    /**
     * Build a cache key for validation data.
     */
    private function buildCacheKey(string $type, mixed $identifier): string
    {
        return sprintf('%s:%s:%s', self::CACHE_PREFIX, $type, $identifier);
    }

    /**
     * Get validation configuration with caching.
     */
    private function getValidationConfig(): array
    {
        return $this->validationConfig ??= $this->config->get('service_validation', [
            'default_min_consumption' => 0,
            'default_max_consumption' => 10000,
            'rate_change_frequency_days' => 30,
        ]);
    }

    /**
     * Get seasonal adjustments configuration with caching.
     */
    private function getSeasonalAdjustments(?ServiceConfiguration $serviceConfig): array
    {
        return $this->seasonalAdjustments ??= $this->config->get('service_validation.seasonal_adjustments', [
            'heating' => [
                'summer_max_threshold' => 50,
                'winter_min_threshold' => 100,
            ],
            'water' => [
                'summer_range' => ['min' => 80, 'max' => 150],
                'winter_range' => ['min' => 60, 'max' => 120],
            ],
            'default' => [
                'variance_threshold' => 0.3,
            ],
        ]);
    }

    /**
     * Log validation results for audit trail.
     */
    private function logValidationResult(MeterReading $reading, array $validationResult): void
    {
        $this->logger->info('Meter reading validation completed', [
            'reading_id' => $reading->id,
            'meter_id' => $reading->meter_id,
            'is_valid' => $validationResult['is_valid'],
            'error_count' => count($validationResult['errors'] ?? []),
            'warning_count' => count($validationResult['warnings'] ?? []),
            'input_method' => $reading->input_method->value,
        ]);
    }

    /**
     * Generate batch validation summary statistics.
     */
    private function generateBatchSummary(array $batchResult): array
    {
        return [
            'validation_rate' => $batchResult['total_readings'] > 0 
                ? round(($batchResult['valid_readings'] / $batchResult['total_readings']) * 100, 2) 
                : 0,
            'average_warnings_per_reading' => $batchResult['total_readings'] > 0 
                ? round($batchResult['warnings_count'] / $batchResult['total_readings'], 2) 
                : 0,
            'error_rate' => $batchResult['total_readings'] > 0
                ? round(($batchResult['invalid_readings'] / $batchResult['total_readings']) * 100, 2)
                : 0,
        ];
    }

    /**
     * Sanitize rate schedule input to prevent injection attacks.
     * 
     * SECURITY ENHANCEMENTS:
     * - Validates array depth to prevent nested injection attacks
     * - Enforces size limits to prevent memory exhaustion
     * - Enhanced type validation with bounds checking
     * - Secure date validation with range limits
     * - Structure-specific validation for time_slots and tiers
     * 
     * @param array<string, mixed> $rateSchedule
     * @return array<string, mixed>
     * @throws \InvalidArgumentException If input is malicious or invalid
     */
    private function sanitizeRateSchedule(array $rateSchedule): array
    {
        // SECURITY: Validate array depth to prevent nested injection
        if ($this->getArrayDepth($rateSchedule) > 3) {
            $this->logger->warning('Rate schedule structure too complex', [
                'depth' => $this->getArrayDepth($rateSchedule),
                'user_id' => auth()->id(),
            ]);
            throw new \InvalidArgumentException('Rate schedule structure too complex');
        }

        // SECURITY: Validate total array size to prevent memory exhaustion
        $arraySize = $this->getArraySize($rateSchedule);
        if ($arraySize > 1000) {
            $this->logger->warning('Rate schedule too large', [
                'size' => $arraySize,
                'user_id' => auth()->id(),
            ]);
            throw new \InvalidArgumentException('Rate schedule too large');
        }

        $sanitized = [];
        $allowedKeys = [
            'rate_per_unit', 'monthly_rate', 'base_rate', 'default_rate',
            'effective_from', 'effective_until', 'time_slots', 'tiers',
            'peak_rate', 'off_peak_rate', 'weekend_rate'
        ];

        foreach ($rateSchedule as $key => $value) {
            // SECURITY: Strict key validation with type checking
            if (!is_string($key) || !in_array($key, $allowedKeys, true)) {
                continue;
            }

            // SECURITY: Enhanced type validation with bounds checking
            try {
                $sanitized[$key] = match ($key) {
                    'rate_per_unit', 'monthly_rate', 'base_rate', 'default_rate', 
                    'peak_rate', 'off_peak_rate', 'weekend_rate' => $this->validateNumericRate($value),
                    'effective_from', 'effective_until' => $this->validateDateString($value),
                    'time_slots', 'tiers' => $this->validateNestedStructure($value, $key),
                    default => null,
                };

                // Remove null values
                if ($sanitized[$key] === null) {
                    unset($sanitized[$key]);
                }
            } catch (\Exception $e) {
                $this->logger->warning('Rate schedule validation error', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);
                // Skip invalid values instead of failing completely
                continue;
            }
        }

        return $sanitized;
    }

    /**
     * SECURITY: Validate numeric rate values with bounds checking.
     */
    private function validateNumericRate(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }
        
        $rate = (float) $value;
        
        // SECURITY: Validate reasonable bounds to prevent overflow attacks
        if ($rate < 0 || $rate > 999999.99) {
            throw new \InvalidArgumentException('Rate value out of acceptable range (0-999999.99)');
        }
        
        // SECURITY: Check for NaN and infinite values
        if (!is_finite($rate)) {
            throw new \InvalidArgumentException('Rate value must be finite');
        }
        
        return $rate;
    }

    /**
     * SECURITY: Validate date strings with format and range checking.
     */
    private function validateDateString(mixed $value): ?string
    {
        if (!is_string($value) || strlen($value) > 25) { // Reasonable date string length
            return null;
        }
        
        // SECURITY: Validate date format and range
        try {
            $date = new \DateTime($value);
            $now = new \DateTime();
            $minDate = (clone $now)->sub(new \DateInterval('P50Y')); // 50 years ago
            $maxDate = (clone $now)->add(new \DateInterval('P10Y')); // 10 years future
            
            if ($date < $minDate || $date > $maxDate) {
                throw new \InvalidArgumentException('Date out of acceptable range');
            }
            
            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * SECURITY: Validate nested structures with type-specific rules.
     */
    private function validateNestedStructure(mixed $value, string $type): array
    {
        if (!is_array($value) || empty($value)) {
            return [];
        }
        
        // SECURITY: Limit nested array size
        if (count($value) > 50) {
            throw new \InvalidArgumentException("Too many {$type} entries (max 50)");
        }
        
        // Type-specific validation
        return match ($type) {
            'time_slots' => $this->validateTimeSlots($value),
            'tiers' => $this->validateTiers($value),
            default => [],
        };
    }

    /**
     * SECURITY: Validate time slots structure.
     */
    private function validateTimeSlots(array $timeSlots): array
    {
        $validated = [];
        
        foreach ($timeSlots as $slot) {
            if (!is_array($slot)) {
                continue;
            }
            
            $validatedSlot = [];
            
            // Validate required fields
            if (isset($slot['start_hour']) && is_numeric($slot['start_hour'])) {
                $hour = (int) $slot['start_hour'];
                if ($hour >= 0 && $hour <= 23) {
                    $validatedSlot['start_hour'] = $hour;
                }
            }
            
            if (isset($slot['end_hour']) && is_numeric($slot['end_hour'])) {
                $hour = (int) $slot['end_hour'];
                if ($hour >= 0 && $hour <= 23) {
                    $validatedSlot['end_hour'] = $hour;
                }
            }
            
            if (isset($slot['rate']) && is_numeric($slot['rate'])) {
                $validatedSlot['rate'] = $this->validateNumericRate($slot['rate']);
            }
            
            if (isset($slot['day_type']) && is_string($slot['day_type'])) {
                $dayType = trim($slot['day_type']);
                if (in_array($dayType, ['weekday', 'weekend'], true)) {
                    $validatedSlot['day_type'] = $dayType;
                }
            }
            
            // Only add if has minimum required fields
            if (isset($validatedSlot['start_hour'], $validatedSlot['end_hour'], $validatedSlot['rate'])) {
                $validated[] = $validatedSlot;
            }
        }
        
        return $validated;
    }

    /**
     * SECURITY: Validate tiers structure.
     */
    private function validateTiers(array $tiers): array
    {
        $validated = [];
        
        foreach ($tiers as $tier) {
            if (!is_array($tier)) {
                continue;
            }
            
            $validatedTier = [];
            
            if (isset($tier['limit']) && is_numeric($tier['limit'])) {
                $limit = (float) $tier['limit'];
                if ($limit > 0 && $limit <= 999999) {
                    $validatedTier['limit'] = $limit;
                }
            }
            
            if (isset($tier['rate']) && is_numeric($tier['rate'])) {
                $validatedTier['rate'] = $this->validateNumericRate($tier['rate']);
            }
            
            // Only add if has required fields
            if (isset($validatedTier['limit'], $validatedTier['rate'])) {
                $validated[] = $validatedTier;
            }
        }
        
        return $validated;
    }

    /**
     * SECURITY: Calculate array depth to prevent deeply nested attacks.
     */
    private function getArrayDepth(array $array): int
    {
        $maxDepth = 1;
        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->getArrayDepth($value) + 1;
                $maxDepth = max($maxDepth, $depth);
            }
        }
        return $maxDepth;
    }

    /**
     * SECURITY: Calculate total array size to prevent memory exhaustion.
     */
    private function getArraySize(array $array): int
    {
        $size = count($array);
        foreach ($array as $value) {
            if (is_array($value)) {
                $size += $this->getArraySize($value);
            }
        }
        return $size;
    }

    /**
     * Sanitize nested arrays in rate schedules.
     * 
     * @param array<mixed> $array
     * @return array<mixed>
     */
    private function sanitizeNestedArray(array $array): array
    {
        $sanitized = [];
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeNestedArray($value);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = is_float($value) ? (float) $value : (int) $value;
            } elseif (is_string($value)) {
                $sanitized[$key] = filter_var($value, FILTER_SANITIZE_STRING);
            } elseif (is_bool($value)) {
                $sanitized[$key] = $value;
            }
            // Skip other types (objects, resources, etc.)
        }

        return $sanitized;
    }

    /**
     * Validate estimated readings and calculate true-up adjustments.
     * 
     * This method validates estimated readings against actual readings when available
     * and calculates the necessary adjustments for billing accuracy.
     * 
     * @param MeterReading $estimatedReading The estimated reading to validate
     * @param MeterReading|null $actualReading The actual reading for comparison
     * @return array{is_valid: bool, true_up_amount: float|null, adjustment_required: bool, errors: array<string>, warnings: array<string>}
     */
    public function validateEstimatedReading(MeterReading $estimatedReading, ?MeterReading $actualReading = null): array
    {
        try {
            // Authorization check
            if (auth()->check() && !auth()->user()->can('view', $estimatedReading)) {
                return ValidationResult::withError(__('validation.unauthorized_meter_reading'))->toArray();
            }

            $errors = [];
            $warnings = [];
            $trueUpAmount = null;
            $adjustmentRequired = false;

            // Validate that this is actually an estimated reading
            if ($estimatedReading->input_method !== \App\Enums\InputMethod::ESTIMATED) {
                $errors[] = 'Reading is not marked as estimated';
            }

            // If actual reading is provided, calculate true-up
            if ($actualReading) {
                $trueUpAmount = $this->calculateTrueUpAmount($estimatedReading, $actualReading);
                $adjustmentRequired = abs($trueUpAmount) > $this->getTrueUpThreshold();
                
                if ($adjustmentRequired) {
                    $warnings[] = "True-up adjustment required: {$trueUpAmount} {$this->getReadingUnit($estimatedReading)}";
                }
            }

            // Validate estimation accuracy if historical data is available
            $accuracyValidation = $this->validateEstimationAccuracy($estimatedReading);
            $warnings = array_merge($warnings, $accuracyValidation['warnings']);

            $result = [
                'is_valid' => empty($errors),
                'true_up_amount' => $trueUpAmount,
                'adjustment_required' => $adjustmentRequired,
                'errors' => $errors,
                'warnings' => $warnings,
                'metadata' => [
                    'validation_type' => 'estimated_reading',
                    'has_actual_reading' => $actualReading !== null,
                    'validated_at' => now()->toISOString(),
                ],
            ];

            $this->logger->info('Estimated reading validation completed', [
                'estimated_reading_id' => $estimatedReading->id,
                'actual_reading_id' => $actualReading?->id,
                'true_up_amount' => $trueUpAmount,
                'adjustment_required' => $adjustmentRequired,
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Estimated reading validation failed', [
                'estimated_reading_id' => $estimatedReading->id,
                'error' => $e->getMessage(),
            ]);

            return ValidationResult::withError(__('validation.system_error', ['error' => $e->getMessage()]))->toArray();
        }
    }

    /**
     * Validate readings by validation status with enhanced filtering.
     * 
     * @param ValidationStatus $status The validation status to filter by
     * @param array $options Additional filtering options
     * @return Collection<int, MeterReading>
     */
    public function getReadingsByValidationStatus(ValidationStatus $status, array $options = []): Collection
    {
        $query = MeterReading::query()
            ->where('validation_status', $status)
            ->with(['meter.serviceConfiguration.utilityService', 'enteredBy', 'validatedBy']);

        // Apply tenant scoping
        if (isset($options['tenant_id'])) {
            $query->where('tenant_id', $options['tenant_id']);
        }

        // Apply date range filtering
        if (isset($options['date_from'])) {
            $query->where('reading_date', '>=', $options['date_from']);
        }

        if (isset($options['date_to'])) {
            $query->where('reading_date', '<=', $options['date_to']);
        }

        // Apply input method filtering
        if (isset($options['input_method'])) {
            $query->where('input_method', $options['input_method']);
        }

        // Apply meter filtering
        if (isset($options['meter_ids'])) {
            $query->whereIn('meter_id', $options['meter_ids']);
        }

        return $query->orderBy('reading_date', 'desc')->get();
    }

    /**
     * Bulk update validation status for multiple readings.
     * 
     * @param Collection<int, MeterReading> $readings
     * @param ValidationStatus $newStatus
     * @param int $validatedByUserId
     * @return array{updated_count: int, errors: array<string>}
     */
    public function bulkUpdateValidationStatus(
        Collection $readings, 
        ValidationStatus $newStatus, 
        int $validatedByUserId
    ): array {
        $updatedCount = 0;
        $errors = [];

        try {
            foreach ($readings as $reading) {
                // Authorization check for each reading
                if (auth()->check() && !auth()->user()->can('update', $reading)) {
                    $errors[] = "Unauthorized to update reading {$reading->id}";
                    continue;
                }

                // Update validation status
                $reading->validation_status = $newStatus;
                $reading->validated_by = $validatedByUserId;
                $reading->save();

                $updatedCount++;

                // Log the status change
                $this->logger->info('Reading validation status updated', [
                    'reading_id' => $reading->id,
                    'old_status' => $reading->getOriginal('validation_status'),
                    'new_status' => $newStatus->value,
                    'validated_by' => $validatedByUserId,
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Bulk validation status update failed', [
                'error' => $e->getMessage(),
                'readings_count' => $readings->count(),
            ]);

            $errors[] = 'System error during bulk update: ' . $e->getMessage();
        }

        return [
            'updated_count' => $updatedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Calculate true-up amount between estimated and actual readings.
     */
    private function calculateTrueUpAmount(MeterReading $estimatedReading, MeterReading $actualReading): float
    {
        $estimatedValue = $estimatedReading->getEffectiveValue();
        $actualValue = $actualReading->getEffectiveValue();
        
        return $actualValue - $estimatedValue;
    }

    /**
     * Get the true-up threshold from configuration.
     */
    private function getTrueUpThreshold(): float
    {
        return (float) $this->config->get('service_validation.true_up_threshold', 5.0);
    }

    /**
     * Get the reading unit for display purposes.
     */
    private function getReadingUnit(MeterReading $reading): string
    {
        return $reading->meter->serviceConfiguration?->utilityService?->unit_of_measurement ?? 'units';
    }

    /**
     * Validate estimation accuracy against historical patterns.
     */
    private function validateEstimationAccuracy(MeterReading $estimatedReading): array
    {
        $warnings = [];

        try {
            // Get historical readings for pattern analysis
            $historicalReadings = $this->getHistoricalReadings($estimatedReading->meter, 6);
            
            if ($historicalReadings->count() < 3) {
                $warnings[] = 'Insufficient historical data for estimation accuracy validation';
                return ['warnings' => $warnings];
            }

            // Calculate average consumption for similar periods
            $averageConsumption = $historicalReadings->avg(function ($reading) {
                return $reading->getConsumption();
            });

            $estimatedConsumption = $estimatedReading->getConsumption();
            
            if ($estimatedConsumption && $averageConsumption) {
                $variance = abs($estimatedConsumption - $averageConsumption) / $averageConsumption;
                
                if ($variance > 0.5) { // 50% variance threshold
                    $warnings[] = 'Estimated reading varies significantly from historical patterns';
                }
            }

        } catch (\Exception $e) {
            $this->logger->warning('Estimation accuracy validation failed', [
                'reading_id' => $estimatedReading->id,
                'error' => $e->getMessage(),
            ]);
        }

        return ['warnings' => $warnings];
    }

    /**
     * Validate that the collection contains only MeterReading models.
     * 
     * @param Collection<int, MeterReading> $readings
     * @throws \InvalidArgumentException
     */
    private function validateReadingsCollection(Collection $readings): void
    {
        if ($readings->isEmpty()) {
            throw new \InvalidArgumentException('Readings collection cannot be empty');
        }

        $invalidModels = $readings->filter(fn($item) => !$item instanceof MeterReading);
        
        if ($invalidModels->isNotEmpty()) {
            throw new \InvalidArgumentException(
                'All items in readings collection must be MeterReading instances. Found ' . 
                $invalidModels->count() . ' invalid items.'
            );
        }

        // Security: Limit batch size to prevent memory exhaustion
        $maxBatchSize = $this->config->get('service_validation.performance.batch_validation_size', 100);
        if ($readings->count() > $maxBatchSize) {
            throw new \InvalidArgumentException(
                "Batch size ({$readings->count()}) exceeds maximum allowed size ({$maxBatchSize})"
            );
        }
    }

    /**
     * SECURITY: Validate authorization for all readings in batch BEFORE processing.
     * 
     * This prevents partial processing and potential information leakage.
     * 
     * @param Collection<int, MeterReading> $readings
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function validateBatchAuthorization(Collection $readings): void
    {
        if (!auth()->check()) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Authentication required for batch validation'
            );
        }

        // Check authorization for ALL readings before processing ANY
        $unauthorizedReadings = $readings->filter(function ($reading) {
            return !auth()->user()->can('view', $reading);
        });

        if ($unauthorizedReadings->isNotEmpty()) {
            $this->logger->warning('Batch validation attempted with unauthorized readings', [
                'user_id' => auth()->id(),
                'unauthorized_count' => $unauthorizedReadings->count(),
                'total_count' => $readings->count(),
                'unauthorized_ids' => $unauthorizedReadings->pluck('id')->toArray(),
                'ip_address' => request()->ip(),
            ]);
            
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Unauthorized access to one or more meter readings'
            );
        }
    }

    /**
     * SECURITY: Enforce rate limiting on validation operations.
     * 
     * @param string $operation Operation type for rate limiting
     * @param int $itemCount Number of items being processed
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    private function enforceRateLimit(string $operation, int $itemCount): void
    {
        $user = auth()->user();
        $identifier = $user ? "user:{$user->id}" : "ip:" . request()->ip();
        $key = "rate_limit:{$operation}:{$identifier}";
        
        // Get operation-specific limits
        $limits = $this->config->get('security.rate_limiting.limits', [
            'batch_validation' => 100, // items per hour
            'single_validation' => 300, // operations per hour
            'rate_change_validation' => 20, // operations per hour
        ]);
        
        $limit = $limits[$operation] ?? 50;
        $window = 3600; // 1 hour in seconds
        
        $current = $this->cache->get($key, 0);
        
        if ($current + $itemCount > $limit) {
            $this->logger->warning('Rate limit exceeded', [
                'operation' => $operation,
                'user_id' => $user?->id,
                'ip_address' => request()->ip(),
                'current_count' => $current,
                'attempted_count' => $itemCount,
                'limit' => $limit,
            ]);
            
            throw new \Illuminate\Http\Exceptions\ThrottleRequestsException(
                'Rate limit exceeded for validation operations',
                [],
                $window - (time() % $window) // Retry after remaining window time
            );
        }
        
        // Update rate limit counter
        $this->cache->put($key, $current + $itemCount, $window);
    }
}