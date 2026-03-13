<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization;

use App\Data\TenantInitialization\InitializationResult;
use App\Data\TenantInitialization\PropertyServiceAssignmentResult;
use App\Models\Organization;
use App\Services\TenantInitialization\Commands\InitializeUniversalServicesCommand;
use App\Services\TenantInitialization\Validators\TenantInitializationValidator;
use App\Services\TenantInitialization\Repositories\UtilityServiceRepositoryInterface;
use App\Exceptions\TenantInitializationException;
use App\Enums\ServiceType;
use App\Enums\PricingModel;
use App\Traits\LogsTenantOperations;
use Illuminate\Support\Collection;

/**
 * Orchestrator for tenant initialization operations.
 * 
 * Coordinates the initialization process using various commands and services
 * while maintaining single responsibility and proper separation of concerns.
 * 
 * @package App\Services\TenantInitialization
 * @author Laravel Development Team
 * @since 2.0.0
 */
final readonly class TenantInitializationOrchestrator
{
    use LogsTenantOperations;

    private const OPERATION_PROPERTY_ASSIGNMENT = 'property_service_assignment';
    private const OPERATION_HEATING_COMPATIBILITY = 'heating_compatibility_check';

    public function __construct(
        private TenantInitializationValidator $validator,
        private InitializeUniversalServicesCommand $initializeServicesCommand,
        private PropertyServiceAssigner $propertyServiceAssigner,
        private UtilityServiceRepositoryInterface $utilityServiceRepository,
    ) {}

    /**
     * Initialize universal services for a tenant.
     * 
     * @param Organization $tenant The tenant to initialize
     * 
     * @return InitializationResult The initialization result
     * 
     * @throws TenantInitializationException If initialization fails
     */
    public function initializeUniversalServices(Organization $tenant): InitializationResult
    {
        $this->validator->validateForInitialization($tenant);
        
        return $this->initializeServicesCommand->execute($tenant);
    }

    /**
     * Initialize property service assignments.
     * 
     * @param Organization $tenant The tenant
     * @param Collection|array $utilityServices The utility services to assign
     * 
     * @return PropertyServiceAssignmentResult The assignment result
     * 
     * @throws TenantInitializationException If assignment fails
     */
    public function initializePropertyServiceAssignments(
        Organization $tenant,
        Collection|array $utilityServices
    ): PropertyServiceAssignmentResult {
        $this->validator->validateForPropertyAssignment($tenant);

        $properties = $tenant->properties()->get();

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
     * Ensure heating compatibility for a tenant.
     * 
     * @param Organization $tenant The tenant to check
     * 
     * @return bool True if compatible
     * 
     * @throws TenantInitializationException If compatibility check fails
     */
    public function ensureHeatingCompatibility(Organization $tenant): bool
    {
        $this->validator->validateForCompatibilityCheck($tenant);

        try {
            $this->logTenantOperationStart($tenant, self::OPERATION_HEATING_COMPATIBILITY);

            $heatingService = $this->utilityServiceRepository->findHeatingService($tenant);

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
     * Validate heating service configuration for compatibility.
     * 
     * @param \App\Models\UtilityService $heatingService The heating service
     * 
     * @return bool True if configuration is valid
     */
    private function validateHeatingServiceConfiguration($heatingService): bool
    {
        return $heatingService->service_type_bridge === ServiceType::HEATING
            && $heatingService->default_pricing_model === PricingModel::HYBRID
            && !empty($heatingService->business_logic_config['supports_shared_distribution']);
    }
}