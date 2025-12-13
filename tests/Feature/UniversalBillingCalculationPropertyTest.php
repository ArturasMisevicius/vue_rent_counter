<?php

declare(strict_types=1);

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\Models\UtilityService;
use App\Services\UniversalBillingCalculator;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\UniversalConsumptionData;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Feature: universal-utility-management, Property 4: Billing Calculation Accuracy
 * Validates: Requirements 5.1, 5.2, 5.4, 5.5
 */
test('Universal billing calculations produce accurate and consistent results across all pricing models', function () {
    // Generate random tenant and property
    $tenant = Tenant::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    // Generate random pricing model
    $pricingModel = fake()->randomElement(PricingModel::cases());
    
    // Create utility service
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => $pricingModel,
        'is_active' => true,
    ]);
    
    // Generate rate schedule based on pricing model
    $rateSchedule = match ($pricingModel) {
        PricingModel::FIXED_MONTHLY => [
            'monthly_rate' => fake()->randomFloat(2, 10, 500),
        ],
        PricingModel::CONSUMPTION_BASED => [
            'unit_rate' => fake()->randomFloat(4, 0.01, 5.0),
        ],
        PricingModel::TIERED_RATES => [
            'tiers' => [
                ['limit' => 100, 'rate' => fake()->randomFloat(4, 0.01, 1.0)],
                ['limit' => 500, 'rate' => fake()->randomFloat(4, 1.0, 2.0)],
                ['limit' => PHP_FLOAT_MAX, 'rate' => fake()->randomFloat(4, 2.0, 5.0)],
            ],
        ],
        PricingModel::HYBRID => [
            'fixed_fee' => fake()->randomFloat(2, 5, 100),
            'unit_rate' => fake()->randomFloat(4, 0.01, 2.0),
        ],
        PricingModel::TIME_OF_USE => [
            'zone_rates' => [
                'day' => fake()->randomFloat(4, 0.01, 2.0),
                'night' => fake()->randomFloat(4, 0.005, 1.0),
                'default' => fake()->randomFloat(4, 0.01, 1.5),
            ],
        ],
        PricingModel::CUSTOM_FORMULA => [
            'formula' => 'consumption * rate + base_fee',
            'variables' => [
                'rate' => fake()->randomFloat(4, 0.01, 3.0),
                'base_fee' => fake()->randomFloat(2, 0, 100),
            ],
        ],
        default => [
            'rate' => fake()->randomFloat(4, 0.01, 2.0),
        ],
    };
    
    // Create service configuration
    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => $pricingModel,
        'rate_schedule' => $rateSchedule,
        'distribution_method' => fake()->randomElement(DistributionMethod::cases()),
        'is_shared_service' => fake()->boolean(),
        'effective_from' => now()->subDays(fake()->numberBetween(1, 30)),
        'is_active' => true,
    ]);
    
    // Generate consumption data
    $totalConsumption = fake()->randomFloat(2, 0, 1000);
    $zoneConsumption = [];
    
    if ($pricingModel === PricingModel::TIME_OF_USE) {
        $dayConsumption = fake()->randomFloat(2, 0, $totalConsumption * 0.7);
        $nightConsumption = $totalConsumption - $dayConsumption;
        $zoneConsumption = [
            'day' => $dayConsumption,
            'night' => $nightConsumption,
        ];
    }
    
    $consumption = new UniversalConsumptionData(
        totalConsumption: $totalConsumption,
        zoneConsumption: $zoneConsumption,
    );
    
    // Generate billing period
    $startDate = Carbon::now()->subMonth()->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();
    $billingPeriod = new BillingPeriod($startDate, $endDate);
    
    // Get the calculator service
    $calculator = app(UniversalBillingCalculator::class);
    
    // Property: Billing calculation should produce accurate results
    $result = $calculator->calculateBill($serviceConfiguration, $consumption, $billingPeriod);
    
    // Verify result structure
    expect($result)->toBeInstanceOf(\App\ValueObjects\UniversalCalculationResult::class);
    expect($result->totalAmount)->toBeFloat();
    expect($result->baseAmount)->toBeFloat();
    expect($result->adjustments)->toBeArray();
    expect($result->consumptionAmount)->toBeFloat();
    expect($result->fixedAmount)->toBeFloat();
    expect($result->tariffSnapshot)->toBeArray();
    expect($result->calculationDetails)->toBeArray();
    
    // Verify monetary values are non-negative
    expect($result->totalAmount)->toBeGreaterThanOrEqual(0);
    expect($result->baseAmount)->toBeGreaterThanOrEqual(0);
    expect($result->consumptionAmount)->toBeGreaterThanOrEqual(0);
    expect($result->fixedAmount)->toBeGreaterThanOrEqual(0);
    
    // Verify calculation accuracy based on pricing model
    switch ($pricingModel) {
        case PricingModel::FIXED_MONTHLY:
            // Fixed monthly should not depend on consumption
            expect($result->consumptionAmount)->toBe(0.0);
            expect($result->fixedAmount)->toBeGreaterThan(0);
            expect($result->totalAmount)->toBeGreaterThan(0);
            break;
            
        case PricingModel::CONSUMPTION_BASED:
            // Consumption-based should have consumption charges
            if ($totalConsumption > 0) {
                expect($result->consumptionAmount)->toBeGreaterThan(0);
                expect($result->fixedAmount)->toBe(0.0);
                
                // Verify calculation accuracy
                $expectedAmount = $totalConsumption * $rateSchedule['unit_rate'];
                expect(abs($result->totalAmount - $expectedAmount))->toBeLessThan(0.01);
            }
            break;
            
        case PricingModel::TIERED_RATES:
            // Tiered rates should have consumption charges
            if ($totalConsumption > 0) {
                expect($result->consumptionAmount)->toBeGreaterThan(0);
                expect($result->fixedAmount)->toBe(0.0);
                
                // Verify tier breakdown exists
                expect($result->calculationDetails)->toHaveKey('tier_breakdown');
                expect($result->calculationDetails['tier_breakdown'])->toBeArray();
            }
            break;
            
        case PricingModel::HYBRID:
            // Hybrid should have both fixed and consumption components
            expect($result->fixedAmount)->toBeGreaterThan(0);
            if ($totalConsumption > 0) {
                expect($result->consumptionAmount)->toBeGreaterThan(0);
            }
            
            // Verify total is sum of components plus adjustments
            $expectedTotal = $result->fixedAmount + $result->consumptionAmount + $result->getTotalAdjustments();
            expect(abs($result->totalAmount - $expectedTotal))->toBeLessThan(0.01);
            break;
            
        case PricingModel::TIME_OF_USE:
            // Time-of-use should have zone breakdown
            if ($totalConsumption > 0) {
                expect($result->consumptionAmount)->toBeGreaterThan(0);
                expect($result->calculationDetails)->toHaveKey('zone_breakdown');
                expect($result->calculationDetails['zone_breakdown'])->toBeArray();
            }
            break;
    }
    
    // Verify tariff snapshot contains required data
    expect($result->tariffSnapshot)->toHaveKey('service_configuration_id');
    expect($result->tariffSnapshot)->toHaveKey('pricing_model');
    expect($result->tariffSnapshot)->toHaveKey('rate_schedule');
    expect($result->tariffSnapshot)->toHaveKey('snapshot_created_at');
    
    // Verify calculation details contain pricing model
    expect($result->calculationDetails)->toHaveKey('pricing_model');
    expect($result->calculationDetails['pricing_model'])->toBe($pricingModel->value);
    
    // Property: Calculation should be deterministic (same inputs = same outputs)
    $result2 = $calculator->calculateBill($serviceConfiguration, $consumption, $billingPeriod);
    
    expect($result2->totalAmount)->toBe($result->totalAmount);
    expect($result2->baseAmount)->toBe($result->baseAmount);
    expect($result2->consumptionAmount)->toBe($result->consumptionAmount);
    expect($result2->fixedAmount)->toBe($result->fixedAmount);
    
    // Property: Zero consumption should result in appropriate charges
    $zeroConsumption = new UniversalConsumptionData(0.0);
    $zeroResult = $calculator->calculateBill($serviceConfiguration, $zeroConsumption, $billingPeriod);
    
    expect($zeroResult->consumptionAmount)->toBe(0.0);
    
    // For fixed models, total should still be positive
    if ($pricingModel->hasFixedComponents()) {
        expect($zeroResult->totalAmount)->toBeGreaterThan(0);
    } else {
        // For pure consumption models, zero consumption should result in zero charges
        expect($zeroResult->totalAmount)->toBe(0.0);
    }
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 4: Billing Calculation Accuracy
 * Validates: Requirements 5.1, 5.2, 5.4, 5.5
 */
