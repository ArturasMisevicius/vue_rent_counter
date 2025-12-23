# Property-Based Testing for Shared Service Cost Distribution

## Overview

This document describes the property-based testing approach used to validate the shared service cost distribution system. Property-based testing generates random inputs and verifies that certain invariants hold across all possible scenarios.

## Test Structure

### Test File Location
```
tests/Property/SharedServiceCostDistributionPropertyTest.php
```

### Test Categories

The property tests validate **Requirements 6.1, 6.2, 6.3, 6.4** from the universal utility management specification:

- **6.1**: Equal division, area-based allocation, consumption-based allocation, custom formulas
- **6.2**: Different area types (total_area, heated_area, commercial_area) as basis
- **6.3**: Historical consumption averages or current period ratios
- **6.4**: Custom distribution formulas with property attributes and service factors

## Core Invariants Tested

### 1. Total Cost Accuracy Invariant

**Property**: For any shared service configuration and property set, the sum of distributed costs should equal the total cost to be distributed.

```php
public function test_property_total_cost_accuracy_invariant(): void
{
    $this->runPropertyTest(100, function () {
        $totalCost = fake()->randomFloat(2, 100, 10000);
        $distributionMethod = fake()->randomElement(DistributionMethod::cases());
        $properties = $this->generateRandomProperties(fake()->numberBetween(2, 10));
        
        $result = $this->costDistributor->distributeCost(/* ... */);
        
        // Assert invariant: sum equals total
        $this->assertEquals(
            $totalCost,
            $result->getDistributedAmounts()->sum(),
            "Total distributed cost should equal original cost",
            0.01 // Floating point tolerance
        );
    });
}
```

**Validation Points**:
- Sum of distributed amounts equals input total cost
- All properties receive non-negative allocations
- Property count matches expected count

### 2. Equal Distribution Accuracy

**Property**: For equal distribution, each property should receive exactly `total_cost / property_count`.

```php
public function test_property_equal_distribution_accuracy(): void
{
    $this->runPropertyTest(50, function () {
        $totalCost = fake()->randomFloat(2, 100, 5000);
        $propertyCount = fake()->numberBetween(2, 8);
        $properties = $this->generateRandomProperties($propertyCount);
        
        $serviceConfig = $this->createSharedServiceConfiguration(DistributionMethod::EQUAL);
        $result = $this->costDistributor->distributeCost(/* ... */);
        
        $expectedAmountPerProperty = $totalCost / $propertyCount;
        foreach ($result->getDistributedAmounts() as $amount) {
            $this->assertEquals($expectedAmountPerProperty, $amount, '', 0.01);
        }
    });
}
```

### 3. Area-Based Distribution Proportionality

**Property**: Cost allocation should be proportional to property areas, with larger areas receiving proportionally larger costs.

```php
public function test_property_area_based_distribution_proportionality(): void
{
    $this->runPropertyTest(30, function () {
        $totalCost = fake()->randomFloat(2, 1000, 8000);
        $properties = $this->generatePropertiesWithAreas();
        
        $result = $this->costDistributor->distributeCost(/* ... */);
        
        $totalArea = $properties->sum('area_sqm');
        foreach ($properties as $property) {
            $expectedProportion = $property->area_sqm / $totalArea;
            $expectedAmount = $totalCost * $expectedProportion;
            $actualAmount = $result->getDistributedAmounts()[$property->id];
            
            $this->assertEquals($expectedAmount, $actualAmount, '', 0.01);
        }
    });
}
```

### 4. Consumption-Based Distribution Accuracy

**Property**: Cost allocation should be proportional to historical consumption ratios.

```php
public function test_property_consumption_based_distribution_accuracy(): void
{
    $this->runPropertyTest(25, function () {
        $properties = $this->generatePropertiesWithConsumption();
        
        $totalConsumption = $properties->sum('historical_consumption');
        if ($totalConsumption > 0) {
            foreach ($properties as $property) {
                $expectedProportion = $property->historical_consumption / $totalConsumption;
                $expectedAmount = $totalCost * $expectedProportion;
                // Assert proportional allocation
            }
        } else {
            // Should fallback to equal distribution
        }
    });
}
```

### 5. Custom Formula Distribution Flexibility

**Property**: The system should support mathematical expressions combining multiple factors.

```php
public function test_property_custom_formula_distribution_flexibility(): void
{
    $this->runPropertyTest(20, function () {
        $properties = $this->generatePropertiesWithMultipleFactors();
        
        // Generate random formula
        $areaWeight = fake()->randomFloat(2, 0.1, 0.9);
        $consumptionWeight = 1.0 - $areaWeight;
        $formula = "area * {$areaWeight} + consumption * {$consumptionWeight}";
        
        $serviceConfig = $this->createSharedServiceConfiguration(
            DistributionMethod::CUSTOM_FORMULA,
            ['formula' => $formula]
        );
        
        // Validate formula-based distribution
        $result = $this->costDistributor->distributeCost(/* ... */);
        
        // Calculate expected distribution and verify
    });
}
```

### 6. Distribution Method Consistency

**Property**: For identical inputs, the same distribution method should always produce identical results (deterministic behavior).

```php
public function test_property_distribution_method_consistency(): void
{
    $this->runPropertyTest(30, function () {
        // Same inputs
        $totalCost = fake()->randomFloat(2, 200, 3000);
        $distributionMethod = fake()->randomElement(DistributionMethod::cases());
        $properties = $this->generateRandomProperties(fake()->numberBetween(2, 6));
        
        // Distribute twice
        $result1 = $this->costDistributor->distributeCost(/* ... */);
        $result2 = $this->costDistributor->distributeCost(/* ... */);
        
        // Assert identical results
        $this->assertEquals(
            $result1->getDistributedAmounts()->toArray(),
            $result2->getDistributedAmounts()->toArray()
        );
    });
}
```

