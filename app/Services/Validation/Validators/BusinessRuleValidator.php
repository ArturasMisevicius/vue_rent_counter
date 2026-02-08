<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\Contracts\ValidatorInterface;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Validates business rules and constraints.
 */
final class BusinessRuleValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'business_rules';
    }

    public function isApplicable(ValidationContext $context): bool
    {
        return $context->serviceConfiguration !== null;
    }

    public function getPriority(): int
    {
        return 20; // Medium-low priority
    }

    public function validate(ValidationContext $context): ValidationResult
    {
        $result = ValidationResult::valid();

        $utilityService = $context->getUtilityService();
        $constraints = data_get($utilityService?->business_logic_config, 'constraints', []);

        if (is_array($constraints)) {
            foreach ($constraints as $constraint) {
                if (!is_array($constraint)) {
                    continue;
                }

                $field = $constraint['field'] ?? null;
                $operator = $constraint['operator'] ?? null;
                $expectedValue = $constraint['value'] ?? null;

                if (!is_string($field) || $field === '' || !is_string($operator) || $operator === '') {
                    continue;
                }

                $actualValue = $this->getFieldValue($context, $field);

                // Business rule constraints act as triggers:
                // when the condition matches, add the configured warning/error.
                if (!$this->matchesConstraint($actualValue, $operator, $expectedValue)) {
                    continue;
                }

                $message = $constraint['message'] ?? null;
                $severity = strtolower((string) ($constraint['severity'] ?? 'warning'));

                $message = is_string($message) && $message !== ''
                    ? $message
                    : __('validation_service.seasonal_variance_detected');

                if ($severity === 'error') {
                    $result = $result->addError($message);
                } else {
                    $result = $result->addWarning($message);
                }
            }
        }
        
        // Basic business rule validation (fallback)
        $consumption = $context->getConsumption();
        if ($consumption && $consumption > 1000) {
            $result = $result->addWarning('High consumption may require review');
        }

        return $result;
    }

    private function getFieldValue(ValidationContext $context, string $field): mixed
    {
        return match ($field) {
            'value' => $context->reading->getEffectiveValue(),
            'consumption' => $context->getConsumption(),
            default => data_get($context->reading, $field),
        };
    }

    private function matchesConstraint(mixed $actual, string $operator, mixed $expected): bool
    {
        if (is_numeric($actual) && is_numeric($expected)) {
            $actual = (float) $actual;
            $expected = (float) $expected;
        }

        return match ($operator) {
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            '=', '==' => $actual == $expected,
            '!=', '<>' => $actual != $expected,
            'in' => is_array($expected) && in_array($actual, $expected, true),
            'not_in' => is_array($expected) && !in_array($actual, $expected, true),
            default => false,
        };
    }
}
