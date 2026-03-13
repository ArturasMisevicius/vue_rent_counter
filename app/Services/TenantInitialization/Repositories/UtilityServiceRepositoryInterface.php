<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization\Repositories;

use App\Enums\ServiceType;
use App\Models\Organization;
use App\Models\UtilityService;
use Illuminate\Support\Collection;

/**
 * Repository interface for utility service data access.
 * 
 * Abstracts database operations for utility services to improve
 * testability and allow for different storage implementations.
 */
interface UtilityServiceRepositoryInterface
{
    /**
     * Find a global template by service type.
     * 
     * @param ServiceType $serviceType The service type to find template for
     * 
     * @return UtilityService|null The global template or null if not found
     */
    public function findGlobalTemplate(ServiceType $serviceType): ?UtilityService;

    /**
     * Check if a global template exists for the service type.
     * 
     * @param ServiceType $serviceType The service type to check
     * 
     * @return bool True if template exists
     */
    public function hasGlobalTemplate(ServiceType $serviceType): bool;

    /**
     * Create multiple utility services in a batch operation.
     * 
     * @param array<array<string, mixed>> $servicesData Array of service data
     * 
     * @return Collection<int, UtilityService> Collection of created services
     */
    public function createBatch(array $servicesData): Collection;

    /**
     * Find utility services for a tenant by service types.
     * 
     * @param Organization $tenant The tenant to find services for
     * @param array<ServiceType> $serviceTypes Service types to find
     * 
     * @return Collection<int, UtilityService> Collection of utility services
     */
    public function findByTenantAndTypes(Organization $tenant, array $serviceTypes): Collection;

    /**
     * Find heating service for a tenant.
     * 
     * @param Organization $tenant The tenant to find heating service for
     * 
     * @return UtilityService|null The heating service or null if not found
     */
    public function findHeatingService(Organization $tenant): ?UtilityService;

    /**
     * Check if tenant has any utility services.
     * 
     * @param Organization $tenant The tenant to check
     * 
     * @return bool True if tenant has services
     */
    public function tenantHasServices(Organization $tenant): bool;
}