<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization\Repositories;

use App\Enums\ServiceType;
use App\Models\Organization;
use App\Models\UtilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Optimized Eloquent repository with batch operations and query optimization.
 * 
 * Implements performance optimizations including batch inserts,
 * eager loading, and query optimization techniques.
 */
final readonly class OptimizedEloquentUtilityServiceRepository implements UtilityServiceRepositoryInterface
{
    public function findGlobalTemplate(ServiceType $serviceType): ?UtilityService
    {
        return UtilityService::where('is_global_template', true)
            ->where('service_type_bridge', $serviceType)
            ->first();
    }

    public function hasGlobalTemplate(ServiceType $serviceType): bool
    {
        return UtilityService::where('is_global_template', true)
            ->where('service_type_bridge', $serviceType)
            ->exists();
    }

    public function createBatch(array $servicesData): Collection
    {
        // Use database transaction for batch operations
        return DB::transaction(function () use ($servicesData) {
            $services = collect();
            
            // Prepare data for batch insert
            $insertData = [];
            $now = now();
            
            foreach ($servicesData as $serviceData) {
                $insertData[] = array_merge($serviceData, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            
            // Batch insert for better performance
            DB::table('utility_services')->insert($insertData);
            
            // Retrieve created services with proper models
            foreach ($insertData as $data) {
                $service = UtilityService::where('tenant_id', $data['tenant_id'])
                    ->where('slug', $data['slug'])
                    ->first();
                
                if ($service) {
                    $services->push($service);
                }
            }
            
            return $services;
        });
    }

    public function findByTenantAndTypes(Organization $tenant, array $serviceTypes): Collection
    {
        $serviceTypeValues = array_map(fn($type) => $type->value, $serviceTypes);
        
        return UtilityService::where('tenant_id', $tenant->id)
            ->whereIn('service_type_bridge', $serviceTypeValues)
            ->select(['id', 'tenant_id', 'name', 'slug', 'service_type_bridge', 'default_pricing_model'])
            ->get();
    }

    public function findHeatingService(Organization $tenant): ?UtilityService
    {
        return UtilityService::where('tenant_id', $tenant->id)
            ->where('service_type_bridge', ServiceType::HEATING)
            ->select(['id', 'tenant_id', 'service_type_bridge', 'default_pricing_model', 'business_logic_config'])
            ->first();
    }

    public function tenantHasServices(Organization $tenant): bool
    {
        return UtilityService::where('tenant_id', $tenant->id)->exists();
    }
}