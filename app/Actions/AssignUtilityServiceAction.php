<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\AssignUtilityServiceDTO;
use App\Exceptions\ServiceConfigurationException;
use App\Models\Meter;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Traits\Auditable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Assign Utility Service Action
 * 
 * Single responsibility: Assign utility services to properties with individual configurations.
 * Creates ServiceConfiguration records linking properties to utility services.
 * Supports pricing overrides with full audit trail.
 * Validates configurations don't conflict with existing meter assignments.
 * 
 * Requirements: 3.1, 3.2, 3.3
 * 
 * @package App\Actions
 */
final class AssignUtilityServiceAction
{
    /**
     * Execute the action to assign a utility service to a property.
     *
     * @param AssignUtilityServiceDTO $data Service assignment data
     * @return ServiceConfiguration The created service configuration
     * @throws ServiceConfigurationException If validation fails
     * @throws ValidationException If input validation fails
     */
    public function execute(AssignUtilityServiceDTO $data): ServiceConfiguration
    {
        return DB::transaction(function () use ($data) {
            // Validate input data
            $this->validateInput($data);

            // Load related models
            $property = Property::findOrFail($data->propertyId);
            $utilityService = UtilityService::findOrFail($data->utilityServiceId);

            // Validate property and service compatibility
            $this->validatePropertyServiceCompatibility($property, $utilityService, $data);

            // Check for conflicting configurations
            $this->validateNoConflictingConfigurations($property, $data);

            // Validate meter assignments
            $this->validateMeterAssignments($property, $data);

            // Create service configuration
            $configuration = $this->createServiceConfiguration($property, $utilityService, $data);

            return $configuration;
        });
    }

