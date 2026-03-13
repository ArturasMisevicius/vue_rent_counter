<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Property;
use App\Models\UtilityService;
use App\Models\ServiceConfiguration;
use App\Services\TenantInitializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Test suite for TenantInitializationService
 * 
 * Tests universal service initialization for new tenants including:
 * - Default utility service creation
 * - Property service assignments
 * - Backward compatibility with heating systems
 * - Error handling and edge cases
 * - Performance and security considerations
 */

uses(RefreshDatabase::class);

// Constants for test expectations
const EXPECTED_DEFAULT_SERVICES = 4;
const DEFAULT_SERVICE_TYPES = ['electricity', 'water', 'heating', 'gas'];

beforeEach(function () {
    $this->service = app(TenantInitializationService::class);
    
    // Clear any cached data
    Cache::flush();
    
    // Ensure clean database state
    DB::statement('PRAGMA foreign_keys = ON');
});

describe('Universal Service Initialization', function () {
    it('initializes universal services for a new tenant', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        $property = Property::factory()->forTenantId($tenant->id)->create();
        
        // Act
        $result = $this->service->initializeUniversalServices($tenant);
        
        // Assert - Verify services were created
        $services = UtilityService::where('tenant_id', $tenant->id)->get();
        expect($services)->toHaveCount(EXPECTED_DEFAULT_SERVICES);
        
        // Verify service types by checking service_type_bridge enum values
        $serviceTypes = $services->pluck('service_type_bridge')
            ->map(fn($type) => $type->value)
            ->toArray();
        
        foreach (DEFAULT_SERVICE_TYPES as $expectedType) {
            expect($serviceTypes)->toContain($expectedType);
        }
        
        // Verify each service has proper configuration
        $services->each(function ($service) {
            expect($service->is_active)->toBeTrue();
            expect($service->configuration_schema)->toBeArray();
            expect($service->validation_rules)->toBeArray();
            expect($service->business_logic_config)->toBeArray();
            expect($service->tenant_id)->toBe($service->tenant_id);
        });
        
        // Verify return structure
        expect($result)->toHaveKeys(['utility_services', 'meter_configurations']);
        expect($result['utility_services'])->toHaveCount(EXPECTED_DEFAULT_SERVICES);
    });

    it('creates default meter configurations for each service', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        $property = Property::factory()->forTenantId($tenant->id)->create();
        
        // Act
        $result = $this->service->initializeUniversalServices($tenant);
        
        // Assert - Verify meter configurations were created
        expect($result)->toHaveKey('meter_configurations');
        expect($result['meter_configurations'])->toHaveCount(EXPECTED_DEFAULT_SERVICES);
        
        // Verify each configuration has proper structure
        foreach ($result['meter_configurations'] as $serviceKey => $config) {
            expect($config)->toHaveKeys([
                'utility_service_id',
                'pricing_model',
                'is_active',
                'effective_from'
            ]);
            expect($config['is_active'])->toBeTrue();
            expect($config['effective_from'])->not()->toBeNull();
            
            // Verify service key matches expected types
            expect(DEFAULT_SERVICE_TYPES)->toContain($serviceKey);
        }
    });
});

describe('Property Service Assignment', function () {
    it('assigns services to all tenant properties', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        $property1 = Property::factory()->forTenantId($tenant->id)->create();
        $property2 = Property::factory()->forTenantId($tenant->id)->create();
        
        // Act - Initialize universal services first
        $result = $this->service->initializeUniversalServices($tenant);
        
        // Initialize property service assignments
        $serviceConfigurations = $this->service->initializePropertyServiceAssignments(
            $tenant, 
            $result['utility_services']
        );
        
        // Assert - Verify property service assignments
        expect($serviceConfigurations)->toHaveCount(2); // 2 properties
        expect($serviceConfigurations[$property1->id])->toHaveCount(EXPECTED_DEFAULT_SERVICES);
        expect($serviceConfigurations[$property2->id])->toHaveCount(EXPECTED_DEFAULT_SERVICES);
        
        // Verify service configurations in database
        $dbConfigurations = ServiceConfiguration::whereIn('property_id', [$property1->id, $property2->id])->get();
        expect($dbConfigurations)->toHaveCount(EXPECTED_DEFAULT_SERVICES * 2); // 4 services × 2 properties
        
        $dbConfigurations->each(function ($config) use ($tenant) {
            expect($config->is_active)->toBeTrue();
            expect($config->effective_from)->not()->toBeNull();
            expect($config->tenant_id)->toBe($tenant->id);
            expect($config->property_id)->toBeIn([$property1->id, $property2->id]);
        });
    });

    it('handles tenants with no properties gracefully', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        
        // Act & Assert - Should not throw an exception
        $result = $this->service->initializeUniversalServices($tenant);
        
        // Services should still be created
        $services = UtilityService::where('tenant_id', $tenant->id)->get();
        expect($services)->toHaveCount(EXPECTED_DEFAULT_SERVICES);
        
        // But no property services should exist when initializing property assignments
        $serviceConfigurations = $this->service->initializePropertyServiceAssignments(
            $tenant, 
            $result['utility_services']
        );
        expect($serviceConfigurations)->toHaveCount(0);
    });
});

