<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Services\Validation\Validators\ConsumptionValidator;
use App\Services\Validation\Validators\SeasonalValidator;
use App\Services\Validation\Validators\DataQualityValidator;
use App\Services\Validation\Validators\BusinessRulesValidator;
use App\Services\Validation\Validators\InputMethodValidator;
use App\Services\Validation\Validators\RateChangeValidator;
use App\Services\Validation\Contracts\ValidatorInterface;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Factory for creating validation rule instances using the Strategy pattern.
 * 
 * Provides centralized validator creation and dependency injection,
 * enabling runtime selection of validation algorithms based on context.
 */
final class ValidationRuleFactory
{
    /**
     * Registry of available validators.
     */
    private array $validators = [
        'consumption' => ConsumptionValidator::class,
        'seasonal' => SeasonalValidator::class,
        'data_quality' => DataQualityValidator::class,
        'business_rules' => BusinessRulesValidator::class,
        'input_method' => InputMethodValidator::class,
        'rate_change' => RateChangeValidator::class,
    ];

    /**
     * Cache of instantiated validators.
     */
    private array $instances = [];

    public function __construct(
        private readonly Container $container
    ) {}

    /**
     * Get a specific validator by name.
     * 
     * @throws InvalidArgumentException When validator is not found
     */
    public function getValidator(string $name): ValidatorInterface
    {
        if (!isset($this->validators[$name])) {
            throw new InvalidArgumentException("Validator '{$name}' not found");
        }

        // Use cached instance if available
        if (!isset($this->instances[$name])) {
            $this->instances[$name] = $this->container->make($this->validators[$name]);
        }

        return $this->instances[$name];
    }

    /**
     * Get all applicable validators for a validation context.
     * 
     * @return array<ValidatorInterface>
     */
    public function getValidatorsForContext(ValidationContext $context): array
    {
        $validators = [];

        // Always include consumption validation if we have consumption data
        if ($context->getConsumption() !== null) {
            $validators[] = $this->getValidator('consumption');
        }

        // Include seasonal validation for utility services that support it
        $utilityService = $context->getUtilityService();
        if ($utilityService && $this->supportsSeasonal($utilityService)) {
            $validators[] = $this->getValidator('seasonal');
        }

        // Always include data quality validation
        $validators[] = $this->getValidator('data_quality');

        // Include business rules validation if service configuration exists
        if ($context->serviceConfiguration) {
            $validators[] = $this->getValidator('business_rules');
        }

        // Include input method validation based on reading input method
        if ($context->reading->input_method) {
            $validators[] = $this->getValidator('input_method');
        }

        return $validators;
    }

    /**
     * Get all available validator names.
     * 
     * @return array<string>
     */
    public function getAvailableValidators(): array
    {
        return array_keys($this->validators);
    }

    /**
     * Register a custom validator.
     */
    public function registerValidator(string $name, string $className): void
    {
        if (!is_subclass_of($className, ValidatorInterface::class)) {
            throw new InvalidArgumentException(
                "Validator class must implement " . ValidatorInterface::class
            );
        }

        $this->validators[$name] = $className;
        
        // Clear cached instance if it exists
        unset($this->instances[$name]);
    }

    /**
     * Check if a validator is registered.
     */
    public function hasValidator(string $name): bool
    {
        return isset($this->validators[$name]);
    }

    /**
     * Create a validator chain for sequential validation.
     * 
     * @param array<string> $validatorNames
     * @return ValidatorChain
     */
    public function createChain(array $validatorNames): ValidatorChain
    {
        $validators = array_map(
            fn(string $name) => $this->getValidator($name),
            $validatorNames
        );

        return new ValidatorChain($validators);
    }

    /**
     * Check if a utility service supports seasonal validation.
     */
    private function supportsSeasonal(\App\Models\UtilityService $utilityService): bool
    {
        // Services that typically have seasonal patterns
        $seasonalServices = ['heating', 'electricity', 'water'];
        
        $serviceType = $utilityService->service_type_bridge?->value;
        return in_array($serviceType, $seasonalServices, true);
    }

    /**
     * Clear all cached validator instances.
     */
    public function clearCache(): void
    {
        $this->instances = [];
    }

    /**
     * Get validator statistics for monitoring.
     */
    public function getStatistics(): array
    {
        return [
            'registered_validators' => count($this->validators),
            'cached_instances' => count($this->instances),
            'available_validators' => $this->getAvailableValidators(),
        ];
    }
}