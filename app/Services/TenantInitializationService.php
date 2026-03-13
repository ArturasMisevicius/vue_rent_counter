<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TenantInitialization\InitializationResult;
use App\Data\TenantInitialization\PropertyServiceAssignmentResult;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Exceptions\TenantInitializationException;
use App\Models\Organization;
use App\Models\Property;
use App\Models\UtilityService;
use App\Services\TenantInitialization\ServiceDefinitionProvider;
use App\Services\TenantInitialization\MeterConfigurationProvider;
use App\Services\TenantInitialization\PropertyServiceAssigner;
use App\Services\TenantInitialization\TenantValidator;
use App\Services\TenantInitialization\SlugGeneratorService;
use App\Traits\LogsTenantOperations;
use Illuminate\Support\Facades\DB;

/**
 * Service for initializing new tenants with default utility service templates
 * and configurations. Extends existing tenant creation to include universal
 * service setup alongside existing heating initialization.
 * 
 * This service creates the foundational utility services (electricity, water, heating, gas)
 * for new tenants and handles property-level service assignments. It maintains backward
 * compatibility with existing heating systems while enabling the universal utility
 * management framework.
 * 
 * @package App\Services
 * @author Laravel Development Team
 * @since 1.0.0
 * 
 * @see \App\Models\UtilityService
 * @see \App\Models\ServiceConfiguration
 * @see \App\Models\Organization
 * @see \App\Models\Property
 */