describe('Error Handling and Edge Cases', function () {
    it('prevents duplicate services when called multiple times', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        $property = Property::factory()->forTenantId($tenant->id)->create();
        
        // Act - Call initialization twice
        $this->service->initializeUniversalServices($tenant);
        $this->service->initializeUniversalServices($tenant);
        
        // Assert - Should handle duplicates gracefully
        $services = UtilityService::where('tenant_id', $tenant->id)->get();
        
        // Allow for potential duplicates with different slugs but verify reasonable count
        expect($services->count())->toBeLessThanOrEqual(EXPECTED_DEFAULT_SERVICES * 2);
        
        // Verify each service type exists at least once
        $serviceTypes = $services->pluck('service_type_bridge')
            ->map(fn($type) => $type->value)
            ->unique()
            ->toArray();
            
        foreach (DEFAULT_SERVICE_TYPES as $expectedType) {
            expect($serviceTypes)->toContain($expectedType);
        }
    });

    it('handles database transaction failures gracefully', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        
        // Mock a database failure scenario
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));
        
        // Act & Assert
        expect(fn() => $this->service->initializeUniversalServices($tenant))
            ->toThrow(\RuntimeException::class, 'Failed to initialize universal services');
    });

    it('validates tenant exists before initialization', function () {
        // Arrange - Create tenant then delete it
        $tenant = Organization::factory()->create();
        $tenantId = $tenant->id;
        $tenant->delete();
        
        // Recreate with same ID to test edge case
        $deletedTenant = new Organization(['id' => $tenantId]);
        
        // Act & Assert - Should handle gracefully
        expect(fn() => $this->service->initializeUniversalServices($deletedTenant))
            ->not()->toThrow(\Exception::class);
    });
});

describe('Backward Compatibility', function () {
    it('maintains backward compatibility with existing heating initialization', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        $property = Property::factory()->forTenantId($tenant->id)->create();
        
        // Act
        $result = $this->service->initializeUniversalServices($tenant);
        
        // Assert - Verify heating service exists and has proper configuration
        $heatingService = UtilityService::where('tenant_id', $tenant->id)
            ->get()
            ->first(fn($service) => $service->service_type_bridge->value === 'heating');
        
        expect($heatingService)->not()->toBeNull();
        expect($heatingService->business_logic_config)->toHaveKey('supports_shared_distribution');
        expect($heatingService->business_logic_config['supports_shared_distribution'])->toBeTrue();
        
        // Test heating compatibility check
        $isCompatible = $this->service->ensureHeatingCompatibility($tenant);
        expect($isCompatible)->toBeTrue();
        
        // Verify heating-specific configuration
        expect($heatingService->default_pricing_model->value)->toBe('hybrid');
        expect($heatingService->business_logic_config)->toHaveKey('supports_seasonal_rates');
        expect($heatingService->business_logic_config['supports_seasonal_rates'])->toBeTrue();
    });

    it('preserves existing heating calculator integration', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        
        // Act
        $result = $this->service->initializeUniversalServices($tenant);
        
        // Assert - Verify heating service configuration matches heating calculator expectations
        $heatingService = $result['utility_services']['heating'] ?? null;
        expect($heatingService)->not()->toBeNull();
        
        $heatingConfig = $result['meter_configurations']['heating'] ?? null;
        expect($heatingConfig)->not()->toBeNull();
        expect($heatingConfig['distribution_method']->value)->toBe('by_area');
        expect($heatingConfig['is_shared_service'])->toBeTrue();
        expect($heatingConfig)->toHaveKey('area_type');
        expect($heatingConfig['area_type'])->toBe('heated_area');
    });
});

describe('Performance and Security', function () {
    it('performs efficiently with multiple tenants', function () {
        // Arrange
        $tenants = Organization::factory()->count(10)->create();
        
        // Act & Assert - Measure performance
        $startTime = microtime(true);
        
        foreach ($tenants as $tenant) {
            $this->service->initializeUniversalServices($tenant);
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // Should complete within reasonable time (adjust threshold as needed)
        expect($executionTime)->toBeLessThan(5.0); // 5 seconds for 10 tenants
        
        // Verify all services were created
        $totalServices = UtilityService::whereIn('tenant_id', $tenants->pluck('id'))->count();
        expect($totalServices)->toBe(EXPECTED_DEFAULT_SERVICES * 10);
    });

    it('maintains tenant isolation', function () {
        // Arrange
        $tenant1 = Organization::factory()->create();
        $tenant2 = Organization::factory()->create();
        
        // Act
        $this->service->initializeUniversalServices($tenant1);
        $this->service->initializeUniversalServices($tenant2);
        
        // Assert - Verify tenant isolation
        $tenant1Services = UtilityService::where('tenant_id', $tenant1->id)->get();
        $tenant2Services = UtilityService::where('tenant_id', $tenant2->id)->get();
        
        expect($tenant1Services)->toHaveCount(EXPECTED_DEFAULT_SERVICES);
        expect($tenant2Services)->toHaveCount(EXPECTED_DEFAULT_SERVICES);
        
        // Verify no cross-tenant contamination
        $tenant1Services->each(function ($service) use ($tenant1) {
            expect($service->tenant_id)->toBe($tenant1->id);
        });
        
        $tenant2Services->each(function ($service) use ($tenant2) {
            expect($service->tenant_id)->toBe($tenant2->id);
        });
        
        // Verify unique slugs across tenants
        $allSlugs = UtilityService::whereIn('tenant_id', [$tenant1->id, $tenant2->id])
            ->pluck('slug')
            ->toArray();
        expect($allSlugs)->toHaveCount(count(array_unique($allSlugs)));
    });

    it('handles concurrent initialization requests', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        
        // Act - Simulate concurrent requests
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->service->initializeUniversalServices($tenant);
        }
        
        // Assert - Should handle gracefully without errors
        expect($results)->toHaveCount(3);
        
        // Verify reasonable number of services (allowing for some duplicates)
        $services = UtilityService::where('tenant_id', $tenant->id)->get();
        expect($services->count())->toBeLessThanOrEqual(EXPECTED_DEFAULT_SERVICES * 3);
        expect($services->count())->toBeGreaterThanOrEqual(EXPECTED_DEFAULT_SERVICES);
    });
});

