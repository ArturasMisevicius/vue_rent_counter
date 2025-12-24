<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization\Repositories;

use App\Enums\ServiceType;
use App\Models\Organization;
use App\Models\UtilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cached decorator for utility service repository.
 * 
 * Implements Decorator pattern to add caching capabilities
 * to the base repository implementation.
 */
final readonly class CachedUtilityServiceRepository implements UtilityServiceRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'utility_service';

    public function __construct(
        private UtilityServiceRepositoryInterface $repository,
    ) {}

    public function findGlobalTemplate(ServiceType $serviceType): ?UtilityService
    {
        $cacheKey = $this->getCacheKey('global_template', $serviceType->value);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($serviceType) {
            return $this->repository->findGlobalTemplate($serviceType);
        });
    }

    public function hasGlobalTemplate(ServiceType $serviceType): bool
    {
        return $this->findGlobalTemplate($serviceType) !== null;
    }

    public function createBatch(array $servicesData): Collection
    {
        // No caching for write operations, delegate to base repository
        $services = $this->repository->createBatch($servicesData);
        
        // Invalidate related caches
        $this->invalidateRelatedCaches($servicesData);
        
        return $services;
    }

    public function findByTenantAndTypes(Organization $tenant, array $serviceTypes): Collection
    {
        $typeValues = array_map(fn($type) => $type->value, $serviceTypes);
        $cacheKey = $this->getCacheKey('tenant_services', $tenant->id, implode(',', $typeValues));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant, $serviceTypes) {
            return $this->repository->findByTenantAndTypes($tenant, $serviceTypes);
        });
    }

    public function findHeatingService(Organization $tenant): ?UtilityService
    {
        $cacheKey = $this->getCacheKey('heating_service', $tenant->id);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant) {
            return $this->repository->findHeatingService($tenant);
        });
    }

    public function tenantHasServices(Organization $tenant): bool
    {
        $cacheKey = $this->getCacheKey('tenant_has_services', $tenant->id);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant) {
            return $this->repository->tenantHasServices($tenant);
        });
    }

    /**
     * Generate cache key with prefix.
     */
    private function getCacheKey(string $operation, mixed ...$params): string
    {
        return self::CACHE_PREFIX . ':' . $operation . ':' . implode(':', $params);
    }

    /**
     * Invalidate caches related to created services.
     */
    private function invalidateRelatedCaches(array $servicesData): void
    {
        foreach ($servicesData as $serviceData) {
            if (isset($serviceData['tenant_id'])) {
                Cache::forget($this->getCacheKey('tenant_has_services', $serviceData['tenant_id']));
                Cache::forget($this->getCacheKey('heating_service', $serviceData['tenant_id']));
            }
        }
    }
}