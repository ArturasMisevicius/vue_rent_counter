<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Services\Validation\Contracts\ValidatorInterface;
use App\Services\Validation\Validators\ConsumptionValidator;
use App\Services\Validation\Validators\SeasonalValidator;
use App\Services\Validation\Validators\DataQualityValidator;
use App\Services\Validation\Validators\BusinessRuleValidator;
use App\Services\Validation\Validators\InputMethodValidator;
use App\Services\Validation\Validators\RateChangeValidator;
use Illuminate\Contracts\Container\Container;

/**
 * Factory for creating validation rule instances.
 * 
 * Implements the Factory pattern for validator creation and management.
 */
final class ValidationRuleFactory
{
    private array $validators = [];

    public function __construct(
        private readonly Container $container
    ) {
        $this->registerDefaultValidators();
    }

    /**
     * Get all validators that apply to the given context.
     * 
     * @return ValidatorInterface[]
     */
    public function getValidatorsForContext(ValidationContext $context): array
    {
        return array_filter(
            $this->validators,
            fn(ValidatorInterface $validator) => $validator->appliesTo($context)
        );
    }

    /**
     * Get a specific validator by name.
     */
    public function getValidator(string $name): ?ValidatorInterface
    {
        return $this->validators[$name] ?? null;
    }

    /**
     * Register a validator.
     */
    public function registerValidator(string $name, ValidatorInterface $validator): void
    {
        $this->validators[$name] = $validator;
    }

    /**
     * Register a validator class that will be resolved from the container.
     */
    public function registerValidatorClass(string $name, string $validatorClass): void
    {
        $this->validators[$name] = $this->container->make($validatorClass);
    }

    /**
     * Get all registered validator names.
     */
    public function getValidatorNames(): array
    {
        return array_keys($this->validators);
    }

    /**
     * Register the default validators.
     */
    private function registerDefaultValidators(): void
    {
        $defaultValidators = [
            'consumption' => ConsumptionValidator::class,
            'seasonal' => SeasonalValidator::class,
            'data_quality' => DataQualityValidator::class,
            'business_rules' => BusinessRuleValidator::class,
            'input_method' => InputMethodValidator::class,
            'rate_change' => RateChangeValidator::class,
        ];

        foreach ($defaultValidators as $name => $class) {
            $this->registerValidatorClass($name, $class);
        }
    }
}