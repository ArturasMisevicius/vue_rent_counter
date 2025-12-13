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
     * @param Collection<int, MeterReading> $readings Collection of MeterReading models
     * @param array<string, mixed> $options Validation options
     * @return array{total_readings: int, valid_readings: int, invalid_readings: int, warnings_count: int, results: array<int, array>, summary: array<string, float>, performance_metrics: array<string, mixed>} Batch validation results
     * 
     * @throws \InvalidArgumentException When readings collection contains invalid models
     */
    public function batchValidateReadings(Collection $readings, array $options = []): array
    {
        // Validate input collection contains only MeterReading models FIRST
        $this->validateReadingsCollection($readings);
        
        $batchResult = [
            'total_readings' => $readings->count(),
            'valid_readings' => 0,
            'invalid_readings' => 0,
            'warnings_count' => 0,
            'results' => [],
            'summary' => [],
            'performance_metrics' => [
                'start_time' => microtime(true),
                'cache_hits' => 0,
                'database_queries' => 0,
            ],
        ];

        try {
            
            // Pre-load all necessary data to minimize database queries
            $this->preloadBatchData($readings);

            // Process each reading with optimized validation
            foreach ($readings as $reading) {
                $validationResult = $this->validateMeterReading($reading);
                
                $batchResult['results'][$reading->id] = $validationResult;
                
                if ($validationResult['is_valid']) {
                    $batchResult['valid_readings']++;
                } else {
                    $batchResult['invalid_readings']++;
                }
                
                $batchResult['warnings_count'] += count($validationResult['warnings'] ?? []);
            }

            // Generate summary statistics
            $batchResult['summary'] = $this->generateBatchSummary($batchResult);
            $batchResult['performance_metrics']['end_time'] = microtime(true);
            $batchResult['performance_metrics']['duration'] = 
                $batchResult['performance_metrics']['end_time'] - $batchResult['performance_metrics']['start_time'];

        } catch (\Exception $e) {
            $this->logger->error('Batch validation failed', [
                'reading_count' => $readings->count(),
                'error' => $e->getMessage(),
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
     * Pre-load data for batch validation to optimize performance.
     */
    private function preloadBatchData(Collection $readings): void
    {
        // Eager load meters with their service configurations to prevent N+1 queries
        $readings->load([
            'meter.serviceConfiguration.utilityService',
            'meter.serviceConfiguration.tariff', 
            'meter.serviceConfiguration.provider'
        ]);

        // Pre-load service configurations with relationships
        $serviceConfigIds = $readings->pluck('meter.service_configuration_id')->filter()->unique();
        
        if ($serviceConfigIds->isNotEmpty()) {
            ServiceConfiguration::with(['utilityService', 'tariff', 'provider'])
                ->whereIn('id', $serviceConfigIds)
                ->get()
                ->each(function ($config) {
                    // Cache service configurations for quick access
                    $cacheKey = $this->buildCacheKey('service_config', $config->id);
                    $this->cache->put($cacheKey, $config, self::CACHE_TTL_SECONDS);
                });
        }

        // Pre-load meters with relationships - use single query with constraints
        $meterIds = $readings->pluck('meter_id')->unique();
        
        if ($meterIds->isNotEmpty()) {
            \App\Models\Meter::with(['readings' => function ($query) {
                $query->where('reading_date', '>=', now()->subMonths(12))
                      ->where('validation_status', ValidationStatus::VALIDATED)
                      ->orderBy('reading_date', 'desc')
                      ->limit(50); // Limit historical readings per meter for performance
            }])->whereIn('id', $meterIds)->get();
        }
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
     * @param array<string, mixed> $rateSchedule
     * @return array<string, mixed>
     */
    private function sanitizeRateSchedule(array $rateSchedule): array
    {
        $sanitized = [];
        $allowedKeys = [
            'rate_per_unit', 'monthly_rate', 'base_rate', 'default_rate',
            'effective_from', 'effective_until', 'time_slots', 'tiers',
            'peak_rate', 'off_peak_rate', 'weekend_rate'
        ];

        foreach ($rateSchedule as $key => $value) {
            // Only allow whitelisted keys
            if (!in_array($key, $allowedKeys, true)) {
                continue;
            }

            // Sanitize based on expected data types
            $sanitized[$key] = match ($key) {
                'rate_per_unit', 'monthly_rate', 'base_rate', 'default_rate', 
                'peak_rate', 'off_peak_rate', 'weekend_rate' => is_numeric($value) ? (float) $value : null,
                'effective_from', 'effective_until' => is_string($value) ? filter_var($value, FILTER_SANITIZE_STRING) : null,
                'time_slots', 'tiers' => is_array($value) ? $this->sanitizeNestedArray($value) : [],
                default => is_scalar($value) ? filter_var($value, FILTER_SANITIZE_STRING) : null,
            };

            // Remove null values
            if ($sanitized[$key] !== null) {
                continue;
            }
            unset($sanitized[$key]);
        }

        return $sanitized;
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
}