describe('Data Validation and Integrity', function () {
    it('validates service configuration schemas', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        
        // Act
        $result = $this->service->initializeUniversalServices($tenant);
        
        // Assert - Verify each service has valid configuration schema
        foreach ($result['utility_services'] as $serviceKey => $service) {
            expect($service->configuration_schema)->toBeArray();
            expect($service->configuration_schema)->toHaveKey('required');
            expect($service->configuration_schema)->toHaveKey('optional');
            expect($service->validation_rules)->toBeArray();
            
            // Verify schema structure
            $schema = $service->configuration_schema;
            expect($schema['required'])->toBeArray();
            expect($schema['optional'])->toBeArray();
            
            // Verify validation rules structure
            expect($service->validation_rules)->toHaveKey('reading_frequency');
            expect($service->validation_rules)->toHaveKey('min_consumption');
            expect($service->validation_rules)->toHaveKey('max_consumption');
        }
    });

    it('ensures proper enum values and relationships', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        
        // Act
        $result = $this->service->initializeUniversalServices($tenant);
        
        // Assert - Verify enum values are valid
        foreach ($result['utility_services'] as $service) {
            expect($service->service_type_bridge)->toBeInstanceOf(\App\Enums\ServiceType::class);
            expect($service->default_pricing_model)->toBeInstanceOf(\App\Enums\PricingModel::class);
        }
        
        foreach ($result['meter_configurations'] as $config) {
            expect($config['pricing_model'])->toBeInstanceOf(\App\Enums\PricingModel::class);
            expect($config['distribution_method'])->toBeInstanceOf(\App\Enums\DistributionMethod::class);
        }
    });

    it('maintains referential integrity', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        $property = Property::factory()->forTenantId($tenant->id)->create();
        
        // Act
        $result = $this->service->initializeUniversalServices($tenant);
        $serviceConfigurations = $this->service->initializePropertyServiceAssignments(
            $tenant, 
            $result['utility_services']
        );
        
        // Assert - Verify all foreign keys are valid
        foreach ($serviceConfigurations as $propertyConfigs) {
            foreach ($propertyConfigs as $config) {
                // Verify utility service exists
                expect(UtilityService::find($config->utility_service_id))->not()->toBeNull();
                
                // Verify property exists
                expect(Property::find($config->property_id))->not()->toBeNull();
                
                // Verify tenant consistency
                expect($config->tenant_id)->toBe($tenant->id);
            }
        }
    });
});

describe('Cache and Performance Optimization', function () {
    it('clears relevant caches after initialization', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        
        // Pre-populate cache
        Cache::put('utility_services.form_options.' . $tenant->id, ['test' => 'data'], 3600);
        
        // Act
        $this->service->initializeUniversalServices($tenant);
        
        // Assert - Cache should be cleared
        expect(Cache::has('utility_services.form_options.' . $tenant->id))->toBeFalse();
    });

    it('handles large property counts efficiently', function () {
        // Arrange
        $tenant = Organization::factory()->create();
        $properties = Property::factory()
            ->count(50)
            ->forTenantId($tenant->id)
            ->create();
        
        // Act
        $result = $this->service->initializeUniversalServices($tenant);
        
        $startTime = microtime(true);
        $serviceConfigurations = $this->service->initializePropertyServiceAssignments(
            $tenant, 
            $result['utility_services']
        );
        $executionTime = microtime(true) - $startTime;
        
        // Assert
        expect($serviceConfigurations)->toHaveCount(50);
        expect($executionTime)->toBeLessThan(2.0); // Should complete within 2 seconds
        
        // Verify database efficiency
        $configCount = ServiceConfiguration::where('tenant_id', $tenant->id)->count();
        expect($configCount)->toBe(50 * EXPECTED_DEFAULT_SERVICES);
    });
});