test('Billing calculations handle edge cases and maintain precision', function () {
    // Generate random tenant and property
    $tenant = Tenant::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    // Test with consumption-based pricing for precision testing
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'is_active' => true,
    ]);
    
    // Generate rate with high precision
    $unitRate = fake()->randomFloat(6, 0.000001, 0.999999);
    
    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => $unitRate],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subDays(1),
        'is_active' => true,
    ]);
    
    $billingPeriod = new BillingPeriod(
        Carbon::now()->startOfMonth(),
        Carbon::now()->endOfMonth()
    );
    
    $calculator = app(UniversalBillingCalculator::class);
    
    // Property: Very small consumption values should be handled correctly
    $smallConsumption = fake()->randomFloat(6, 0.000001, 0.001);
    $consumption = new UniversalConsumptionData($smallConsumption);
    
    $result = $calculator->calculateBill($serviceConfiguration, $consumption, $billingPeriod);
    
    expect($result->totalAmount)->toBeGreaterThan(0);
    expect($result->consumptionAmount)->toBeGreaterThan(0);
    
    // Verify precision is maintained
    $expectedAmount = $smallConsumption * $unitRate;
    expect(abs($result->totalAmount - $expectedAmount))->toBeLessThan(0.001);
    
    // Property: Large consumption values should be handled correctly
    $largeConsumption = fake()->randomFloat(2, 10000, 99999);
    $largeConsumptionData = new UniversalConsumptionData($largeConsumption);
    
    $largeResult = $calculator->calculateBill($serviceConfiguration, $largeConsumptionData, $billingPeriod);
    
    expect($largeResult->totalAmount)->toBeGreaterThan(0);
    expect($largeResult->consumptionAmount)->toBeGreaterThan(0);
    
    // Verify calculation scales correctly
    $expectedLargeAmount = $largeConsumption * $unitRate;
    expect(abs($largeResult->totalAmount - $expectedLargeAmount))->toBeLessThan(0.01);
    
    // Property: Calculation should scale linearly for consumption-based pricing
    $ratio = $largeConsumption / $smallConsumption;
    $amountRatio = $largeResult->totalAmount / $result->totalAmount;
    
    // Allow for small rounding differences
    expect(abs($ratio - $amountRatio))->toBeLessThan(0.01);
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 4: Billing Calculation Accuracy
 * Validates: Requirements 5.3, 5.4
 */
