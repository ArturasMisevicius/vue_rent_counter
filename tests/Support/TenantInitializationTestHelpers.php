<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Organization;
use App\Models\Property;
use App\Models\UtilityService;
use App\Models\ServiceConfiguration;

/**
 * Test helpers for TenantInitializationService testing
 */
trait TenantInitializationTestHelpers
{
    /**
     * Create a tenant with specified number of properties
     */
    protected function createTenantWithProperties(int $propertyCount = 1): array
    {
        $tenant = Organization::factory()->create();
        
        $properties = [];
        for ($i = 0; $i < $propertyCount; $i++) {
            $properties[] = Property::factory()->forTenantId($tenant->id)->create();
        }
        
        return [$tenant, $properties];
    }
    
    /**
     * Assert service configuration is valid
     */
    protected function assertValidServiceConfiguration(array $config): void
    {
        expect($config)->toHaveKeys([
            'utility_service_id',
            'pricing_model', 
            'is_active',
            'effective_from'
        ]);
        
        expect($config['is_active'])->toBeTrue();
        expect($config['effective_from'])->not()->toBeNull();
    }
    
    /**
     * Assert tenant isolation is maintained
     */
    protected function assertTenantIsolation(Organization $tenant): void
    {
        $services = UtilityService::where('tenant_id', $tenant->id)->get();
        
        $services->each(function ($service) use ($tenant) {
            expect($service->tenant_id)->toBe($tenant->id);
        });
    }
}