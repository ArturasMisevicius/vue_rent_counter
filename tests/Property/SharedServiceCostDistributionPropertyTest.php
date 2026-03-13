<?php

declare(strict_types=1);

namespace Tests\Property;

use App\Contracts\SharedServiceCostDistributor;
use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Models\Building;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\Models\User;
use App\ValueObjects\BillingPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * **Feature: universal-utility-management, Property 5: Shared Service Cost Distribution**
 * 
 * Property-based tests for shared service cost distribution functionality.
 * 
 * Tests the invariant: "For any shared service configuration and property set, 
 * distributing costs should allocate charges according to the configured method 
 * and ensure total accuracy"
 * 
 * Validates Requirements 6.1, 6.2, 6.3, 6.4:
 * - 6.1: Equal division, area-based allocation, consumption-based allocation, custom formulas
 * - 6.2: Different area types (total_area, heated_area, commercial_area) as basis
 * - 6.3: Historical consumption averages or current period ratios
 * - 6.4: Custom distribution formulas with property attributes and service factors
 */
class SharedServiceCostDistributionPropertyTest extends TestCase
{
    use RefreshDatabase;

    private SharedServiceCostDistributor $costDistributor;
    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($this->user);
        
        // Use the real service from the container
        $this->costDistributor = app(SharedServiceCostDistributor::class);
    }

    /**
     * Property: Total cost accuracy across all distribution methods
     * 
     * For any shared service configuration and property set, the sum of 
     * distributed costs should equal the total cost to be distributed.
     */
    public function test_property_total_cost_accuracy_invariant(): void
    {
        $this->runPropertyTest(100, function () {
            // Generate random shared service configuration
            $totalCost = fake()->randomFloat(2, 100, 10000);
            $distributionMethod = fake()->randomElement([
                DistributionMethod::EQUAL,
                DistributionMethod::AREA,
                DistributionMethod::BY_CONSUMPTION,
            ]); // Skip CUSTOM_FORMULA for now to avoid complexity
            
            // Generate random set of properties (2-10 properties)
            $propertyCount = fake()->numberBetween(2, 10);
            $properties = $this->generateRandomProperties($propertyCount);
            
            $serviceConfig = $this->createSharedServiceConfiguration($distributionMethod);
            $billingPeriod = $this->generateRandomBillingPeriod();
            
            // Act - distribute the cost
            $result = $this->costDistributor->distributeCost(
                $serviceConfig,
                $properties,
                $totalCost,
                $billingPeriod
            );
            
            // Assert invariant: sum of distributed costs equals total cost
            $distributedTotal = $result->getDistributedAmounts()->sum();
            
            $this->assertEquals(
                $totalCost,
                $distributedTotal,
                "Total distributed cost should equal original cost. Method: {$distributionMethod->value}",
                0.01 // Allow small floating point differences
            );
            
            // Assert all properties received some allocation (unless zero cost)
            if ($totalCost > 0) {
                $this->assertCount(
                    $propertyCount,
                    $result->getDistributedAmounts(),
                    'All properties should receive cost allocation'
                );
                
                foreach ($result->getDistributedAmounts() as $amount) {
                    $this->assertGreaterThanOrEqual(
                        0,
                        $amount,
                        'No property should receive negative cost allocation'
                    );
                }
            }
        });
    }

    /**
     * Property: Equal distribution method accuracy
     * 
     * For equal distribution, each property should receive exactly 
     * total_cost / property_count (within rounding precision).
     */
    public function test_property_equal_distribution_accuracy(): void
    {
        $this->runPropertyTest(50, function () {
            $totalCost = fake()->randomFloat(2, 100, 5000);
            $propertyCount = fake()->numberBetween(2, 8);
            $properties = $this->generateRandomProperties($propertyCount);
            
            $serviceConfig = $this->createSharedServiceConfiguration(DistributionMethod::EQUAL);
            $billingPeriod = $this->generateRandomBillingPeriod();
            
            // Act
            $result = $this->costDistributor->distributeCost(
                $serviceConfig,
                $properties,
                $totalCost,
                $billingPeriod
            );
            
            // Assert equal distribution
            $expectedAmountPerProperty = $totalCost / $propertyCount;
            $distributedAmounts = $result->getDistributedAmounts();
            
            foreach ($distributedAmounts as $propertyId => $amount) {
                $this->assertEquals(
                    $expectedAmountPerProperty,
                    $amount,
                    "Each property should receive equal share for EQUAL distribution method",
                    0.01
                );
            }
        });
    }

    /**
     * Property: Area-based distribution proportionality
     * 
     * For area-based distribution, cost allocation should be proportional 
     * to property areas, and properties with larger areas should receive 
     * proportionally larger costs.
     */
    public function test_property_area_based_distribution_proportionality(): void
    {
        $this->runPropertyTest(30, function () {
            $totalCost = fake()->randomFloat(2, 1000, 8000);
            $properties = $this->generatePropertiesWithAreas();
            
            $serviceConfig = $this->createSharedServiceConfiguration(DistributionMethod::AREA);
            $billingPeriod = $this->generateRandomBillingPeriod();
            
            // Act
            $result = $this->costDistributor->distributeCost(
                $serviceConfig,
                $properties,
                $totalCost,
                $billingPeriod
            );
            
            // Calculate expected proportions
            $totalArea = $properties->sum('area_sqm');
            $distributedAmounts = $result->getDistributedAmounts();
            
            foreach ($properties as $property) {
                $expectedProportion = $property->area_sqm / $totalArea;
                $expectedAmount = $totalCost * $expectedProportion;
                $actualAmount = $distributedAmounts[$property->id];
                
                $this->assertEquals(
                    $expectedAmount,
                    $actualAmount,
                    "Property cost should be proportional to area. Property {$property->id} area: {$property->area_sqm}",
                    0.01
                );
            }
            
            // Assert larger areas get larger costs (if areas differ)
            $sortedByArea = $properties->sortBy('area_sqm');
            $firstProperty = $sortedByArea->first();
            $lastProperty = $sortedByArea->last();
            
            if ($firstProperty->area_sqm < $lastProperty->area_sqm) {
                $this->assertLessThan(
                    $distributedAmounts[$lastProperty->id],
                    $distributedAmounts[$firstProperty->id],
                    'Properties with larger areas should receive larger cost allocations'
                );
            }
        });
    }

    /**
     * Property: Consumption-based distribution accuracy
     * 
     * For consumption-based distribution, cost allocation should be 
     * proportional to historical consumption ratios.
     */
    public function test_property_consumption_based_distribution_accuracy(): void
    {
        $this->runPropertyTest(25, function () {
            $totalCost = fake()->randomFloat(2, 500, 6000);
            $properties = $this->generatePropertiesWithConsumption();
            
            $serviceConfig = $this->createSharedServiceConfiguration(DistributionMethod::BY_CONSUMPTION);
            $billingPeriod = $this->generateRandomBillingPeriod();
            
            // Act
            $result = $this->costDistributor->distributeCost(
                $serviceConfig,
                $properties,
                $totalCost,
                $billingPeriod
            );
            
            // Calculate expected proportions based on consumption
            $totalConsumption = $properties->sum('historical_consumption');
            $distributedAmounts = $result->getDistributedAmounts();
            
            if ($totalConsumption > 0) {
                foreach ($properties as $property) {
                    $expectedProportion = $property->historical_consumption / $totalConsumption;
                    $expectedAmount = $totalCost * $expectedProportion;
                    $actualAmount = $distributedAmounts[$property->id];
                    
                    $this->assertEquals(
                        $expectedAmount,
                        $actualAmount,
                        "Property cost should be proportional to consumption. Property {$property->id} consumption: {$property->historical_consumption}",
                        0.01
                    );
                }
            } else {
                // Fallback to equal distribution when no consumption data
                $expectedAmountPerProperty = $totalCost / $properties->count();
                foreach ($distributedAmounts as $amount) {
                    $this->assertEquals(
                        $expectedAmountPerProperty,
                        $amount,
                        'Should fallback to equal distribution when no consumption data',
                        0.01
                    );
                }
            }
        });
    }

    /**
     * Property: Custom formula distribution flexibility
     * 
     * For custom formula distribution, the system should support 
     * mathematical expressions combining multiple factors.
     */
    public function test_property_custom_formula_distribution_flexibility(): void
    {
        $this->runPropertyTest(20, function () {
            $totalCost = fake()->randomFloat(2, 800, 4000);
            $properties = $this->generatePropertiesWithMultipleFactors();
            
            // Use simple formulas that are safe to evaluate
            $formulas = [
                'area * 0.7 + consumption * 0.3',
                'area + consumption',
                'area * 2',
                'consumption * 1.5',
            ];
            $formula = fake()->randomElement($formulas);
            
            $serviceConfig = $this->createSharedServiceConfiguration(
                DistributionMethod::CUSTOM_FORMULA,
                ['formula' => $formula]
            );
            $billingPeriod = $this->generateRandomBillingPeriod();
            
            // Act
            $result = $this->costDistributor->distributeCost(
                $serviceConfig,
                $properties,
                $totalCost,
                $billingPeriod
            );
            
            // Assert that distribution was successful and balanced
            $this->assertTrue($result->isBalanced(), 'Custom formula distribution should be balanced');
            $this->assertEquals($totalCost, $result->getTotalDistributed(), 'Total should match', 0.01);
            
            // Assert all properties received some allocation
            foreach ($result->getDistributedAmounts() as $amount) {
                $this->assertGreaterThanOrEqual(0, $amount, 'No negative allocations');
            }
        });
    }

    /**
     * Property: Distribution method consistency
     * 
     * For the same inputs, the same distribution method should always 
     * produce identical results (deterministic behavior).
     */
    public function test_property_distribution_method_consistency(): void
    {
        $this->runPropertyTest(30, function () {
            $totalCost = fake()->randomFloat(2, 200, 3000);
            $distributionMethod = fake()->randomElement([
                DistributionMethod::EQUAL,
                DistributionMethod::AREA,
                DistributionMethod::BY_CONSUMPTION,
            ]);
            $properties = $this->generateRandomProperties(fake()->numberBetween(2, 6));
            
            $serviceConfig = $this->createSharedServiceConfiguration($distributionMethod);
            $billingPeriod = $this->generateRandomBillingPeriod();
            
            // Act - distribute cost twice with same inputs
            $result1 = $this->costDistributor->distributeCost(
                $serviceConfig,
                $properties,
                $totalCost,
                $billingPeriod
            );
            
            $result2 = $this->costDistributor->distributeCost(
                $serviceConfig,
                $properties,
                $totalCost,
                $billingPeriod
            );
            
            // Assert deterministic behavior
            $amounts1 = $result1->getDistributedAmounts();
            $amounts2 = $result2->getDistributedAmounts();
            
            $this->assertEquals(
                $amounts1->toArray(),
                $amounts2->toArray(),
                "Distribution method should produce consistent results for same inputs. Method: {$distributionMethod->value}"
            );
        });
    }

    /**
     * Property: Zero cost handling
     * 
     * When distributing zero cost, all properties should receive zero allocation.
     */
    public function test_property_zero_cost_handling(): void
    {
        $this->runPropertyTest(15, function () {
            $totalCost = 0.0;
            $distributionMethod = fake()->randomElement([
                DistributionMethod::EQUAL,
                DistributionMethod::AREA,
                DistributionMethod::BY_CONSUMPTION,
            ]);
            $properties = $this->generateRandomProperties(fake()->numberBetween(2, 5));
            
            $serviceConfig = $this->createSharedServiceConfiguration($distributionMethod);
            $billingPeriod = $this->generateRandomBillingPeriod();
            
            // Act
            $result = $this->costDistributor->distributeCost(
                $serviceConfig,
                $properties,
                $totalCost,
                $billingPeriod
            );
            
            // Assert all allocations are zero
            $distributedAmounts = $result->getDistributedAmounts();
            
            foreach ($distributedAmounts as $propertyId => $amount) {
                $this->assertEquals(
                    0.0,
                    $amount,
                    "Zero cost should result in zero allocation for all properties. Method: {$distributionMethod->value}"
                );
            }
        });
    }

    /**
     * Property: Single property edge case
     * 
     * When distributing cost to a single property, that property 
     * should receive the entire cost regardless of distribution method.
     */
    public function test_property_single_property_edge_case(): void
    {
        $this->runPropertyTest(20, function () {
            $totalCost = fake()->randomFloat(2, 50, 2000);
            $distributionMethod = fake()->randomElement([
                DistributionMethod::EQUAL,
                DistributionMethod::AREA,
                DistributionMethod::BY_CONSUMPTION,
            ]);
            $properties = $this->generateRandomProperties(1);
            
            $serviceConfig = $this->createSharedServiceConfiguration($distributionMethod);
            $billingPeriod = $this->generateRandomBillingPeriod();
            
            // Act
            $result = $this->costDistributor->distributeCost(
                $serviceConfig,
                $properties,
                $totalCost,
                $billingPeriod
            );
            
            // Assert single property gets entire cost
            $distributedAmounts = $result->getDistributedAmounts();
            $property = $properties->first();
            
            $this->assertCount(1, $distributedAmounts);
            $this->assertEquals(
                $totalCost,
                $distributedAmounts[$property->id],
                "Single property should receive entire cost. Method: {$distributionMethod->value}",
                0.01
            );
        });
    }

    /**
     * Test validation of properties for different distribution methods.
     */
    public function test_property_validation(): void
    {
        // Test area-based validation
        $properties = collect([
            Property::factory()->create(['tenant_id' => $this->tenant->id, 'area_sqm' => null]),
        ]);
        
        $serviceConfig = $this->createSharedServiceConfiguration(DistributionMethod::AREA);
        
        $errors = $this->costDistributor->validateProperties($serviceConfig, $properties);
        $this->assertNotEmpty($errors, 'Should have validation errors for missing area data');
        
        // Test consumption-based validation
        $serviceConfig = $this->createSharedServiceConfiguration(DistributionMethod::BY_CONSUMPTION);
        
        $errors = $this->costDistributor->validateProperties($serviceConfig, $properties);
        $this->assertNotEmpty($errors, 'Should have validation errors for missing consumption data');
    }

    // Helper methods for generating test data

    private function generateRandomProperties(int $count): Collection
    {
        $building = Building::factory()->create(['tenant_id' => $this->tenant->id]);
        
        return Property::factory()
            ->count($count)
            ->create([
                'tenant_id' => $this->tenant->id,
                'building_id' => $building->id,
                'area_sqm' => fake()->randomFloat(2, 20, 200),
            ]);
    }

    private function generatePropertiesWithAreas(): Collection
    {
        $building = Building::factory()->create(['tenant_id' => $this->tenant->id]);
        $propertyCount = fake()->numberBetween(3, 7);
        
        return Property::factory()
            ->count($propertyCount)
            ->create([
                'tenant_id' => $this->tenant->id,
                'building_id' => $building->id,
                'area_sqm' => fake()->randomFloat(2, 30, 150),
            ]);
    }

    private function generatePropertiesWithConsumption(): Collection
    {
        $building = Building::factory()->create(['tenant_id' => $this->tenant->id]);
        $propertyCount = fake()->numberBetween(3, 6);
        
        $properties = collect();
        for ($i = 0; $i < $propertyCount; $i++) {
            $property = Property::factory()->create([
                'tenant_id' => $this->tenant->id,
                'building_id' => $building->id,
                'area_sqm' => fake()->randomFloat(2, 40, 120),
            ]);
            
            // Add historical consumption as a dynamic property
            $property->historical_consumption = fake()->randomFloat(2, 100, 2000);
            $properties->push($property);
        }
        
        return $properties;
    }

    private function generatePropertiesWithMultipleFactors(): Collection
    {
        $building = Building::factory()->create(['tenant_id' => $this->tenant->id]);
        $propertyCount = fake()->numberBetween(3, 5);
        
        $properties = collect();
        for ($i = 0; $i < $propertyCount; $i++) {
            $property = Property::factory()->create([
                'tenant_id' => $this->tenant->id,
                'building_id' => $building->id,
                'area_sqm' => fake()->randomFloat(2, 50, 180),
            ]);
            
            // Add multiple factors for custom formula
            $property->historical_consumption = fake()->randomFloat(2, 200, 1500);
            $properties->push($property);
        }
        
        return $properties;
    }

    private function createSharedServiceConfiguration(
        DistributionMethod $distributionMethod,
        array $additionalConfig = []
    ): ServiceConfiguration {
        $rateSchedule = array_merge([
            'unit_rate' => fake()->randomFloat(4, 0.1, 2.0),
        ], $additionalConfig);
        
        return ServiceConfiguration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'pricing_model' => PricingModel::CONSUMPTION_BASED,
            'rate_schedule' => $rateSchedule,
            'distribution_method' => $distributionMethod,
            'is_shared_service' => true,
            'effective_from' => now()->subMonth(),
            'effective_until' => now()->addYear(),
        ]);
    }

    private function generateRandomBillingPeriod(): BillingPeriod
    {
        $startDate = Carbon::now()->subMonth()->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        return new BillingPeriod($startDate, $endDate);
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
        }
    }
}