test('Seasonal adjustments and pro-ration calculations work correctly', function () {
    // Generate random tenant and property
    $tenant = Tenant::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    // Test with fixed monthly pricing for seasonal adjustments
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => PricingModel::FIXED_MONTHLY,
        'is_active' => true,
    ]);
    
    $monthlyRate = fake()->randomFloat(2, 50, 500);
    $summerMultiplier = fake()->randomFloat(2, 0.8, 1.2);
    $winterMultiplier = fake()->randomFloat(2, 1.1, 1.5);
    
    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::FIXED_MONTHLY,
        'rate_schedule' => [
            'monthly_rate' => $monthlyRate,
            'seasonal_adjustments' => [
                'summer_multiplier' => $summerMultiplier,
                'winter_multiplier' => $winterMultiplier,
            ],
        ],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subDays(1),
        'is_active' => true,
    ]);
    
    $consumption = new UniversalConsumptionData(0.0); // Fixed pricing doesn't use consumption
    $calculator = app(UniversalBillingCalculator::class);
    
    // Property: Full month billing should apply seasonal adjustments correctly
    $fullMonthPeriod = new BillingPeriod(
        Carbon::create(2024, 7, 1), // July (summer)
        Carbon::create(2024, 7, 31)
    );
    
    $summerResult = $calculator->calculateBill($serviceConfiguration, $consumption, $fullMonthPeriod);
    
    expect($summerResult->totalAmount)->toBeGreaterThan(0);
    expect($summerResult->fixedAmount)->toBeGreaterThan(0);
    
    // Test winter period
    $winterPeriod = new BillingPeriod(
        Carbon::create(2024, 1, 1), // January (winter)
        Carbon::create(2024, 1, 31)
    );
    
    $winterResult = $calculator->calculateBill($serviceConfiguration, $consumption, $winterPeriod);
    
    expect($winterResult->totalAmount)->toBeGreaterThan(0);
    expect($winterResult->fixedAmount)->toBeGreaterThan(0);
    
    // Property: Partial month billing should pro-rate correctly
    $partialPeriod = new BillingPeriod(
        Carbon::create(2024, 7, 15), // Half month in July
        Carbon::create(2024, 7, 31)
    );
    
    $partialResult = $calculator->calculateBill($serviceConfiguration, $consumption, $partialPeriod);
    
    expect($partialResult->totalAmount)->toBeGreaterThan(0);
    expect($partialResult->totalAmount)->toBeLessThan($summerResult->totalAmount);
    
    // Verify pro-ration is approximately correct (17 days out of 31)
    $expectedRatio = 17 / 31;
    $actualRatio = $partialResult->totalAmount / $summerResult->totalAmount;
    
    expect(abs($expectedRatio - $actualRatio))->toBeLessThan(0.05); // Allow 5% tolerance
    
    // Property: Calculation details should indicate seasonal adjustment and pro-ration
    expect($partialResult->calculationDetails)->toHaveKey('pro_rated');
    expect($partialResult->calculationDetails['pro_rated'])->toBeTrue();
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 4: Billing Calculation Accuracy
 * Validates: Requirements 5.1, 5.5
 */
