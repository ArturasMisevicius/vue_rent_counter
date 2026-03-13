<?php

declare(strict_types=1);

namespace Tests\Property;

use App\Actions\AssignUtilityServiceAction;
use App\DTOs\AssignUtilityServiceDTO;
use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Exceptions\ServiceConfigurationException;
use App\Models\Building;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * **Feature: universal-utility-management, Property 2: Property Service Assignment with Audit Trail**
 * 
 * Property-based tests for service assignment and validation functionality.
 * 
 * Tests the invariant: "For any property and utility service combination, 
 * assigning the service should create proper configuration records with 
 * complete audit trails and validation"
 * 
 * Validates Requirements 3.1, 3.2, 3.3:
 * - 3.1: Manager assigning services to properties with pricing overrides and audit trail
 * - 3.2: Property-specific service configuration with rate adjustments and effective dates
 * - 3.3: Validation that multiple service configurations don't conflict
 */
class PropertyServiceAssignmentPropertyTest extends TestCase
{
    use RefreshDatabase;

    private AssignUtilityServiceAction $assignAction;
    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($this->user);
        
        // Use the real action from the container
        $this->assignAction = app(AssignUtilityServiceAction::class);
    }

    /**
     * Property: Service assignment creates valid configuration
     * 
     * For any valid property and utility service combination, 
     * assigning the service should create a ServiceConfiguration 
     * record with all required fields populated correctly.
     */
    public function test_property_service_assignment_creates_valid_configuration(): void
    {
        $this->runPropertyTest(100, function () {
            // Generate random property and utility service
            $property = $this->generateRandomProperty();
            $utilityService = $this->generateRandomUtilityService();
            $assignmentData = $this->generateValidAssignmentData($property, $utilityService);
            
            // Act - assign service to property
            $configuration = $this->assignAction->execute($assignmentData);
            
            // Assert configuration was created correctly
            $this->assertInstanceOf(ServiceConfiguration::class, $configuration);
            $this->assertEquals($property->id, $configuration->property_id);
            $this->assertEquals($utilityService->id, $configuration->utility_service_id);
            $this->assertEquals($assignmentData->pricingModel, $configuration->pricing_model);
            $this->assertEquals($assignmentData->isSharedService, $configuration->is_shared_service);
            $this->assertEquals($assignmentData->effectiveFrom->toDateString(), $configuration->effective_from->toDateString());
            $this->assertTrue($configuration->is_active);
            
            // Assert configuration is persisted in database
            $this->assertDatabaseHas('service_configurations', [
                'id' => $configuration->id,
                'property_id' => $property->id,
                'utility_service_id' => $utilityService->id,
                'tenant_id' => $this->tenant->id,
                'is_active' => true,
            ]);
        });
    }

    /**
     * Property: Pricing overrides are preserved
     * 
     * When assigning a service with custom pricing overrides,
     * the configuration should preserve all override values
     * and maintain audit trail of changes.
     */
    public function test_property_pricing_overrides_are_preserved(): void
    {
        $this->runPropertyTest(50, function () {
            $property = $this->generateRandomProperty();
            $utilityService = $this->generateRandomUtilityService();
            
            // Generate assignment with pricing overrides
            $customRateSchedule = [
                'unit_rate' => fake()->randomFloat(4, 0.5, 3.0),
                'base_fee' => fake()->randomFloat(2, 10, 100),
                'peak_rate' => fake()->randomFloat(4, 1.0, 5.0),
            ];
            
            $assignmentData = $this->generateValidAssignmentData($property, $utilityService, [
                'rate_schedule' => $customRateSchedule,
                'configuration_overrides' => [
                    'custom_setting' => fake()->word(),
                    'override_reason' => 'Property-specific pricing adjustment',
                ],
            ]);
            
            // Act
            $configuration = $this->assignAction->execute($assignmentData);
            
            // Assert pricing overrides are preserved
            $this->assertEquals($customRateSchedule, $configuration->rate_schedule);
            $this->assertNotNull($configuration->configuration_overrides);
            $this->assertEquals('Property-specific pricing adjustment', 
                $configuration->configuration_overrides['override_reason']);
            
            // Assert effective rate calculation works with overrides
            $effectiveRate = $configuration->getEffectiveRate();
            $this->assertEquals($customRateSchedule['unit_rate'], $effectiveRate);
        });
    }

    /**
     * Property: Effective date validation
     * 
     * Service configurations should respect effective dates
     * and prevent overlapping configurations for the same service.
     */
    public function test_property_effective_date_validation(): void
    {
        $this->runPropertyTest(30, function () {
            $property = $this->generateRandomProperty();
            $utilityService = $this->generateRandomUtilityService();
            
            // Create first configuration
            $firstStart = now()->subMonth();
            $firstEnd = now()->addMonth();
            
            $firstAssignment = $this->generateValidAssignmentData($property, $utilityService, [
                'effective_from' => $firstStart,
                'effective_until' => $firstEnd,
            ]);
            
            $firstConfig = $this->assignAction->execute($firstAssignment);
            
            // Attempt to create overlapping configuration
            $overlappingStart = now()->subWeek();
            $overlappingEnd = now()->addWeeks(2);
            
            $overlappingAssignment = $this->generateValidAssignmentData($property, $utilityService, [
                'effective_from' => $overlappingStart,
                'effective_until' => $overlappingEnd,
            ]);
            
            // Assert overlapping configuration is rejected
            $this->expectException(ServiceConfigurationException::class);
            $this->assignAction->execute($overlappingAssignment);
        });
    }

    /**
     * Property: Non-overlapping configurations are allowed
     * 
     * Multiple configurations for the same service should be allowed
     * if they don't overlap in time periods.
     */
    public function test_property_non_overlapping_configurations_allowed(): void
    {
        $this->runPropertyTest(25, function () {
            $property = $this->generateRandomProperty();
            $utilityService = $this->generateRandomUtilityService();
            
            // Create first configuration (past period)
            $firstStart = now()->subMonths(3);
            $firstEnd = now()->subMonth();
            
            $firstAssignment = $this->generateValidAssignmentData($property, $utilityService, [
                'effective_from' => $firstStart,
                'effective_until' => $firstEnd,
            ]);
            
            $firstConfig = $this->assignAction->execute($firstAssignment);
            
            // Create second configuration (future period)
            $secondStart = now()->addMonth();
            $secondEnd = now()->addMonths(3);
            
            $secondAssignment = $this->generateValidAssignmentData($property, $utilityService, [
                'effective_from' => $secondStart,
                'effective_until' => $secondEnd,
            ]);
            
            // Act - should succeed without exception
            $secondConfig = $this->assignAction->execute($secondAssignment);
            
            // Assert both configurations exist
            $this->assertNotEquals($firstConfig->id, $secondConfig->id);
            $this->assertEquals($property->id, $secondConfig->property_id);
            $this->assertEquals($utilityService->id, $secondConfig->utility_service_id);
            
            // Assert database has both configurations
            $this->assertDatabaseHas('service_configurations', ['id' => $firstConfig->id]);
            $this->assertDatabaseHas('service_configurations', ['id' => $secondConfig->id]);
        });
    }

    /**
     * Property: Shared service distribution validation
     * 
     * When assigning shared services, the system should validate
     * that distribution methods are compatible with property data.
     */
    public function test_property_shared_service_distribution_validation(): void
    {
        $this->runPropertyTest(40, function () {
            $property = $this->generateRandomPropertyWithArea();
            $utilityService = $this->generateRandomUtilityService();
            
            $distributionMethod = fake()->randomElement([
                DistributionMethod::EQUAL,
                DistributionMethod::AREA,
                DistributionMethod::BY_CONSUMPTION,
            ]);
            
            $assignmentData = $this->generateValidAssignmentData($property, $utilityService, [
                'is_shared_service' => true,
                'distribution_method' => $distributionMethod,
                'area_type' => 'total_area',
            ]);
            
            // Act
            $configuration = $this->assignAction->execute($assignmentData);
            
            // Assert shared service configuration
            $this->assertTrue($configuration->is_shared_service);
            $this->assertEquals($distributionMethod, $configuration->distribution_method);
            
            // Assert area-based distribution has area type
            if ($distributionMethod === DistributionMethod::AREA) {
                $this->assertEquals('total_area', $configuration->area_type);
                $this->assertTrue($configuration->requiresAreaData());
            }
        });
    }

    /**
     * Property: Custom formula validation
     * 
     * When using custom formulas for pricing, the system should
     * validate formula syntax and prevent dangerous operations.
     */
    public function test_property_custom_formula_validation(): void
    {
        $this->runPropertyTest(20, function () {
            $property = $this->generateRandomProperty();
            $utilityService = $this->generateRandomUtilityService();
            
            // Test valid custom formulas
            $validFormulas = [
                'consumption * 1.5',
                'area * 0.8 + consumption * 0.2',
                'base_rate + (consumption * unit_rate)',
                '(area / 100) * monthly_rate',
            ];
            
            $formula = fake()->randomElement($validFormulas);
            
            $assignmentData = $this->generateValidAssignmentData($property, $utilityService, [
                'pricing_model' => PricingModel::HYBRID,
                'custom_formula' => $formula,
            ]);
            
            // Act - should succeed for valid formulas
            $configuration = $this->assignAction->execute($assignmentData);
            
            // Assert formula is stored
            $this->assertEquals($formula, $configuration->custom_formula);
        });
    }

    /**
     * Property: Dangerous formula rejection
     * 
     * The system should reject formulas containing dangerous functions
     * or invalid syntax to prevent security issues.
     */
    public function test_property_dangerous_formula_rejection(): void
    {
        $this->runPropertyTest(15, function () {
            $property = $this->generateRandomProperty();
            $utilityService = $this->generateRandomUtilityService();
            
            // Test dangerous formulas
            $dangerousFormulas = [
                'eval("malicious code")',
                'system("rm -rf /")',
                'exec("dangerous command")',
                'shell_exec("bad stuff")',
            ];
            
            $dangerousFormula = fake()->randomElement($dangerousFormulas);
            
            $assignmentData = $this->generateValidAssignmentData($property, $utilityService, [
                'pricing_model' => PricingModel::HYBRID,
                'custom_formula' => $dangerousFormula,
            ]);
            
            // Assert dangerous formula is rejected
            $this->expectException(ServiceConfigurationException::class);
            $this->assignAction->execute($assignmentData);
        });
    }

    /**
     * Property: Audit trail creation
     * 
     * Every service assignment should create appropriate audit records
     * for tracking changes and compliance.
     */
    public function test_property_audit_trail_creation(): void
    {
        $this->runPropertyTest(30, function () {
            $property = $this->generateRandomProperty();
            $utilityService = $this->generateRandomUtilityService();
            $assignmentData = $this->generateValidAssignmentData($property, $utilityService);
            
            // Act
            $configuration = $this->assignAction->execute($assignmentData);
            
            // Assert configuration exists and is auditable
            $this->assertNotNull($configuration->id);
            $this->assertNotNull($configuration->created_at);
            $this->assertNotNull($configuration->updated_at);
            
            // Assert tenant isolation
            $this->assertEquals($this->tenant->id, $configuration->tenant_id);
            $this->assertEquals($property->tenant_id, $configuration->tenant_id);
        });
    }

    /**
     * Property: Configuration consistency
     * 
     * For the same inputs, service assignment should always
     * produce consistent results (deterministic behavior).
     */
    public function test_property_configuration_consistency(): void
    {
        $this->runPropertyTest(20, function () {
            $property = $this->generateRandomProperty();
            $utilityService = $this->generateRandomUtilityService();
            
            // Create identical assignment data
            $assignmentData1 = $this->generateValidAssignmentData($property, $utilityService, [
                'effective_from' => now()->addDay(),
                'effective_until' => now()->addMonth(),
            ]);
            
            $assignmentData2 = $this->generateValidAssignmentData($property, $utilityService, [
                'effective_from' => now()->addMonths(2),
                'effective_until' => now()->addMonths(3),
            ]);
            
            // Act - create two configurations with same data structure
            $config1 = $this->assignAction->execute($assignmentData1);
            $config2 = $this->assignAction->execute($assignmentData2);
            
            // Assert consistent structure
            $this->assertEquals($config1->pricing_model, $config2->pricing_model);
            $this->assertEquals($config1->is_shared_service, $config2->is_shared_service);
            $this->assertEquals($config1->property_id, $config2->property_id);
            $this->assertEquals($config1->utility_service_id, $config2->utility_service_id);
            
            // Assert different IDs (separate records)
            $this->assertNotEquals($config1->id, $config2->id);
        });
    }

    /**
     * Property: Input validation completeness
     * 
     * The system should validate all required fields and
     * reject incomplete or invalid assignment data.
     */
    public function test_property_input_validation_completeness(): void
    {
        $this->runPropertyTest(25, function () {
            $property = $this->generateRandomProperty();
            $utilityService = $this->generateRandomUtilityService();
            
            // Test with missing required fields
            $incompleteData = AssignUtilityServiceDTO::fromArray([
                'property_id' => $property->id,
                'utility_service_id' => $utilityService->id,
                // Missing pricing_model - should cause validation error
                'is_shared_service' => false,
                'is_active' => true,
            ]);
            
            // Assert validation catches missing required fields
            $this->expectException(\Exception::class);
            $this->assignAction->execute($incompleteData);
        });
    }

    // Helper methods for generating test data

    private function generateRandomProperty(): Property
    {
        $building = Building::factory()->create(['tenant_id' => $this->tenant->id]);
        
        return Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'building_id' => $building->id,
            'area_sqm' => fake()->randomFloat(2, 30, 200),
        ]);
    }

    private function generateRandomPropertyWithArea(): Property
    {
        $building = Building::factory()->create(['tenant_id' => $this->tenant->id]);
        
        return Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'building_id' => $building->id,
            'area_sqm' => fake()->randomFloat(2, 50, 150),
        ]);
    }

    private function generateRandomUtilityService(): UtilityService
    {
        return UtilityService::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
    }

    private function generateValidAssignmentData(
        Property $property,
        UtilityService $utilityService,
        array $overrides = []
    ): AssignUtilityServiceDTO {
        $baseData = [
            'property_id' => $property->id,
            'utility_service_id' => $utilityService->id,
            'pricing_model' => fake()->randomElement([
                PricingModel::FIXED_MONTHLY,
                PricingModel::CONSUMPTION_BASED,
                PricingModel::TIERED_RATES,
                PricingModel::HYBRID,
            ]),
            'rate_schedule' => [
                'unit_rate' => fake()->randomFloat(4, 0.1, 2.0),
                'base_fee' => fake()->randomFloat(2, 5, 50),
            ],
            'is_shared_service' => fake()->boolean(30), // 30% chance of shared service
            'effective_from' => fake()->dateTimeBetween('now', '+1 week'),
            'effective_until' => fake()->optional(0.7)->dateTimeBetween('+1 month', '+1 year'),
            'is_active' => true,
        ];
        
        // Add distribution method for shared services
        if ($baseData['is_shared_service']) {
            $baseData['distribution_method'] = fake()->randomElement([
                DistributionMethod::EQUAL,
                DistributionMethod::AREA,
                DistributionMethod::BY_CONSUMPTION,
            ]);
        }
        
        $data = array_merge($baseData, $overrides);
        
        return AssignUtilityServiceDTO::fromArray($data);
    }

    private function generateRandomBillingPeriod(): array
    {
        $startDate = Carbon::now()->subMonth()->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * Helper method to run property-based tests with specified iterations
     */
    private function runPropertyTest(int $iterations, callable $testFunction): void
    {
        for ($i = 0; $i < $iterations; $i++) {
            try {
                $testFunction();
            } catch (\Exception $e) {
                $this->fail("Property test failed on iteration {$i}: " . $e->getMessage());
            }
            
            // Clean up between iterations to avoid conflicts
            ServiceConfiguration::query()->delete();
        }
    }
}