final readonly class TenantInitializationService
{
    use LogsTenantOperations;

    private const OPERATION_UNIVERSAL_SERVICES = 'universal_services_initialization';
    private const OPERATION_PROPERTY_ASSIGNMENT = 'property_service_assignment';
    private const OPERATION_HEATING_COMPATIBILITY = 'heating_compatibility_check';

    public function __construct(
        private ServiceDefinitionProvider $serviceDefinitionProvider,
        private MeterConfigurationProvider $meterConfigurationProvider,
        private PropertyServiceAssigner $propertyServiceAssigner,
        private TenantValidator $tenantValidator,
        private SlugGeneratorService $slugGenerator,
    ) {}

    /**
     * Initialize a new tenant with default utility service templates.
     * Creates tenant-specific copies of global templates and sets up
     * default configurations based on tenant type.
     * 
     * This method creates four default utility services (electricity, water, heating, gas)
     * for the tenant with appropriate pricing models, validation rules, and business logic
     * configurations. Each service is created with tenant-specific settings while
     * maintaining compatibility with existing heating system integrations.
     * 
     * @param Organization $tenant The organization/tenant to initialize services for
     * 
     * @return InitializationResult Returns DTO with created services and meter configurations
     * 
     * @throws TenantInitializationException If service initialization fails
     * 
     * @example
     * ```php
     * $service = app(TenantInitializationService::class);
     * $result = $service->initializeUniversalServices($tenant);
     * 
     * // Access created services
     * $electricityService = $result->getUtilityService('electricity');
     * $heatingConfig = $result->getMeterConfiguration('heating');
     * ```
     * 
     * @since 1.0.0
     */
    public function initializeUniversalServices(Organization $tenant): InitializationResult
    {
        $this->tenantValidator->validate($tenant);

        return DB::transaction(function () use ($tenant) {
            try {
                $this->logTenantOperationStart($tenant, self::OPERATION_UNIVERSAL_SERVICES);

                $utilityServices = $this->createDefaultUtilityServices($tenant);
                $meterConfigurations = $this->createMeterConfigurations($utilityServices);

                $result = new InitializationResult(
                    utilityServices: $utilityServices,
                    meterConfigurations: collect($meterConfigurations),
                );

                $this->logTenantOperationSuccess($tenant, self::OPERATION_UNIVERSAL_SERVICES, [
                    'services_created' => $result->getServiceCount(),
                    'meter_configs_created' => $result->getMeterConfigurationCount(),
                ]);

                return $result;
            } catch (\Exception $e) {
                $this->logTenantOperationError($tenant, self::OPERATION_UNIVERSAL_SERVICES, $e);
                throw TenantInitializationException::serviceCreationFailed($tenant, 'universal', $e);
            }
        });
    }

    /**
     * Initialize property-level service assignments for existing properties.
     * Creates ServiceConfiguration records for properties that already exist.
     * 
     * This method assigns all utility services to each property owned by the tenant,
     * creating ServiceConfiguration records that link properties to utility services
     * with appropriate pricing models and configuration overrides based on property type.
     * 
     * @param Organization $tenant The tenant whose properties to configure
     * @param \Illuminate\Support\Collection<string, UtilityService>|array<string, UtilityService> $utilityServices Collection or array of utility services to assign
     * 
     * @return PropertyServiceAssignmentResult Service configurations grouped by property ID
     * 
     * @throws TenantInitializationException If configuration creation fails
     * 
     * @since 1.0.0
     */
    public function initializePropertyServiceAssignments(
        Organization $tenant, 
        \Illuminate\Support\Collection|array $utilityServices
    ): PropertyServiceAssignmentResult {
        $this->tenantValidator->validate($tenant);

        $properties = Property::where('tenant_id', $tenant->id)->get();

        if ($properties->isEmpty()) {
            $this->logTenantOperationInfo(
                $tenant, 
                self::OPERATION_PROPERTY_ASSIGNMENT, 
                'No existing properties found, skipping property service assignments'
            );
            return new PropertyServiceAssignmentResult(collect());
        }

        try {
            $this->logTenantOperationStart($tenant, self::OPERATION_PROPERTY_ASSIGNMENT, [
                'properties_count' => $properties->count(),
                'services_count' => is_array($utilityServices) ? count($utilityServices) : $utilityServices->count(),
            ]);

            $servicesArray = is_array($utilityServices) ? $utilityServices : $utilityServices->toArray();
            
            $configurations = $this->propertyServiceAssigner->assignServicesToProperties(
                $tenant,
                $properties,
                $servicesArray
            );

            $result = PropertyServiceAssignmentResult::fromArray($configurations);

            $this->logTenantOperationSuccess($tenant, self::OPERATION_PROPERTY_ASSIGNMENT, [
                'properties_configured' => $result->getPropertyCount(),
                'total_configurations' => $result->getTotalConfigurationCount(),
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logTenantOperationError($tenant, self::OPERATION_PROPERTY_ASSIGNMENT, $e);
            throw TenantInitializationException::propertyAssignmentFailed($tenant, $e);
        }
    }

    /**
     * Ensure backward compatibility with existing heating initialization.
     * This method can be called to verify that existing heating setup
     * is preserved and enhanced with universal service capabilities.
     * 
     * Validates that the heating service exists and is properly configured
     * for integration with the existing heating calculator system. This ensures
     * that universal service initialization doesn't break existing heating
     * functionality.
     * 
     * @param Organization $tenant The tenant to check heating compatibility for
     * 
     * @return bool True if heating service is compatible, false otherwise
     * 
     * @throws TenantInitializationException If compatibility check fails
     * 
     * @since 1.0.0
     */
    public function ensureHeatingCompatibility(Organization $tenant): bool
    {
        $this->tenantValidator->validate($tenant);

        try {
            $this->logTenantOperationStart($tenant, self::OPERATION_HEATING_COMPATIBILITY);

            $heatingService = $this->findHeatingService($tenant);

            if (!$heatingService) {
                $this->logTenantOperationWarning(
                    $tenant, 
                    self::OPERATION_HEATING_COMPATIBILITY, 
                    'No heating service found for tenant during compatibility check'
                );
                return false;
            }

            $isCompatible = $this->validateHeatingServiceConfiguration($heatingService);

            $this->logTenantOperationSuccess($tenant, self::OPERATION_HEATING_COMPATIBILITY, [
                'heating_service_id' => $heatingService->id,
                'is_compatible' => $isCompatible,
            ]);

            return $isCompatible;
        } catch (\Exception $e) {
            $this->logTenantOperationError($tenant, self::OPERATION_HEATING_COMPATIBILITY, $e);
            throw TenantInitializationException::heatingCompatibilityFailed($tenant, $e);
        }
    }

    /**
     * Create default utility service templates for a new tenant.
     * Includes electricity, water, heating, and gas services with
     * standard pricing models and regional defaults.
     * 
     * @param Organization $tenant The tenant to create services for
     * 
     * @return \Illuminate\Support\Collection<string, UtilityService> Collection of created utility services keyed by service type
     * 
     * @throws TenantInitializationException If service creation fails
     * 
     * @since 1.0.0
     */
    private function createDefaultUtilityServices(Organization $tenant): \Illuminate\Support\Collection
    {
        $services = collect();
        $serviceDefinitions = $this->serviceDefinitionProvider->getDefaultServiceDefinitions();

        foreach ($serviceDefinitions as $key => $definition) {
            try {
                $services[$key] = $this->createUtilityService($tenant, $key, $definition);
            } catch (\Exception $e) {
                throw TenantInitializationException::serviceCreationFailed($tenant, $key, $e);
            }
        }

        return $services;
    }

    /**
     * Create a single utility service for the tenant.
     * 
     * @param Organization $tenant The tenant to create the service for
     * @param string $serviceKey The service type key
     * @param array<string, mixed> $definition The service definition
     * 
     * @return UtilityService The created utility service
     */
    private function createUtilityService(Organization $tenant, string $serviceKey, array $definition): UtilityService
    {
        // Check if a global template exists for this service type
        $globalTemplate = UtilityService::where('is_global_template', true)
            ->where('service_type_bridge', $definition['service_type_bridge'])
            ->first();

        if ($globalTemplate) {
            return $globalTemplate->createTenantCopy($tenant->id, [
                'name' => $definition['name'],
                'description' => $definition['description'],
            ]);
        }

        // Create new service if no global template exists
        return UtilityService::create([
            'tenant_id' => $tenant->id,
            'name' => $definition['name'],
            'slug' => $this->slugGenerator->generateUniqueSlug($definition['name'], $tenant->id),
            'unit_of_measurement' => $definition['unit_of_measurement'],
            'default_pricing_model' => $definition['default_pricing_model'],
            'calculation_formula' => $definition['calculation_formula'],
            'is_global_template' => false,
            'created_by_tenant_id' => $tenant->id,
            'configuration_schema' => $definition['configuration_schema'],
            'validation_rules' => $definition['validation_rules'],
            'business_logic_config' => $definition['business_logic_config'],
            'service_type_bridge' => $definition['service_type_bridge'],
            'description' => $definition['description'],
            'is_active' => true,
        ]);
    }

    /**
     * Create meter configurations for utility services.
     * 
     * @param \Illuminate\Support\Collection<string, UtilityService> $utilityServices
     * 
     * @return array<string, array<string, mixed>>
     */
    private function createMeterConfigurations(\Illuminate\Support\Collection $utilityServices): array
    {
        return $this->meterConfigurationProvider
            ->createDefaultMeterConfigurations($utilityServices->toArray());
    }

    /**
     * Find heating service for a tenant.
     */
    private function findHeatingService(Organization $tenant): ?UtilityService
    {
        return UtilityService::where('tenant_id', $tenant->id)
            ->where('service_type_bridge', ServiceType::HEATING)
            ->first();
    }

    /**
     * Validate heating service configuration for compatibility.
     * 
     * @param UtilityService $heatingService The heating service to validate
     * 
     * @return bool True if configuration is valid
     */
    private function validateHeatingServiceConfiguration(UtilityService $heatingService): bool
    {
        return $heatingService->service_type_bridge === ServiceType::HEATING
            && $heatingService->default_pricing_model === PricingModel::HYBRID
            && !empty($heatingService->business_logic_config['supports_shared_distribution']);
    }
}