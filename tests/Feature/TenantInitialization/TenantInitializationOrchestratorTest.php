<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Property;
use App\Services\TenantInitialization\TenantInitializationOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->orchestrator = app(TenantInitializationOrchestrator::class);
});

it('initializes universal services for new tenant', function () {
    $tenant = Organization::factory()->create();
    
    $result = $this->orchestrator->initializeUniversalServices($tenant);
    
    expect($result->getServiceCount())->toBe(4);
    expect($result->hasService('electricity'))->toBeTrue();
    expect($result->hasService('water'))->toBeTrue();
    expect($result->hasService('heating'))->toBeTrue();
    expect($result->hasService('gas'))->toBeTrue();
    
    // Verify services are created in database
    $this->assertDatabaseCount('utility_services', 4);
    $this->assertDatabaseHas('utility_services', [
        'tenant_id' => $tenant->id,
        'service_type_bridge' => 'electricity',
    ]);
});

it('assigns services to existing properties', function () {
    $tenant = Organization::factory()->create();
    $properties = Property::factory()->count(3)->create(['tenant_id' => $tenant->id]);
    
    // Initialize services first
    $result = $this->orchestrator->initializeUniversalServices($tenant);
    
    // Assign to properties
    $assignments = $this->orchestrator->initializePropertyServiceAssignments(
        $tenant,
        $result->utilityServices
    );
    
    expect($assignments->getPropertyCount())->toBe(3);
    expect($assignments->getTotalConfigurationCount())->toBe(12); // 3 properties × 4 services
    
    // Verify configurations are created
    $this->assertDatabaseCount('service_configurations', 12);
});

it('prevents duplicate initialization', function () {
    $tenant = Organization::factory()->create();
    
    // First initialization should succeed
    $this->orchestrator->initializeUniversalServices($tenant);
    
    // Second initialization should fail
    expect(fn() => $this->orchestrator->initializeUniversalServices($tenant))
        ->toThrow(\App\Exceptions\TenantInitializationException::class);
});

it('validates heating compatibility correctly', function () {
    $tenant = Organization::factory()->create();
    
    // Initialize services first
    $this->orchestrator->initializeUniversalServices($tenant);
    
    // Check heating compatibility
    $isCompatible = $this->orchestrator->ensureHeatingCompatibility($tenant);
    
    expect($isCompatible)->toBeTrue();
});

it('handles tenant without properties gracefully', function () {
    $tenant = Organization::factory()->create();
    
    // Initialize services
    $result = $this->orchestrator->initializeUniversalServices($tenant);
    
    // Try to assign to properties (none exist)
    $assignments = $this->orchestrator->initializePropertyServiceAssignments(
        $tenant,
        $result->utilityServices
    );
    
    expect($assignments->getPropertyCount())->toBe(0);
    expect($assignments->getTotalConfigurationCount())->toBe(0);
});

it('maintains transaction integrity on failure', function () {
    $tenant = Organization::factory()->create();
    
    // Mock a service to fail during creation
    $this->mock(\App\Services\TenantInitialization\ServiceDefinitionProvider::class)
        ->shouldReceive('getDefaultServiceDefinitions')
        ->andThrow(new \Exception('Service creation failed'));
    
    expect(fn() => $this->orchestrator->initializeUniversalServices($tenant))
        ->toThrow(\Exception::class);
    
    // Verify no services were created due to transaction rollback
    $this->assertDatabaseCount('utility_services', 0);
});

it('logs operations correctly', function () {
    $tenant = Organization::factory()->create();
    
    Log::shouldReceive('info')
        ->with('Tenant operation started', Mockery::type('array'))
        ->once();
    
    Log::shouldReceive('info')
        ->with('Tenant operation completed successfully', Mockery::type('array'))
        ->once();
    
    $this->orchestrator->initializeUniversalServices($tenant);
});