<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization\Commands;

use App\Data\TenantInitialization\InitializationResult;
use App\Models\Organization;
use App\Services\TenantInitialization\Factories\UtilityServiceFactory;
use App\Services\TenantInitialization\ServiceDefinitionProvider;
use App\Services\TenantInitialization\MeterConfigurationProvider;
use App\Traits\LogsTenantOperations;
use Illuminate\Support\Facades\DB;

/**
 * Command for initializing universal services for a tenant.
 * 
 * Implements Command pattern to encapsulate the initialization operation
 * with proper transaction handling and logging.
 */
final readonly class InitializeUniversalServicesCommand
{
    use LogsTenantOperations;

    private const OPERATION = 'universal_services_initialization';

    public function __construct(
        private UtilityServiceFactory $serviceFactory,
        private ServiceDefinitionProvider $serviceDefinitionProvider,
        private MeterConfigurationProvider $meterConfigurationProvider,
    ) {}

    /**
     * Execute the initialization command.
     * 
     * @param Organization $tenant The tenant to initialize services for
     * 
     * @return InitializationResult The initialization result
     * 
     * @throws \App\Exceptions\TenantInitializationException
     */
    public function execute(Organization $tenant): InitializationResult
    {
        return DB::transaction(function () use ($tenant) {
            $this->logTenantOperationStart($tenant, self::OPERATION);

            try {
                // Get service definitions
                $serviceDefinitions = $this->serviceDefinitionProvider->getDefaultServiceDefinitions();

                // Create utility services using factory
                $utilityServices = $this->serviceFactory->createBatch($tenant, $serviceDefinitions);

                // Create meter configurations
                $meterConfigurations = $this->meterConfigurationProvider
                    ->createDefaultMeterConfigurations($utilityServices->toArray());

                $result = new InitializationResult(
                    utilityServices: $utilityServices,
                    meterConfigurations: collect($meterConfigurations),
                );

                $this->logTenantOperationSuccess($tenant, self::OPERATION, [
                    'services_created' => $result->getServiceCount(),
                    'meter_configs_created' => $result->getMeterConfigurationCount(),
                ]);

                return $result;
            } catch (\Exception $e) {
                $this->logTenantOperationError($tenant, self::OPERATION, $e);
                throw $e;
            }
        });
    }
}