test('Time-of-use pricing calculations handle zone consumption correctly', function () {
    // Generate random tenant and property
    $tenant = Tenant::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => PricingModel::TIME_OF_USE,
        'is_active' => true,
    ]);
    
    $dayRate = fake()->randomFloat(4, 0.01, 2.0);
    $nightRate = fake()->randomFloat(4, 0.005, 1.0);
    $weekendRate = fake()->randomFloat(4, 0.008, 1.5);
    
    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::TIME_OF_USE,
        'rate_schedule' => [
            'zone_rates' => [
                'day' => $dayRate,
                'night' => $nightRate,
                'weekend' => $weekendRate,
                'default' => $dayRate,
            ],
        ],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subDays(1),
        'is_active' => true,
    ]);
    
    // Generate zone consumption data
    $dayConsumption = fake()->randomFloat(2, 10, 500);
    $nightConsumption = fake()->randomFloat(2, 5, 200);
    $weekendConsumption = fake()->randomFloat(2, 8, 300);
    
    $zoneConsumption = [
        'day' => $dayConsumption,
        'night' => $nightConsumption,
        'weekend' => $weekendConsumption,
    ];
    
    $totalConsumption = array_sum($zoneConsumption);
    
    $consumption = new UniversalConsumptionData(
        totalConsumption: $totalConsumption,
        zoneConsumption: $zoneConsumption,
    );
    
    $billingPeriod = new BillingPeriod(
        Carbon::now()->startOfMonth(),
        Carbon::now()->endOfMonth()
    );
    
    $calculator = app(UniversalBillingCalculator::class);
    
    // Property: Time-of-use calculation should apply correct rates to each zone
    $result = $calculator->calculateBill($serviceConfiguration, $consumption, $billingPeriod);
    
    expect($result->totalAmount)->toBeGreaterThan(0);
    expect($result->consumptionAmount)->toBeGreaterThan(0);
    expect($result->fixedAmount)->toBe(0.0);
    
    // Verify zone breakdown exists and is correct
    expect($result->calculationDetails)->toHaveKey('zone_breakdown');
    $zoneBreakdown = $result->calculationDetails['zone_breakdown'];
    
    expect($zoneBreakdown)->toBeArray();
    expect($zoneBreakdown)->toHaveKey('day');
    expect($zoneBreakdown)->toHaveKey('night');
    expect($zoneBreakdown)->toHaveKey('weekend');
    
    // Verify each zone calculation
    expect($zoneBreakdown['day']['consumption'])->toBe($dayConsumption);
    expect($zoneBreakdown['day']['rate'])->toBe($dayRate);
    expect(abs($zoneBreakdown['day']['amount'] - ($dayConsumption * $dayRate)))->toBeLessThan(0.01);
    
    expect($zoneBreakdown['night']['consumption'])->toBe($nightConsumption);
    expect($zoneBreakdown['night']['rate'])->toBe($nightRate);
    expect(abs($zoneBreakdown['night']['amount'] - ($nightConsumption * $nightRate)))->toBeLessThan(0.01);
    
    expect($zoneBreakdown['weekend']['consumption'])->toBe($weekendConsumption);
    expect($zoneBreakdown['weekend']['rate'])->toBe($weekendRate);
    expect(abs($zoneBreakdown['weekend']['amount'] - ($weekendConsumption * $weekendRate)))->toBeLessThan(0.01);
    
    // Property: Total amount should equal sum of zone amounts
    $expectedTotal = ($dayConsumption * $dayRate) + 
                    ($nightConsumption * $nightRate) + 
                    ($weekendConsumption * $weekendRate);
    
    expect(abs($result->totalAmount - $expectedTotal))->toBeLessThan(0.01);
    
    // Property: Single zone consumption should work correctly
    $singleZoneConsumption = new UniversalConsumptionData(
        totalConsumption: $dayConsumption,
        zoneConsumption: ['day' => $dayConsumption],
    );
    
    $singleZoneResult = $calculator->calculateBill($serviceConfiguration, $singleZoneConsumption, $billingPeriod);
    
    expect($singleZoneResult->totalAmount)->toBeGreaterThan(0);
    expect(abs($singleZoneResult->totalAmount - ($dayConsumption * $dayRate)))->toBeLessThan(0.01);
    
})->repeat(100);

