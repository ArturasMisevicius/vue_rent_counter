<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;
use App\Services\MeterReadingService;

/**
 * Validates business rules specific to the service configuration.
 */
final class BusinessRuleValidator extends AbstractValidator
{
    public function __construct(
        \Illuminate\Contracts\Cache\Repository $cache,
        \Illuminate\Contracts\Config\Repository $config,
        \Psr\Log\LoggerInterface $logger,
        private readonly MeterReadingService $meterReadingService,
    ) {
        parent::__construct($cache, $config, $logger);
    }

    public function getName(): string
    {
        return 'business_rules';
    }

    public function appliesTo(ValidationContext $context): bool
    {
        return $context->hasServiceConfiguration();
    }

    public function validate(ValidationContext $context): ValidationResult
    {
        try {
            $errors = [];
            $warnings = [];
            $recommendations = [];

            $utilityService = $context->serviceConfiguration->utilityService;
            $businessRules = $utilityService->business_logic_config ?? [];

            // 1. Reading frequency validation
            if (isset($businessRules['reading_frequency'])) {
                $frequencyValidation = $this->validateReadingFrequency($context, $businessRules['reading_frequency']);
                $warnings = array_merge($warnings, $frequencyValidation['warnings']);
            }

            // 2. Service-specific constraints
            if (isset($businessRules['constraints'])) {
                $constraintValidation = $this->validateServiceConstraints($context, $businessRules['constraints']);
                $errors = array_merge($errors, $constraintValidation['errors']);
                $warnings = array_merge($warnings, $constraintValidation['warnings']);
            }

            // 3. Configuration validation
            $configValidation = $context->serviceConfiguration->validateConfiguration();
            if (!empty($configValidation)) {
                $errors = array_merge($errors, $configValidation);
            }

            $metadata = [
                'rules_applied' => ['reading_frequency', 'service_constraints', 'configuration_validation'],
                'business_rules_count' => count($businessRules),
            ];

            if (empty($errors)) {
                return ValidationResult::valid($warnings, $recommendations, $metadata);
            }

            return ValidationResult::invalid($errors, $warnings, $recommendations, $metadata);

        } catch (\Exception $e) {
            return $this->handleException($e, $context);
        }
    }

    private function validateReadingFrequency(ValidationContext $context, array $frequencyRules): array
    {
        $warnings = [];

        $requiredFrequency = $frequencyRules['required_days'] ?? 30;
        
        if ($context->hasPreviousReading()) {
            $daysSinceLastReading = $context->reading->reading_date->diffInDays($context->previousReading->reading_date);
            
            if ($daysSinceLastReading > $requiredFrequency) {
                $warnings[] = "Reading frequency exceeds recommended interval: {$daysSinceLastReading} days since last reading";
            }
        }

        return ['warnings' => $warnings];
    }

    private function validateServiceConstraints(ValidationContext $context, array $constraints): array
    {
        $errors = [];
        $warnings = [];

        foreach ($constraints as $constraint) {
            $constraintResult = $this->evaluateConstraint($context, $constraint);
            
            if ($constraintResult['violated']) {
                $severity = $constraint['severity'] ?? 'warning';
                
                if ($severity === 'error') {
                    $errors[] = $constraintResult['message'];
                } else {
                    $warnings[] = $constraintResult['message'];
                }
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function evaluateConstraint(ValidationContext $context, array $constraint): array
    {
        $field = $constraint['field'] ?? 'value';
        $operator = $constraint['operator'] ?? '>';
        $value = $constraint['value'] ?? 0;
        $message = $constraint['message'] ?? "Constraint violation on field {$field}";

        $readingValue = $context->reading->{$field} ?? $context->reading->getEffectiveValue();
        
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
}