    /**
     * Validate input data.
     *
     * @param AssignUtilityServiceDTO $data
     * @throws ValidationException
     */
    private function validateInput(AssignUtilityServiceDTO $data): void
    {
        $validator = Validator::make($data->toArray(), [
            'property_id' => 'required|exists:properties,id',
            'utility_service_id' => 'required|exists:utility_services,id',
            'pricing_model' => 'required',
            'rate_schedule' => 'nullable|array',
            'distribution_method' => 'nullable',
            'is_shared_service' => 'boolean',
            'effective_from' => 'required|date',
            'effective_until' => 'nullable|date|after:effective_from',
            'configuration_overrides' => 'nullable|array',
            'tariff_id' => 'nullable|exists:tariffs,id',
            'provider_id' => 'nullable|exists:providers,id',
            'area_type' => 'nullable|string|in:total_area,heated_area,commercial_area',
            'custom_formula' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate property and service compatibility.
     *
     * @param Property $property
     * @param UtilityService $utilityService
     * @param AssignUtilityServiceDTO $data
     * @throws ServiceConfigurationException
     */
    private function validatePropertyServiceCompatibility(
        Property $property,
        UtilityService $utilityService,
        AssignUtilityServiceDTO $data
    ): void {
        // Validate pricing model requirements
        if ($data->pricingModel->requiresConsumptionData()) {
            // Ensure rate schedule is provided for consumption-based pricing
            if (empty($data->rateSchedule)) {
                throw ServiceConfigurationException::missingRequiredConfiguration('rate_schedule');
            }
        }

        // Validate distribution method requirements
        if ($data->distributionMethod && $data->distributionMethod->requiresAreaData()) {
            // Ensure property has area data
            if (empty($property->area_sqm)) {
                throw ServiceConfigurationException::missingAreaData($property->id);
            }
        }

        // Validate custom formula if provided
        if ($data->pricingModel->supportsCustomFormulas() && !empty($data->customFormula)) {
            $this->validateCustomFormula($data->customFormula);
        }

        // Validate configuration overrides against utility service schema (if provided)
        // Note: Core fields like rate_schedule are validated separately, not in overrides
        if (!empty($data->configurationOverrides)) {
            $configErrors = $utilityService->validateConfiguration(
                $data->configurationOverrides
            );

            if (!empty($configErrors)) {
                throw ServiceConfigurationException::validationErrors($configErrors);
            }
        }
    }

    /**
     * Validate no conflicting configurations exist.
     *
     * @param Property $property
     * @param AssignUtilityServiceDTO $data
     * @throws ServiceConfigurationException
     */
    private function validateNoConflictingConfigurations(
        Property $property,
        AssignUtilityServiceDTO $data
    ): void {
        // Check for overlapping active configurations for the same utility service
        $overlapping = ServiceConfiguration::where('property_id', $property->id)
            ->where('utility_service_id', $data->utilityServiceId)
            ->where('is_active', true)
            ->where(function ($query) use ($data) {
                $query->where(function ($q) use ($data) {
                    // New config starts during existing config
                    $q->where('effective_from', '<=', $data->effectiveFrom)
                        ->where(function ($q2) use ($data) {
                            $q2->whereNull('effective_until')
                                ->orWhere('effective_until', '>=', $data->effectiveFrom);
                        });
                })->orWhere(function ($q) use ($data) {
                    // New config ends during existing config
                    if ($data->effectiveUntil) {
                        $q->where('effective_from', '<=', $data->effectiveUntil)
                            ->where(function ($q2) use ($data) {
                                $q2->whereNull('effective_until')
                                    ->orWhere('effective_until', '>=', $data->effectiveUntil);
                            });
                    }
                });
            })
            ->exists();

        if ($overlapping) {
            $dateRange = $data->effectiveFrom->format('Y-m-d') . 
                ($data->effectiveUntil ? ' to ' . $data->effectiveUntil->format('Y-m-d') : ' onwards');
            throw ServiceConfigurationException::overlappingConfiguration($property->id, $dateRange);
        }
    }

    /**
     * Validate meter assignments don't conflict.
     *
     * @param Property $property
     * @param AssignUtilityServiceDTO $data
     * @throws ServiceConfigurationException
     */
    private function validateMeterAssignments(
        Property $property,
        AssignUtilityServiceDTO $data
    ): void {
        // Check if property has meters assigned to different service configurations
        // for the same utility service
        $conflictingMeters = Meter::where('property_id', $property->id)
            ->whereNotNull('service_configuration_id')
            ->whereHas('serviceConfiguration', function ($query) use ($data) {
                $query->where('utility_service_id', $data->utilityServiceId)
                    ->where('is_active', true);
            })
            ->get();

        if ($conflictingMeters->isNotEmpty()) {
            $meter = $conflictingMeters->first();
            throw ServiceConfigurationException::conflictingMeterAssignment(
                $meter->id,
                $property->id
            );
        }
    }

    /**
     * Validate custom formula syntax.
     *
     * @param string $formula
     * @throws ServiceConfigurationException
     */
    private function validateCustomFormula(string $formula): void
    {
        // Basic validation - check for dangerous functions
        $dangerousFunctions = ['eval', 'exec', 'system', 'shell_exec', 'passthru'];
        
        foreach ($dangerousFunctions as $func) {
            if (stripos($formula, $func) !== false) {
                throw ServiceConfigurationException::invalidPricingModel(
                    'CUSTOM_FORMULA',
                    "Formula contains dangerous function: {$func}"
                );
            }
        }

        // Validate formula has basic mathematical structure
        if (!preg_match('/^[\d\s\+\-\*\/\(\)\.\w]+$/', $formula)) {
            throw ServiceConfigurationException::invalidPricingModel(
                'CUSTOM_FORMULA',
                'Formula contains invalid characters'
            );
        }
    }

    /**
     * Create service configuration with audit trail.
     *
     * @param Property $property
     * @param UtilityService $utilityService
     * @param AssignUtilityServiceDTO $data
     * @return ServiceConfiguration
     */
    private function createServiceConfiguration(
        Property $property,
        UtilityService $utilityService,
        AssignUtilityServiceDTO $data
    ): ServiceConfiguration {
        // Prepare configuration data
        $configData = $data->toArray();
        $configData['tenant_id'] = $property->tenant_id;

        // Create configuration
        $configuration = ServiceConfiguration::create($configData);

        // Audit trail is automatically created by Auditable trait if applied
        // Additional logging for service assignment
        activity()
            ->performedOn($configuration)
            ->causedBy(auth()->user())
            ->withProperties([
                'property_id' => $property->id,
                'property_address' => $property->address,
                'utility_service_id' => $utilityService->id,
                'utility_service_name' => $utilityService->name,
                'pricing_model' => $data->pricingModel->value,
                'is_shared_service' => $data->isSharedService,
                'effective_from' => $data->effectiveFrom->toDateString(),
                'effective_until' => $data->effectiveUntil?->toDateString(),
            ])
            ->log('utility_service_assigned');

        return $configuration->fresh();
    }
}
