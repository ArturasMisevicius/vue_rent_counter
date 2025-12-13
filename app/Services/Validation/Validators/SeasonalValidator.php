<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;
use App\Services\GyvatukasCalculator;

/**
 * Validates seasonal adjustments building on gyvatukas summer/winter logic.
 */
final class SeasonalValidator extends AbstractValidator
{
    public function __construct(
        \Illuminate\Contracts\Cache\Repository $cache,
        \Illuminate\Contracts\Config\Repository $config,
        \Psr\Log\LoggerInterface $logger,
        private readonly GyvatukasCalculator $gyvatukasCalculator,
    ) {
        parent::__construct($cache, $config, $logger);
    }

    public function getName(): string
    {
        return 'seasonal';
    }

    public function appliesTo(ValidationContext $context): bool
    {
        return $context->hasServiceConfiguration() && $context->getConsumption() !== null;
    }

    public function validate(ValidationContext $context): ValidationResult
    {
        try {
            $consumption = $context->getConsumption();
            $readingDate = $context->getReadingDate();
            $utilityType = $context->getUtilityType();
            
            if ($consumption === null || !$utilityType) {
                return ValidationResult::valid();
            }

            $warnings = [];
            $recommendations = [];
            
            // Determine seasonal context using gyvatukas logic
            $isHeatingSeason = $this->gyvatukasCalculator->isHeatingSeason($readingDate);
            $isSummerPeriod = $this->gyvatukasCalculator->isSummerPeriod($readingDate);

            // Get seasonal configuration
            $seasonalConfig = $this->getSeasonalConfig($utilityType);
            
            // Apply seasonal validation based on utility type
            if ($utilityType === 'heating') {
                $heatingValidation = $this->validateHeatingConsumption(
                    $consumption, $isSummerPeriod, $seasonalConfig, $context
                );
                $warnings = array_merge($warnings, $heatingValidation['warnings']);
            }
            
            if ($utilityType === 'water') {
                $waterValidation = $this->validateWaterConsumption(
                    $consumption, $isSummerPeriod, $seasonalConfig, $context
                );
                $recommendations = array_merge($recommendations, $waterValidation['recommendations']);
            }

            $metadata = [
                'rules_applied' => ['seasonal_adjustments'],
                'seasonal_context' => [
                    'is_heating_season' => $isHeatingSeason,
                    'is_summer_period' => $isSummerPeriod,
                    'utility_type' => $utilityType,
                    'applied_thresholds' => $seasonalConfig,
                ],
            ];

            return ValidationResult::valid($warnings, $recommendations, $metadata);

        } catch (\Exception $e) {
            return $this->handleException($e, $context);
        }
    }

    private function getSeasonalConfig(string $utilityType): array
    {
        $cacheKey = $this->buildCacheKey('seasonal_config', $utilityType);
        
        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($utilityType) {
                $allConfig = $this->getConfigValue('service_validation.seasonal_adjustments', []);
                return $allConfig[$utilityType] ?? $allConfig['default'] ?? [];
            }
        );
    }

    private function validateHeatingConsumption(
        float $consumption,
        bool $isSummerPeriod,
        array $config,
        ValidationContext $context
    ): array {
        $warnings = [];

        if ($isSummerPeriod && isset($config['summer_max_threshold'])) {
            if ($consumption > $config['summer_max_threshold']) {
                $warnings[] = "High heating consumption during summer period: {$consumption} {$context->getUnit()}";
            }
        }

        if (!$isSummerPeriod && isset($config['winter_min_threshold'])) {
            if ($consumption < $config['winter_min_threshold']) {
                $warnings[] = "Low heating consumption during heating season: {$consumption} {$context->getUnit()}";
            }
        }

        return ['warnings' => $warnings];
    }

    private function validateWaterConsumption(
        float $consumption,
        bool $isSummerPeriod,
        array $config,
        ValidationContext $context
    ): array {
        $recommendations = [];

        $rangeKey = $isSummerPeriod ? 'summer_range' : 'winter_range';
        $expectedRange = $config[$rangeKey] ?? null;

        if ($expectedRange) {
            if ($consumption < $expectedRange['min'] || $consumption > $expectedRange['max']) {
                $season = $isSummerPeriod ? 'summer' : 'winter';
                $recommendations[] = "Water consumption outside typical {$season} range: {$consumption} {$context->getUnit()}";
            }
        }

        return ['recommendations' => $recommendations];
    }
}