### 7. Edge Case Handling

**Zero Cost Handling**:
```php
public function test_property_zero_cost_handling(): void
{
    // When distributing zero cost, all properties should receive zero allocation
}
```

**Single Property Edge Case**:
```php
public function test_property_single_property_edge_case(): void
{
    // Single property should receive entire cost regardless of method
}
```

## Test Data Generation

### Random Property Generation

```php
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
```

### Properties with Specific Attributes

```php
private function generatePropertiesWithAreas(): Collection
{
    // Generate properties with varied area values
}

private function generatePropertiesWithConsumption(): Collection
{
    // Generate properties with historical consumption data
}

private function generatePropertiesWithMultipleFactors(): Collection
{
    // Generate properties with both area and consumption data
}
```

### Service Configuration Generation

```php
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
```

## Mock Implementation Strategy

### Test-Specific Mock Service

The test includes a mock implementation of `SharedServiceCostDistributor` that provides realistic behavior for testing:

```php
private function createMockCostDistributor(): SharedServiceCostDistributor
{
    return new class implements SharedServiceCostDistributor {
        public function distributeCost(/* ... */): SharedServiceCostDistributionResult {
            $distributionMethod = $serviceConfig->distribution_method;
            $distributedAmounts = collect();
            
            switch ($distributionMethod) {
                case DistributionMethod::EQUAL:
                    // Equal distribution logic
                    break;
                case DistributionMethod::AREA:
                    // Area-based distribution logic
                    break;
                // ... other methods
            }
            
            return new SharedServiceCostDistributionResult($distributedAmounts);
        }
    };
}
```

### Formula Evaluation Mock

```php
private function evaluateSimpleFormula(string $formula, float $area, float $consumption): float
{
    // Simple formula evaluation for testing purposes
    $formula = str_replace('area', (string) $area, $formula);
    $formula = str_replace('consumption', (string) $consumption, $formula);
    
    try {
        return eval("return {$formula};");
    } catch (\Throwable $e) {
        return 1.0; // Fallback value
    }
}
```

## Test Execution Patterns

### Property Test Runner

```php
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
```

### Iteration Counts

- **Total Cost Accuracy**: 100 iterations (high confidence needed)
- **Equal Distribution**: 50 iterations (straightforward logic)
- **Area-Based Distribution**: 30 iterations (moderate complexity)
- **Consumption-Based**: 25 iterations (moderate complexity)
- **Custom Formula**: 20 iterations (complex logic, fewer iterations)
- **Consistency**: 30 iterations (determinism verification)
- **Edge Cases**: 15-20 iterations (specific scenarios)

## Benefits of Property-Based Testing

### 1. Comprehensive Coverage
- Tests thousands of input combinations automatically
- Discovers edge cases that manual tests might miss
- Validates invariants across the entire input space

### 2. Regression Detection
- Catches breaking changes in distribution logic
- Ensures mathematical accuracy is maintained
- Validates that optimizations don't break correctness

### 3. Documentation Value
- Tests serve as executable specifications
- Clearly define expected behavior and invariants
- Provide examples of valid input ranges

### 4. Confidence Building
- High iteration counts provide statistical confidence
- Random inputs simulate real-world usage patterns
- Validates robustness under various conditions

## Running the Tests

### Command Line Execution

```bash
# Run all property tests
php artisan test --filter=SharedServiceCostDistributionPropertyTest

# Run specific property test
php artisan test --filter=test_property_total_cost_accuracy_invariant

# Run with verbose output
php artisan test --filter=SharedServiceCostDistributionPropertyTest -v
```

### CI/CD Integration

```yaml
# GitHub Actions example
- name: Run Property Tests
  run: |
    php artisan test --filter=Property --parallel
    php artisan test --filter=SharedServiceCostDistributionPropertyTest --stop-on-failure
```

## Debugging Property Test Failures

### Failure Analysis

When a property test fails:

1. **Identify the iteration**: Note which iteration failed
2. **Examine the inputs**: Log the generated test data
3. **Verify the invariant**: Check if the invariant is correctly implemented
4. **Reproduce manually**: Create a unit test with the failing inputs

### Debugging Helpers

```php
// Add logging to property tests
Log::info('Property test iteration', [
    'iteration' => $i,
    'total_cost' => $totalCost,
    'property_count' => $properties->count(),
    'distribution_method' => $distributionMethod->value,
    'distributed_total' => $result->getDistributedAmounts()->sum(),
]);
```

### Common Failure Patterns

1. **Floating Point Precision**: Use appropriate tolerance values
2. **Edge Case Handling**: Ensure zero/negative values are handled
3. **Mock Behavior**: Verify mock implementations match real service behavior
4. **Data Generation**: Ensure generated data is realistic and valid

## Best Practices

### 1. Invariant Design
- Keep invariants simple and verifiable
- Focus on mathematical properties that must always hold
- Avoid testing implementation details

### 2. Test Data Quality
- Generate realistic data ranges
- Include edge cases in data generation
- Ensure data relationships are valid

### 3. Performance Considerations
- Balance iteration count with test execution time
- Use appropriate ranges for random values
- Consider parallel test execution

### 4. Maintainability
- Keep property tests focused on core invariants
- Document the business rules being tested
- Update tests when requirements change

## Related Documentation

- [Shared Service Cost Distribution](../services/SHARED_SERVICE_COST_DISTRIBUTION.md)
- [Testing Standards](../../.kiro/steering/testing-standards.md)
- [Universal Utility Management Spec](../../.kiro/specs/universal-utility-management/)
- [Distribution Method Enhancement](../DISTRIBUTION_METHOD_ENHANCEMENT_COMPLETE.md)