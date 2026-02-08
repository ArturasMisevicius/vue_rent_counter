<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Data\TenantInitialization\InitializationResult;
use App\Data\TenantInitialization\PropertyServiceAssignmentResult;
use App\Exceptions\TenantInitializationException;
use App\Models\Organization;
use App\Models\Property;
use App\Models\UtilityService;
use App\Services\TenantInitializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit tests for TenantInitializationService.
 * 
 * Tests the core functionality of tenant initialization including
 * service creation, property assignment, and error handling.
 */
class TenantInitializationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TenantInitializationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Get the service from the container
        $this->service = app(TenantInitializationService::class);
    }

    public function test_initializes_universal_services_successfully(): void
    {
        $tenant = Organization::factory()->create();
        
        $result = $this->service->initializeUniversalServices($tenant);

        $this->assertInstanceOf(InitializationResult::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertGreaterThan(0, $result->getServiceCount());
        $this->assertGreaterThan(0, $result->getMeterConfigurationCount());
        
        // Verify services were created in database
        $this->assertDatabaseHas('utility_services', [
            'tenant_id' => $tenant->id,
            'service_type_bridge' => \App\Enums\ServiceType::ELECTRICITY,
        ]);
        
        $this->assertDatabaseHas('utility_services', [
            'tenant_id' => $tenant->id,
            'service_type_bridge' => \App\Enums\ServiceType::WATER,
        ]);
        
        $this->assertDatabaseHas('utility_services', [
            'tenant_id' => $tenant->id,
            'service_type_bridge' => \App\Enums\ServiceType::HEATING,
        ]);
        
        $this->assertDatabaseHas('utility_services', [
            'tenant_id' => $tenant->id,
            'service_type_bridge' => \App\Enums\ServiceType::GAS,
        ]);
    }

    public function test_validates_tenant_before_initialization(): void
    {
        $tenant = new Organization();
        $tenant->name = '';

        $this->expectException(TenantInitializationException::class);
        $this->expectExceptionMessage('Tenant name is required');

        $this->service->initializeUniversalServices($tenant);
    }

    public function test_initializes_property_service_assignments_successfully(): void
    {
        $tenant = Organization::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        
        // First initialize universal services
        $initResult = $this->service->initializeUniversalServices($tenant);
        $utilityServices = $initResult->utilityServices;

        $result = $this->service->initializePropertyServiceAssignments($tenant, $utilityServices);

        $this->assertInstanceOf(PropertyServiceAssignmentResult::class, $result);
        $this->assertTrue($result->hasConfigurations());
        $this->assertEquals(1, $result->getPropertyCount());
        
        // Verify service configurations were created
        $this->assertDatabaseHas('service_configurations', [
            'property_id' => $property->id,
        ]);
    }

    public function test_returns_empty_result_when_no_properties_exist(): void
    {
        $tenant = Organization::factory()->create();
        
        // Initialize services first
        $initResult = $this->service->initializeUniversalServices($tenant);
        $utilityServices = $initResult->utilityServices;

        $result = $this->service->initializePropertyServiceAssignments($tenant, $utilityServices);

        $this->assertInstanceOf(PropertyServiceAssignmentResult::class, $result);
        $this->assertFalse($result->hasConfigurations());
        $this->assertEquals(0, $result->getPropertyCount());
    }

    public function test_caches_slug_generation(): void
    {
        $tenant = Organization::factory()->create();
        
        // Clear any existing cache
        Cache::flush();
        
        // First call should generate and cache the slug
        $result1 = $this->service->initializeUniversalServices($tenant);
        $this->assertTrue($result1->isSuccessful());
        
        // Verify services have unique slugs
        $services = UtilityService::where('tenant_id', $tenant->id)->get();
        $slugs = $services->pluck('slug')->toArray();
        $this->assertEquals(count($slugs), count(array_unique($slugs)));
    }

    public function test_ensures_heating_compatibility_successfully(): void
    {
        $tenant = Organization::factory()->create();
        
        // Initialize services first to create heating service
        $this->service->initializeUniversalServices($tenant);

        $result = $this->service->ensureHeatingCompatibility($tenant);

        $this->assertTrue($result);
        
        // Verify heating service exists with correct configuration
        $heatingService = UtilityService::where('tenant_id', $tenant->id)
            ->where('service_type_bridge', \App\Enums\ServiceType::HEATING)
            ->first();
            
        $this->assertNotNull($heatingService);
        $this->assertEquals(\App\Enums\PricingModel::HYBRID, $heatingService->default_pricing_model);
        $this->assertTrue($heatingService->business_logic_config['supports_shared_distribution']);
    }

    public function test_heating_compatibility_fails_when_no_service_exists(): void
    {
        $tenant = Organization::factory()->create();

        $result = $this->service->ensureHeatingCompatibility($tenant);

        $this->assertFalse($result);
    }

    public function test_heating_compatibility_fails_with_invalid_configuration(): void
    {
        $tenant = Organization::factory()->create();
        
        // Create heating service with wrong configuration
        UtilityService::factory()->create([
            'tenant_id' => $tenant->id,
            'service_type_bridge' => \App\Enums\ServiceType::HEATING,
            'default_pricing_model' => \App\Enums\PricingModel::FLAT, // Wrong pricing model
            'business_logic_config' => ['supports_shared_distribution' => true],
        ]);

        $result = $this->service->ensureHeatingCompatibility($tenant);

        $this->assertFalse($result);
    }

    public function test_validates_tenant_must_exist_in_database(): void
    {
        $tenant = new Organization([
            'name' => 'Test Tenant',
        ]);
        // Don't save to database

        $this->expectException(TenantInitializationException::class);
        $this->expectExceptionMessage('Tenant must be persisted to database');

        $this->service->initializeUniversalServices($tenant);
    }

    public function test_validates_tenant_must_have_id(): void
    {
        $tenant = new Organization([
            'name' => 'Test Tenant',
        ]);
        $tenant->exists = true; // Fake exists but no ID

        $this->expectException(TenantInitializationException::class);
        $this->expectExceptionMessage('Tenant ID is required');

        $this->service->initializeUniversalServices($tenant);
    }
}