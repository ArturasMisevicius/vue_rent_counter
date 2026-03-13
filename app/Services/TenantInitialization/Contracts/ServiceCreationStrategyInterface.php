<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization\Contracts;

use App\Models\Organization;
use App\Models\UtilityService;

/**
 * Strategy interface for creating utility services.
 * 
 * Implements Strategy pattern to allow different service creation approaches
 * (template-based, default creation, custom configurations).
 */
interface ServiceCreationStrategyInterface
{
    /**
     * Create a utility service for the given tenant.
     * 
     * @param Organization $tenant The tenant to create service for
     * @param string $serviceKey The service type key (electricity, water, etc.)
     * @param array<string, mixed> $definition Service definition configuration
     * 
     * @return UtilityService The created utility service
     * 
     * @throws \App\Exceptions\TenantInitializationException
     */
    public function createService(
        Organization $tenant, 
        string $serviceKey, 
        array $definition
    ): UtilityService;

    /**
     * Check if this strategy can handle the given service definition.
     * 
     * @param array<string, mixed> $definition Service definition
     * 
     * @return bool True if strategy can handle this definition
     */
    public function canHandle(array $definition): bool;
}