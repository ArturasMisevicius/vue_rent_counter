<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InputMethod;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Services\Validation\ValidationRuleFactory;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;
use App\Services\Validation\Validators\ConsumptionValidator;
use App\Services\Validation\Validators\SeasonalValidator;
use App\Services\Validation\Validators\DataQualityValidator;
use App\Services\Validation\Validators\BusinessRuleValidator;
use App\Services\Validation\Validators\InputMethodValidator;
use App\Services\Validation\Validators\RateChangeValidator;
use App\ValueObjects\SummerPeriod;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * ServiceValidationEngine - Orchestrates utility service validation using the Strategy pattern
 * 
 * This refactored service uses a modular validation architecture with individual validators
 * for different validation concerns, improving maintainability and testability.
 * 
 * ## Architecture
 * - **Strategy Pattern**: Different validation strategies for different concerns
 * - **Factory Pattern**: Centralized validator creation and management
 * - **Value Objects**: Immutable validation context and results
 * - **Chain of Responsibility**: Validators can be chained and combined
 * 
 * ## Key Features
 * - Modular validation architecture with single responsibility validators
 * - Immutable validation context and results for thread safety
 * - Comprehensive caching and performance optimization
 * - Graceful error handling with detailed logging
 * - Extensible validator registration system
 * 
 * ## Performance Features
 * - Cached validation rules and historical data
 * - Optimized batch validation with eager loading
 * - Efficient validator selection based on context
 * - Memoized configuration values
 * 
 * @see \App\Services\Validation\ValidationRuleFactory
 * @see \App\Services\Validation\ValidationContext
 * @see \App\Services\Validation\ValidationResult
 * 
 * @package App\Services
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
     * @return array Array of validation results with errors and warnings
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

            return ValidationResult::withError('Validation system error: ' . $e->getMessage())->toArray();
        }
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
     * Validate consumption limits based on service configuration and historical data.
     * 
     * @deprecated Use ConsumptionValidator instead
     */
    protected function validateConsumptionLimits(MeterReading $reading, ?ServiceConfiguration $serviceConfig): array
    {
        $result = [
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
            'validation_metadata' => ['rules_applied' => ['consumption_limits']],
        ];

        // Get consumption value
        $consumption = $reading->getConsumption();
        if ($consumption === null) {
            // No previous reading available - this is acceptable for first readings
            $result['warnings'][] = 'No previous reading available for consumption calculation';
            return $result;
        }

        // Get validation rules from service configuration or defaults
        $limits = $this->getConsumptionLimits($serviceConfig);
        
        // 1. Absolute limits validation
        if ($consumption < $limits['min_consumption']) {
            if ($consumption < self::MIN_CONSUMPTION_THRESHOLD) {
                $result['warnings'][] = "Very low consumption detected: {$consumption} {$this->getUnit($serviceConfig)}";
            } else {
                $result['errors'][] = "Consumption below minimum limit: {$consumption} < {$limits['min_consumption']} {$this->getUnit($serviceConfig)}";
            }
        }

        if ($consumption > $limits['max_consumption']) {
            $result['errors'][] = "Consumption exceeds maximum limit: {$consumption} > {$limits['max_consumption']} {$this->getUnit($serviceConfig)}";
        }

        // 2. Historical variance validation
        $varianceValidation = $this->validateConsumptionVariance($reading, $consumption, $limits);
        $result['errors'] = array_merge($result['errors'], $varianceValidation['errors']);
        $result['warnings'] = array_merge($result['warnings'], $varianceValidation['warnings']);

        // 3. Anomaly detection
        $anomalyValidation = $this->detectConsumptionAnomalies($reading, $consumption, $serviceConfig);
        $result['warnings'] = array_merge($result['warnings'], $anomalyValidation['warnings']);
        $result['recommendations'] = array_merge($result['recommendations'], $anomalyValidation['recommendations']);

        return $result;
    }

    /**
     * Validate seasonal adjustments building on gyvatukas summer/winter logic.
     * 
     * Applies seasonal validation rules based on utility type and time of year,
     * leveraging existing gyvatukas seasonal calculation patterns.
     */
    protected function validateSeasonalAdjustments(MeterReading $reading, ?ServiceConfiguration $serviceConfig): array
    {
        $result = [
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
            'validation_metadata' => ['rules_applied' => ['seasonal_adjustments']],
        ];

        $readingDate = $reading->reading_date;
        $consumption = $reading->getConsumption();
        
        if ($consumption === null) {
            return $result; // No consumption to validate
        }

        // Get seasonal adjustments configuration
        $seasonalConfig = $this->getSeasonalAdjustments($serviceConfig);
        
        // Determine if this is heating season (using gyvatukas logic)
        $isHeatingSeason = $this->gyvatukasCalculator->isHeatingSeason($readingDate);
        $isSummerPeriod = $this->gyvatukasCalculator->isSummerPeriod($readingDate);

        // Apply seasonal validation based on utility type
        $utilityType = $serviceConfig?->utilityService?->service_type_bridge;
        
        if ($utilityType && isset($seasonalConfig[$utilityType->value])) {
            $typeConfig = $seasonalConfig[$utilityType->value];
            
            // Heating-related services have different expectations in summer vs winter
            if ($utilityType->value === 'heating') {
                if ($isSummerPeriod && $consumption > $typeConfig['summer_max_threshold']) {
                    $result['warnings'][] = "High heating consumption during summer period: {$consumption} {$this->getUnit($serviceConfig)}";
                }
                
                if ($isHeatingSeason && $consumption < $typeConfig['winter_min_threshold']) {
                    $result['warnings'][] = "Low heating consumption during heating season: {$consumption} {$this->getUnit($serviceConfig)}";
                }
            }
            
            // Water consumption patterns
            if ($utilityType->value === 'water') {
                $expectedRange = $isSummerPeriod ? $typeConfig['summer_range'] : $typeConfig['winter_range'];
                
                if ($consumption < $expectedRange['min'] || $consumption > $expectedRange['max']) {
                    $season = $isSummerPeriod ? 'summer' : 'winter';
                    $result['recommendations'][] = "Water consumption outside typical {$season} range: {$consumption} {$this->getUnit($serviceConfig)}";
                }
            }
        }

        // Store seasonal metadata
        $result['validation_metadata']['seasonal_adjustments'] = [
            'is_heating_season' => $isHeatingSeason,
            'is_summer_period' => $isSummerPeriod,
            'utility_type' => $utilityType?->value,
            'applied_thresholds' => $seasonalConfig[$utilityType?->value ?? 'default'] ?? [],
        ];

        return $result;
    }

    /**
     * Validate data quality leveraging existing meter reading audit trail.
     * 
     * Performs comprehensive data quality checks including duplicate detection,
     * reading sequence validation, and audit trail consistency.
     */
    protected function validateDataQuality(MeterReading $reading, ?ServiceConfiguration $serviceConfig): array
    {
        $result = [
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
            'validation_metadata' => ['rules_applied' => ['data_quality']],
        ];

        // 1. Duplicate reading detection
        $duplicateCheck = $this->checkForDuplicateReadings($reading);
        if (!empty($duplicateCheck['duplicates'])) {
            $result['errors'][] = 'Duplicate reading detected for the same date and meter';
            $result['validation_metadata']['duplicate_readings'] = $duplicateCheck['duplicates'];
        }

        // 2. Reading sequence validation
        $sequenceValidation = $this->validateReadingSequence($reading);
        $result['errors'] = array_merge($result['errors'], $sequenceValidation['errors']);
        $result['warnings'] = array_merge($result['warnings'], $sequenceValidation['warnings']);

        // 3. Audit trail validation
        if ($reading->exists) {
            $auditValidation = $this->validateAuditTrail($reading);
            $result['warnings'] = array_merge($result['warnings'], $auditValidation['warnings']);
        }

        // 4. Photo validation for OCR readings
        if ($reading->input_method === InputMethod::PHOTO_OCR) {
            $photoValidation = $this->validatePhotoReading($reading);
            $result['errors'] = array_merge($result['errors'], $photoValidation['errors']);
            $result['recommendations'] = array_merge($result['recommendations'], $photoValidation['recommendations']);
        }

        return $result;
    }

    /**
     * Validate business rules specific to the service configuration.
     * 
     * Applies service-specific validation rules and constraints defined
     * in the utility service configuration.
     */
    protected function validateBusinessRules(MeterReading $reading, ServiceConfiguration $serviceConfig): array
    {
        $result = [
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
            'validation_metadata' => ['rules_applied' => ['business_rules']],
        ];

        $utilityService = $serviceConfig->utilityService;
        $businessRules = $utilityService->business_logic_config ?? [];

        // 1. Reading frequency validation
        if (isset($businessRules['reading_frequency'])) {
            $frequencyValidation = $this->validateReadingFrequency($reading, $businessRules['reading_frequency']);
            $result['warnings'] = array_merge($result['warnings'], $frequencyValidation['warnings']);
        }

        // 2. Service-specific constraints
        if (isset($businessRules['constraints'])) {
            $constraintValidation = $this->validateServiceConstraints($reading, $businessRules['constraints'], $serviceConfig);
            $result['errors'] = array_merge($result['errors'], $constraintValidation['errors']);
            $result['warnings'] = array_merge($result['warnings'], $constraintValidation['warnings']);
        }

        // 3. Configuration validation
        $configValidation = $serviceConfig->validateConfiguration();
        if (!empty($configValidation)) {
            $result['errors'] = array_merge($result['errors'], $configValidation);
        }

        return $result;
    }

    /**
     * Validate input method specific requirements.
     * 
     * Applies validation rules based on how the reading was collected,
     * extending existing InputMethod enum validation logic.
     */
    protected function validateInputMethod(MeterReading $reading): array
    {
        $result = [
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
            'validation_metadata' => ['rules_applied' => ['input_method']],
        ];

        $inputMethod = $reading->input_method;

        // Photo OCR specific validation
        if ($inputMethod === InputMethod::PHOTO_OCR) {
            if (empty($reading->photo_path)) {
                $result['errors'][] = 'Photo path required for OCR readings';
            }
            
            if ($reading->validation_status === ValidationStatus::PENDING) {
                $result['recommendations'][] = 'OCR reading requires manual validation';
            }
        }

        // Estimated reading validation
        if ($inputMethod === InputMethod::ESTIMATED) {
            if ($reading->validation_status !== ValidationStatus::REQUIRES_REVIEW) {
                $result['warnings'][] = 'Estimated readings should be marked for review';
            }
            
            $result['recommendations'][] = 'Replace estimated reading with actual reading when available';
        }

        // API integration validation
        if ($inputMethod === InputMethod::API_INTEGRATION) {
            // Validate that reading came from a trusted source
            if (empty($reading->entered_by)) {
                $result['warnings'][] = 'API integration reading missing source identification';
            }
        }

        // CSV import validation
        if ($inputMethod === InputMethod::CSV_IMPORT) {
            if ($reading->validation_status === ValidationStatus::PENDING) {
                $result['recommendations'][] = 'Batch imported reading should be validated';
            }
        }

        return $result;
    }

    /**
     * Validate rate change restrictions using the dedicated RateChangeValidator.
     * 
     * @param ServiceConfiguration $serviceConfig The service configuration to validate
     * @param array $newRateSchedule The proposed new rate schedule
     * @return array Validation results
     */
    public function validateRateChangeRestrictions(ServiceConfiguration $serviceConfig, array $newRateSchedule): array
    {
        $validator = $this->validatorFactory->getValidator('rate_change');
        
        if (!$validator instanceof \App\Services\Validation\Validators\RateChangeValidator) {
            $this->logger->error('RateChangeValidator not found or invalid type');
            return ValidationResult::withError('Rate change validator not available')->toArray();
        }

        $result = $validator->validateRateChangeRestrictions($serviceConfig, $newRateSchedule);
        return $result->toArray();
    }

    /**
     * Batch validate multiple meter readings for performance.
     * 
     * Optimized validation for processing multiple readings simultaneously,
     * with shared cache and reduced database queries.
     * 
     * @param Collection $readings Collection of MeterReading models
     * @param array $options Validation options
     * @return array Batch validation results
     */
    public function batchValidateReadings(Collection $readings, array $options = []): array
    {
        $batchResult = [
            'total_readings' => $readings->count(),
            'valid_readings' => 0,
            'invalid_readings' => 0,
            'warnings_count' => 0,
            'results' => [],
            'summary' => [],
        ];

        // Pre-load service configurations to reduce queries
        $serviceConfigIds = $readings->pluck('meter.service_configuration_id')->filter()->unique();
        $serviceConfigs = ServiceConfiguration::with(['utilityService', 'tariff', 'provider'])
            ->whereIn('id', $serviceConfigIds)
            ->get()
            ->keyBy('id');

        foreach ($readings as $reading) {
            $serviceConfig = $serviceConfigs->get($reading->meter->service_configuration_id);
            $validationResult = $this->validateMeterReading($reading, $serviceConfig);
            
            $batchResult['results'][$reading->id] = $validationResult;
            
            if ($validationResult['is_valid']) {
                $batchResult['valid_readings']++;
            } else {
                $batchResult['invalid_readings']++;
            }
            
            $batchResult['warnings_count'] += count($validationResult['warnings']);
        }

        // Generate summary statistics
        $batchResult['summary'] = $this->generateBatchSummary($batchResult);

        return $batchResult;
    }

    /**
     * Get consumption limits for a service configuration.
     */
    protected function getConsumptionLimits(?ServiceConfiguration $serviceConfig): array
    {
        $cacheKey = $this->buildCacheKey('consumption_limits', $serviceConfig?->id ?? 'default');
        
        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($serviceConfig) {
                $config = $this->getValidationConfig();
                
                if ($serviceConfig && $serviceConfig->utilityService) {
                    $serviceRules = $serviceConfig->utilityService->validation_rules ?? [];
                    $consumptionLimits = $serviceRules['consumption_limits'] ?? [];
                } else {
                    $consumptionLimits = [];
                }

                return [
                    'min_consumption' => $consumptionLimits['min'] ?? $config['default_min_consumption'],
                    'max_consumption' => $consumptionLimits['max'] ?? $config['default_max_consumption'],
                    'variance_threshold' => $consumptionLimits['variance_threshold'] ?? self::DEFAULT_CONSUMPTION_VARIANCE_THRESHOLD,
                ];
            }
        );
    }

    /**
     * Validate consumption variance against historical averages.
     */
    protected function validateConsumptionVariance(MeterReading $reading, float $consumption, array $limits): array
    {
        $result = ['errors' => [], 'warnings' => []];

        // Get historical average for comparison
        $historicalAverage = $this->getHistoricalConsumptionAverage($reading->meter, 6); // 6 months
        
        if ($historicalAverage > 0) {
            $variance = abs($consumption - $historicalAverage) / $historicalAverage;
            
            if ($variance > $limits['variance_threshold']) {
                $percentageChange = round($variance * 100, 1);
                $result['warnings'][] = "Consumption varies significantly from historical average: {$percentageChange}% change";
            }
        }

        return $result;
    }

    /**
     * Detect consumption anomalies using statistical analysis.
     */
    protected function detectConsumptionAnomalies(MeterReading $reading, float $consumption, ?ServiceConfiguration $serviceConfig): array
    {
        $result = ['warnings' => [], 'recommendations' => []];

        // Get recent readings for statistical analysis
        $recentReadings = $this->getRecentReadings($reading->meter, 12); // 12 months
        
        if ($recentReadings->count() >= 3) {
            $consumptions = $recentReadings->map(fn($r) => $r->getConsumption())->filter();
            
            if ($consumptions->count() >= 3) {
                $average = $consumptions->average();
                $stdDev = $this->calculateStandardDeviation($consumptions->toArray());
                
                // Check for outliers (more than 2 standard deviations from mean)
                if ($stdDev > 0 && abs($consumption - $average) > (2 * $stdDev)) {
                    $result['warnings'][] = "Consumption appears to be an outlier based on historical patterns";
                    $result['recommendations'][] = "Consider verifying this reading manually";
                }
                
                // Check for extreme values
                if ($consumption > ($average * self::MAX_CONSUMPTION_MULTIPLIER)) {
                    $result['warnings'][] = "Consumption is extremely high compared to historical average";
                }
            }
        }

        return $result;
    }

    /**
     * Check for duplicate readings.
     */
    protected function checkForDuplicateReadings(MeterReading $reading): array
    {
        $duplicates = MeterReading::where('meter_id', $reading->meter_id)
            ->where('reading_date', $reading->reading_date)
            ->when($reading->exists, fn($q) => $q->where('id', '!=', $reading->id))
            ->get();

        return ['duplicates' => $duplicates->toArray()];
    }

    /**
     * Validate reading sequence and progression.
     */
    protected function validateReadingSequence(MeterReading $reading): array
    {
        $result = ['errors' => [], 'warnings' => []];

        $previousReading = $this->meterReadingService->getPreviousReading(
            $reading->meter,
            $reading->zone,
            $reading->reading_date->toDateString()
        );

        if ($previousReading && $reading->value < $previousReading->value) {
            // Check if this might be a meter rollover
            $maxMeterValue = $this->getMaxMeterValue($reading->meter);
            $possibleRollover = ($previousReading->value > ($maxMeterValue * 0.9)) && ($reading->value < ($maxMeterValue * 0.1));
            
            if (!$possibleRollover) {
                $result['errors'][] = "Reading value is less than previous reading (possible meter rollback)";
            } else {
                $result['warnings'][] = "Possible meter rollover detected";
            }
        }

        return $result;
    }

    /**
     * Validate audit trail consistency.
     */
    protected function validateAuditTrail(MeterReading $reading): array
    {
        $result = ['warnings' => []];

        $auditCount = $reading->auditTrail()->count();
        
        if ($auditCount === 0 && $reading->wasChanged()) {
            $result['warnings'][] = "Reading has been modified but no audit trail exists";
        }

        return $result;
    }

    /**
     * Validate photo reading requirements.
     */
    protected function validatePhotoReading(MeterReading $reading): array
    {
        $result = ['errors' => [], 'recommendations' => []];

        if (empty($reading->photo_path)) {
            $result['errors'][] = "Photo path is required for OCR readings";
        } elseif (!file_exists(storage_path('app/public/' . $reading->photo_path))) {
            $result['errors'][] = "Photo file not found at specified path";
        }

        if ($reading->validation_status === ValidationStatus::PENDING) {
            $result['recommendations'][] = "OCR reading requires manual validation for accuracy";
        }

        return $result;
    }

    /**
     * Validate reading frequency requirements.
     */
    protected function validateReadingFrequency(MeterReading $reading, array $frequencyRules): array
    {
        $result = ['warnings' => []];

        $requiredFrequency = $frequencyRules['required_days'] ?? 30;
        $lastReading = $this->meterReadingService->getPreviousReading(
            $reading->meter,
            $reading->zone,
            $reading->reading_date->toDateString()
        );

        if ($lastReading) {
            $daysSinceLastReading = $reading->reading_date->diffInDays($lastReading->reading_date);
            
            if ($daysSinceLastReading > $requiredFrequency) {
                $result['warnings'][] = "Reading frequency exceeds recommended interval: {$daysSinceLastReading} days since last reading";
            }
        }

        return $result;
    }

    /**
     * Validate service-specific constraints.
     */
    protected function validateServiceConstraints(MeterReading $reading, array $constraints, ServiceConfiguration $serviceConfig): array
    {
        $result = ['errors' => [], 'warnings' => []];

        foreach ($constraints as $constraint) {
            $constraintResult = $this->evaluateConstraint($reading, $constraint, $serviceConfig);
            
            if ($constraintResult['violated']) {
                $severity = $constraint['severity'] ?? 'warning';
                
                if ($severity === 'error') {
                    $result['errors'][] = $constraintResult['message'];
                } else {
                    $result['warnings'][] = $constraintResult['message'];
                }
            }
        }

        return $result;
    }

    /**
     * Validate rate change frequency.
     */
    protected function validateRateChangeFrequency(ServiceConfiguration $serviceConfig): array
    {
        $result = ['errors' => [], 'warnings' => []];

        $config = $this->getValidationConfig();
        $minDaysBetweenChanges = $config['rate_change_frequency_days'] ?? self::DEFAULT_RATE_CHANGE_FREQUENCY_DAYS;

        // Check when the rate schedule was last updated
        $lastUpdate = $serviceConfig->updated_at;
        $daysSinceUpdate = now()->diffInDays($lastUpdate);

        if ($daysSinceUpdate < $minDaysBetweenChanges) {
            $result['warnings'][] = "Rate change requested within {$minDaysBetweenChanges} day minimum period";
        }

        return $result;
    }

    /**
     * Validate effective dates for rate changes.
     */
    protected function validateEffectiveDates(ServiceConfiguration $serviceConfig, array $newRateSchedule): array
    {
        $result = ['errors' => [], 'warnings' => []];

        $effectiveFrom = $newRateSchedule['effective_from'] ?? null;
        
        if ($effectiveFrom) {
            $effectiveDate = Carbon::parse($effectiveFrom);
            
            // Effective date should not be in the past (with some tolerance)
            if ($effectiveDate->isPast() && $effectiveDate->diffInDays(now()) > 1) {
                $result['warnings'][] = "Effective date is in the past: {$effectiveDate->toDateString()}";
            }
            
            // Effective date should not be too far in the future
            if ($effectiveDate->isFuture() && $effectiveDate->diffInDays(now()) > 365) {
                $result['warnings'][] = "Effective date is more than one year in the future";
            }
        }

        return $result;
    }

    /**
     * Validate rate schedule structure.
     */
    protected function validateRateScheduleStructure(array $rateSchedule, ServiceConfiguration $serviceConfig): array
    {
        $result = ['errors' => [], 'warnings' => []];

        $pricingModel = $serviceConfig->pricing_model;
        
        // Validate structure based on pricing model
        switch ($pricingModel) {
            case \App\Enums\PricingModel::TIERED_RATES:
                if (empty($rateSchedule['tiers'])) {
                    $result['errors'][] = "Tiered pricing model requires tier definitions";
                }
                break;
                
            case \App\Enums\PricingModel::TIME_OF_USE:
                if (empty($rateSchedule['time_slots'])) {
                    $result['errors'][] = "Time-of-use pricing model requires time slot definitions";
                } else {
                    // Validate time slots using existing TimeRangeValidator
                    $timeValidator = app(\App\Services\TimeRangeValidator::class);
                    $timeValidationErrors = $timeValidator->validate($rateSchedule['time_slots']);
                    $result['errors'] = array_merge($result['errors'], $timeValidationErrors);
                }
                break;
        }

        return $result;
    }

    /**
     * Validate configuration overlaps.
     */
    protected function validateConfigurationOverlaps(ServiceConfiguration $serviceConfig, array $newRateSchedule): array
    {
        $result = ['errors' => [], 'warnings' => []];

        // Check for overlapping service configurations on the same property
        $overlappingConfigs = ServiceConfiguration::where('property_id', $serviceConfig->property_id)
            ->where('utility_service_id', $serviceConfig->utility_service_id)
            ->where('id', '!=', $serviceConfig->id)
            ->effectiveOn()
            ->get();

        if ($overlappingConfigs->isNotEmpty()) {
            $result['warnings'][] = "Multiple active configurations found for the same utility service on this property";
        }

        return $result;
    }

    /**
     * Helper methods
     */

    protected function getValidationConfig(): array
    {
        return $this->validationConfig ??= $this->config->get('service_validation', [
            'default_min_consumption' => 0,
            'default_max_consumption' => 10000,
            'rate_change_frequency_days' => self::DEFAULT_RATE_CHANGE_FREQUENCY_DAYS,
        ]);
    }

    protected function getSeasonalAdjustments(?ServiceConfiguration $serviceConfig): array
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

    protected function getUnit(?ServiceConfiguration $serviceConfig): string
    {
        return $serviceConfig?->utilityService?->unit_of_measurement ?? 'units';
    }

    protected function getHistoricalConsumptionAverage($meter, int $months): float
    {
        return $meter->readings()
            ->where('reading_date', '>=', now()->subMonths($months))
            ->where('validation_status', ValidationStatus::VALIDATED)
            ->get()
            ->map(fn($r) => $r->getConsumption())
            ->filter()
            ->average() ?? 0.0;
    }

    protected function getRecentReadings($meter, int $months): Collection
    {
        return $meter->readings()
            ->where('reading_date', '>=', now()->subMonths($months))
            ->orderBy('reading_date', 'desc')
            ->get();
    }

    protected function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) return 0;
        
        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / ($count - 1);
        
        return sqrt($variance);
    }

    protected function getMaxMeterValue($meter): float
    {
        // Default meter maximum value - could be configured per meter type
        return 999999.99;
    }

    protected function evaluateConstraint(MeterReading $reading, array $constraint, ServiceConfiguration $serviceConfig): array
    {
        // Simplified constraint evaluation - could be extended with expression language
        $field = $constraint['field'] ?? 'value';
        $operator = $constraint['operator'] ?? '>';
        $value = $constraint['value'] ?? 0;
        $message = $constraint['message'] ?? "Constraint violation on field {$field}";

        $readingValue = $reading->{$field} ?? $reading->getEffectiveValue();
        
        $violated = match ($operator) {
            '>' => $readingValue > $value,
            '<' => $readingValue < $value,
            '>=' => $readingValue >= $value,
            '<=' => $readingValue <= $value,
            '==' => $readingValue == $value,
            '!=' => $readingValue != $value,
            default => false,
        };

        return [
            'violated' => $violated,
            'message' => $message,
        ];
    }

    protected function mergeValidationResults(array &$target, array $source): void
    {
        $target['errors'] = array_merge($target['errors'] ?? [], $source['errors'] ?? []);
        $target['warnings'] = array_merge($target['warnings'] ?? [], $source['warnings'] ?? []);
        $target['recommendations'] = array_merge($target['recommendations'] ?? [], $source['recommendations'] ?? []);
        
        if (isset($source['validation_metadata'])) {
            $target['validation_metadata'] = array_merge(
                $target['validation_metadata'] ?? [],
                $source['validation_metadata']
            );
        }
    }

    protected function logValidationResult(MeterReading $reading, array $validationResult): void
    {
        $this->logger->info('Meter reading validation completed', [
            'reading_id' => $reading->id,
            'meter_id' => $reading->meter_id,
            'is_valid' => $validationResult['is_valid'],
            'error_count' => count($validationResult['errors']),
            'warning_count' => count($validationResult['warnings']),
            'input_method' => $reading->input_method->value,
        ]);
    }

    protected function generateBatchSummary(array $batchResult): array
    {
        return [
            'validation_rate' => $batchResult['total_readings'] > 0 
                ? round(($batchResult['valid_readings'] / $batchResult['total_readings']) * 100, 2) 
                : 0,
            'average_warnings_per_reading' => $batchResult['total_readings'] > 0 
                ? round($batchResult['warnings_count'] / $batchResult['total_readings'], 2) 
                : 0,
        ];
    }

    protected function buildCacheKey(string $type, $identifier): string
    {
        return sprintf('%s:%s:%s', self::CACHE_PREFIX, $type, $identifier);
    }
}