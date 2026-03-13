<?php

declare(strict_types=1);

namespace App\Handlers\TenantInitialization;

use App\Commands\TenantInitialization\InitializeUniversalServicesCommand;
use App\Data\TenantInitialization\InitializationResult;
use App\Events\TenantInitialization\TenantInitializationStarted;
use App\Events\TenantInitialization\ServicesInitialized;
use App\Events\TenantInitialization\InitializationFailed;
use App\Exceptions\TenantInitializationException;
use App\Services\TenantInitialization\ServiceDefinitionProvider;
use App\Services\TenantInitialization\MeterConfigurationProvider;
use App\Services\TenantInitialization\TenantValidationService;
use App\Services\TenantInitialization\ServiceConfigurationFactory;
use App\Services\TenantInitialization\CacheManager;
use App\Traits\LogsTenantOperations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Handles the initialization of universal services for tenants.
 * 
 * Implements the Command Handler pattern to process service initialization
 * commands with proper error handling, event dispatching, and transaction safety.
 */
final readonly class InitializeUniversalServicesHandler
{
    use LogsTenantOperations;

    private const OPERATION_NAME = 'universal_services_initialization';

    public function __construct(
        private ServiceDefinitionProvider $serviceDefinitionProvider,
        private MeterConfigurationProvider $meterConfigurationProvider,
        private TenantValidationService $tenantValidator,
        private ServiceConfigurationFactory $serviceFactory,
        private CacheManager $cacheManager,
    ) {}

    /**
     * Handle the initialization command.
     * 
     * @throws TenantInitializationException
     */
    public function handle(InitializeUniversalServicesCommand $command): InitializationResult
    {
        $tenant = $command->tenant;
        
        // Validate tenant before processing
        $this->tenantValidator->validateForInitialization($tenant);

        Event::dispatch(new TenantInitializationStarted($tenant, $command->serviceTypes));
        $this->logTenantOperationStart($tenant, self::OPERATION_NAME);

        return DB::transaction(function () use ($command) {
            try {
                $result = $this->processInitialization($command);
                
                Event::dispatch(new ServicesInitialized($command->tenant, $result));
                $this->logTenantOperationSuccess($command->tenant, self::OPERATION_NAME, [
                    'services_created' => $result->getServiceCount(),
                    'meter_configs_created' => $result->getMeterConfigurationCount(),
                ]);

                return $result;
            } catch (\Exception $e) {
                Event::dispatch(new InitializationFailed($command->tenant, $e));
                $this->logTenantOperationError($command->tenant, self::OPERATION_NAME, $e);
                
                throw TenantInitializationException::serviceCreationFailed(
                    $command->tenant, 
                    'universal', 
                    $e
                );
            }
        });
    }

    /**
     * Process the actual initialization logic.
     */
    private function processInitialization(InitializeUniversalServicesCommand $command): InitializationResult
    {
        $tenant = $command->tenant;
        $serviceDefinitions = $this->serviceDefinitionProvider->getDefaultServiceDefinitions();
        
        // Filter service definitions based on command
        $filteredDefinitions = array_filter(
            $serviceDefinitions,
            fn($key) => $command->shouldInitializeService($key),
            ARRAY_FILTER_USE_KEY
        );

        // Create utility services
        $utilityServices = $this->createUtilityServices($tenant, $filteredDefinitions);

        // Create meter configurations
        $meterConfigurations = $this->meterConfigurationProvider
            ->createDefaultMeterConfigurations($utilityServices->toArray());

        // Update cache
        $this->cacheManager->invalidateServiceCache($tenant);

        return new InitializationResult(
            utilityServices: $utilityServices,
            meterConfigurations: collect($meterConfigurations),
        );
    }

    /**
     * Create utility services using the factory.
     */
    private function createUtilityServices(
        \App\Models\Organization $tenant, 
        array $serviceDefinitions
    ): \Illuminate\Support\Collection {
        $services = collect();

        foreach ($serviceDefinitions as $key => $definition) {
            $services[$key] = $this->serviceFactory->createUtilityService(
                $tenant,
                $key,
                $definition
            );
        }

        return $services;
    }
}