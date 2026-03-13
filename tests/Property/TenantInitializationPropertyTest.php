<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Property;
use App\Services\TenantInitialization\TenantInitializationOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Property-based tests for tenant initialization invariants.
 * 
 * These tests verify that certain properties always hold true
 * regardless of the specific input data.
 */

it('always creates exactly 4 utility services for any valid tenant', function () {
    $orchestrator = app(TenantInitializationOrchestrator::class);
    
    // Property: For any valid tenant, initialization creates exactly 4 services
    for ($i = 0; $i < 50; $i++) {
        $tenant = Organization::factory()->create([
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
        ]);
        
        $result = $orchestrator->initializeUniversalServices($tenant);
        
        expect($result->getServiceCount())->toBe(4);
        expect($result->hasService('electricity'))->toBeTrue();
        expect($result->hasService('water'))->toBeTrue();
        expect($result->hasService('heating'))->toBeTrue();
        expect($result->hasService('gas'))->toBeTrue();
        
        // Clean up for next iteration
        $tenant->utilityServices()->delete();
        $tenant->delete();
    }
});

it('property assignment count equals properties × services', function () {
    $orchestrator = app(TenantInitializationOrchestrator::class);
    
    // Property: Total configurations = number of properties × number of services
    for ($i = 0; $i < 30; $i++) {
        $tenant = Organization::factory()->create();
        $propertyCount = fake()->numberBetween(1, 10);
        
        Property::factory()->count($propertyCount)->create(['tenant_id' => $tenant->id]);
        
        $result = $orchestrator->initializeUniversalServices($tenant);
        $assignments = $orchestrator->initializePropertyServiceAssignments(
            $tenant,
            $result->utilityServices
        );
        
        expect($assignments->getPropertyCount())->toBe($propertyCount);
        expect($assignments->getTotalConfigurationCount())->toBe($propertyCount * 4);
        
        // Clean up
        $tenant->properties()->delete();
        $tenant->utilityServices()->delete();
        $tenant->delete();
    }
});

it('heating compatibility is always true after initialization', function () {
    $orchestrator = app(TenantInitializationOrchestrator::class);
    
    // Property: Heating compatibility check always returns true after initialization
    for ($i = 0; $i < 25; $i++) {
        $tenant = Organization::factory()->create();
        
        $orchestrator->initializeUniversalServices($tenant);
        $isCompatible = $orchestrator->ensureHeatingCompatibility($tenant);
        
        expect($isCompatible)->toBeTrue();
        
        // Clean up
        $tenant->utilityServices()->delete();
        $tenant->delete();
    }
});

it('service slugs are always unique within tenant scope', function () {
    $orchestrator = app(TenantInitializationOrchestrator::class);
    
    // Property: All service slugs are unique within a tenant
    for ($i = 0; $i < 20; $i++) {
        $tenant = Organization::factory()->create();
        
        $result = $orchestrator->initializeUniversalServices($tenant);
        $slugs = $result->utilityServices->pluck('slug')->toArray();
        
        expect($slugs)->toHaveCount(4);
        expect(array_unique($slugs))->toHaveCount(4); // All slugs are unique
        
        // Clean up
        $tenant->utilityServices()->delete();
        $tenant->delete();
    }
});

it('initialization is idempotent for validation failures', function () {
    $orchestrator = app(TenantInitializationOrchestrator::class);
    
    // Property: Failed initialization attempts don't leave partial data
    for ($i = 0; $i < 15; $i++) {
        $tenant = Organization::factory()->create();
        
        // First initialization succeeds
        $orchestrator->initializeUniversalServices($tenant);
        
        // Second initialization fails but doesn't create duplicate data
        try {
            $orchestrator->initializeUniversalServices($tenant);
        } catch (\App\Exceptions\TenantInitializationException $e) {
            // Expected exception
        }
        
        // Verify still only 4 services exist
        expect($tenant->utilityServices()->count())->toBe(4);
        
        // Clean up
        $tenant->utilityServices()->delete();
        $tenant->delete();
    }
});