<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization\Factories;

use App\Models\Organization;
use App\Models\UtilityService;
use App\Services\TenantInitialization\Contracts\ServiceCreationStrategyInterface;
use App\Exceptions\TenantInitializationException;
use Illuminate\Support\Collection;

/**
 * Factory for creating utility services using appropriate strategies.
 * 
 * Implements Factory pattern to delegate service creation to the most
 * appropriate strategy based on the service definition.
 */
final readonly class UtilityServiceFactory
{
    /**
     * @param Collection<int, ServiceCreationStrategyInterface> $strategies
     */
    public function __construct(
        private Collection $strategies,
    ) {}

    /**
     * Create a utility service using the appropriate strategy.
     * 
     * @param Organization $tenant The tenant to create service for
     * @param string $serviceKey The service type key
     * @param array<string, mixed> $definition Service definition
     * 
     * @return UtilityService The created utility service
     * 
     * @throws TenantInitializationException If no strategy can handle the definition
     */
    public function createService(
        Organization $tenant, 
        string $serviceKey, 
        array $definition
    ): UtilityService {
        $strategy = $this->findStrategy($definition);
        
        if (!$strategy) {
            throw TenantInitializationException::serviceCreationFailed(
                $tenant, 
                $serviceKey, 
                new \InvalidArgumentException('No strategy found for service definition')
            );
        }

        return $strategy->createService($tenant, $serviceKey, $definition);
    }

    /**
     * Create multiple utility services in batch.
     * 
     * @param Organization $tenant The tenant to create services for
     * @param array<string, array<string, mixed>> $definitions Service definitions keyed by service key
     * 
     * @return Collection<string, UtilityService> Created services keyed by service key
     */
    public function createBatch(Organization $tenant, array $definitions): Collection
    {
        $services = collect();

        foreach ($definitions as $serviceKey => $definition) {
            $services[$serviceKey] = $this->createService($tenant, $serviceKey, $definition);
        }

        return $services;
    }

    /**
     * Find the appropriate strategy for the given definition.
     * 
     * @param array<string, mixed> $definition Service definition
     * 
     * @return ServiceCreationStrategyInterface|null The strategy or null if none found
     */
    private function findStrategy(array $definition): ?ServiceCreationStrategyInterface
    {
        return $this->strategies->first(
            fn(ServiceCreationStrategyInterface $strategy) => $strategy->canHandle($definition)
        );
    }
}