/**
 * Feature: universal-utility-management, Property 4: Billing Calculation Accuracy
 * Validates: Requirements 5.2, 5.4
 */
test('Tiered rate calculations apply correct rates to consumption brackets', function () {
    // Generate random tenant and property
    $tenant = Tenant::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->id]);
    
    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $tenant->id,
        'default_pricing_model' => PricingModel::TIERED_RATES,
        'is_active' => true,
    ]);
    
    // Define tier structure
    $tier1Limit = fake()->numberBetween(50, 150);
    $tier2Limit = fake()->numberBetween(200, 400);
    $tier1Rate = fake()->randomFloat(4, 0.01, 1.0);
    $tier2Rate = fake()->randomFloat(4, 1.0, 2.0);
    $tier3Rate = fake()->randomFloat(4, 2.0, 5.0);
    
    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::TIERED_RATES,
        'rate_schedule' => [
            'tiers' => [
                ['limit' => $tier1Limit, 'rate' => $tier1Rate],
                ['limit' => $tier2Limit, 'rate' => $tier2Rate],
                ['limit' => PHP_FLOAT_MAX, 'rate' => $tier3Rate],
            ],
        ],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subDays(1),
        'is_active' => true,
    ]);
    
    $billingPeriod = new BillingPeriod(
        Carbon::now()->startOfMonth(),
        Carbon::now()->endOfMonth()
    );
    
    $calculator = app(UniversalBillingCalculator::class);
    
    // Property: Consumption within first tier should use only first tier rate
    $tier1Consumption = fake()->randomFloat(2, 1, $tier1Limit - 1);
    $tier1ConsumptionData = new UniversalConsumptionData($tier1Consumption);
    
    $tier1Result = $calculator->calculateBill($serviceConfiguration, $tier1ConsumptionData, $billingPeriod);
    
    expect($tier1Result->totalAmount)->toBeGreaterThan(0);
    expect($tier1Result->consumptionAmount)->toBeGreaterThan(0);
    
    $expectedTier1Amount = $tier1Consumption * $tier1Rate;
    expect(abs($tier1Result->totalAmount - $expectedTier1Amount))->toBeLessThan(0.01);
    
    // Verify tier breakdown
    expect($tier1Result->calculationDetails)->toHaveKey('tier_breakdown');
    $tier1Breakdown = $tier1Result->calculationDetails['tier_breakdown'];
    expect($tier1Breakdown)->toHaveCount(1);
    expect($tier1Breakdown[0]['consumption'])->toBe($tier1Consumption);
    expect($tier1Breakdown[0]['rate'])->toBe($tier1Rate);
    
    // Property: Consumption spanning multiple tiers should apply correct rates to each bracket
    $multiTierConsumption = $tier1Limit + fake()->randomFloat(2, 1, $tier2Limit - $tier1Limit - 1);
    $multiTierConsumptionData = new UniversalConsumptionData($multiTierConsumption);
    
    $multiTierResult = $calculator->calculateBill($serviceConfiguration, $multiTierConsumptionData, $billingPeriod);
    
    expect($multiTierResult->totalAmount)->toBeGreaterThan(0);
    expect($multiTierResult->totalAmount)->toBeGreaterThan($tier1Result->totalAmount);
    
    // Calculate expected amount manually
    $expectedMultiTierAmount = ($tier1Limit * $tier1Rate) + 
                              (($multiTierConsumption - $tier1Limit) * $tier2Rate);
    
    expect(abs($multiTierResult->totalAmount - $expectedMultiTierAmount))->toBeLessThan(0.01);
    
    // Verify tier breakdown for multi-tier consumption
    $multiTierBreakdown = $multiTierResult->calculationDetails['tier_breakdown'];
    expect($multiTierBreakdown)->toHaveCount(2);
    
    expect($multiTierBreakdown[0]['consumption'])->toBe($tier1Limit);
    expect($multiTierBreakdown[0]['rate'])->toBe($tier1Rate);
    
    expect($multiTierBreakdown[1]['consumption'])->toBe($multiTierConsumption - $tier1Limit);
    expect($multiTierBreakdown[1]['rate'])->toBe($tier2Rate);
    
    // Property: Very high consumption should use all tiers including the highest
    $highConsumption = $tier2Limit + fake()->randomFloat(2, 100, 1000);
    $highConsumptionData = new UniversalConsumptionData($highConsumption);
    
    $highResult = $calculator->calculateBill($serviceConfiguration, $highConsumptionData, $billingPeriod);
    
    expect($highResult->totalAmount)->toBeGreaterThan($multiTierResult->totalAmount);
    
    // Calculate expected amount for all three tiers
    $expectedHighAmount = ($tier1Limit * $tier1Rate) + 
                         (($tier2Limit - $tier1Limit) * $tier2Rate) + 
                         (($highConsumption - $tier2Limit) * $tier3Rate);
    
    expect(abs($highResult->totalAmount - $expectedHighAmount))->toBeLessThan(0.01);
    
    // Verify all three tiers are used
    $highBreakdown = $highResult->calculationDetails['tier_breakdown'];
    expect($highBreakdown)->toHaveCount(3);
    expect($highBreakdown[2]['rate'])->toBe($tier3Rate);
    
})->repeat(100);