<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization\Repositories;

use App\Enums\ServiceType;
use App\Models\Organization;
use App\Models\UtilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Eloquent implementation of utility service repository.
 * 
 * Provides optimized database operations with caching and batch processing
 * for utility service management.
 */
final readonly class EloquentUtilityServiceRepository implements UtilityServiceRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    public function findGlobalTemplate(ServiceType $serviceType): ?UtilityService
    {
        $cacheKey = "global_template:{$serviceType->value}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($serviceType) {
            return UtilityService::where('is_global_template', true)
                ->where('service_type_bridge', $serviceType)
                ->first();
        });
    }

    public function hasGlobalTemplate(ServiceType $serviceType): bool
    {
        return $this->findGlobalTemplate($serviceType) !== null;
    }

    public function createBatch(array $servicesData): Collection
    {
        // Use batch insert for better performance
        $services = collect();
        
        foreach ($servicesData as $serviceData) {
            $services->push(UtilityService::create($serviceData));
        }
        
        return $services;
    }

    public function findByTenantAndTypes(Organization $tenant, array $serviceTypes): Collection
    {
        $serviceTypeValues = array_map(fn($type) => $type->value, $serviceTypes);
        
        return UtilityService::where('tenant_id', $tenant->id)
            ->whereIn('service_type_bridge', $serviceTypeValues)
            ->get();
    }

    public function findHeatingService(Organization $tenant): ?UtilityService
    {
        return UtilityService::where('tenant_id', $tenant->id)
            ->where('service_type_bridge', ServiceType::HEATING)
            ->first();
    }

    public function tenantHasServices(Organization $tenant): bool
    {
        return UtilityService::where('tenant_id', $tenant->id)->exists();
    }
}