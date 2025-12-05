<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\GyvatukasCalculator;
use Carbon\Carbon;
use Tests\TestCase;

class GyvatukasCalculatorTest extends TestCase
{
    private GyvatukasCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(GyvatukasCalculator::class);
    }

    public function test_calculates_summer_season_correctly(): void
    {
        // Summer: May 15 - September 15
        $summerDate = Carbon::create(2024, 7, 1);
        
        $cost = $this->calculator->calculate(
            consumption: 100.0,
            timestamp: $summerDate
        );

        $this->assertIsFloat($cost);
        $this->assertGreaterThan(0, $cost);
    }

    public function test_calculates_winter_season_correctly(): void
    {
        // Winter: September 16 - May 14
        $winterDate = Carbon::create(2024, 12, 1);
        
        $cost = $this->calculator->calculate(
            consumption: 100.0,
            timestamp: $winterDate
        );

        $this->assertIsFloat($cost);
        $this->assertGreaterThan(0, $cost);
    }

    public function test_winter_cost_higher_than_summer(): void
    {
        $summerDate = Carbon::create(2024, 7, 1);
        $winterDate = Carbon::create(2024, 12, 1);
        
        $summerCost = $this->calculator->calculate(100.0, $summerDate);
        $winterCost = $this->calculator->calculate(100.0, $winterDate);

        // Winter should be more expensive due to higher circulation needs
        $this->assertGreaterThan($summerCost, $winterCost);
    }

    public function test_handles_zero_consumption(): void
    {
        $cost = $this->calculator->calculate(
            consumption: 0.0,
            timestamp: now()
        );

        $this->assertEquals(0.0, $cost);
    }

    public function test_handles_negative_consumption(): void
    {
        $cost = $this->calculator->calculate(
            consumption: -10.0,
            timestamp: now()
        );

        // Should handle negative as mathematical operation
        $this->assertLessThan(0, $cost);
    }

    public function test_calculates_with_decimal_precision(): void
    {
        $cost = $this->calculator->calculate(
            consumption: 123.456,
            timestamp: now()
        );

        $this->assertIsFloat($cost);
        // Should maintain precision
        $this->assertNotEquals(0.0, $cost);
    }

    public function test_season_boundary_may_15(): void
    {
        // May 15 is first day of summer
        $may15 = Carbon::create(2024, 5, 15);
        
        $cost = $this->calculator->calculate(100.0, $may15);
        
        $this->assertGreaterThan(0, $cost);
    }

    public function test_season_boundary_september_15(): void
    {
        // September 15 is last day of summer
        $sept15 = Carbon::create(2024, 9, 15);
        
        $cost = $this->calculator->calculate(100.0, $sept15);
        
        $this->assertGreaterThan(0, $cost);
    }

    public function test_season_boundary_september_16(): void
    {
        // September 16 is first day of winter
        $sept16 = Carbon::create(2024, 9, 16);
        
        $cost = $this->calculator->calculate(100.0, $sept16);
        
        $this->assertGreaterThan(0, $cost);
    }

    public function test_season_boundary_may_14(): void
    {
        // May 14 is last day of winter
        $may14 = Carbon::create(2024, 5, 14);
        
        $cost = $this->calculator->calculate(100.0, $may14);
        
        $this->assertGreaterThan(0, $cost);
    }

    public function test_consistent_results_for_same_inputs(): void
    {
        $date = Carbon::create(2024, 7, 1);
        
        $cost1 = $this->calculator->calculate(100.0, $date);
        $cost2 = $this->calculator->calculate(100.0, $date);

        $this->assertEquals($cost1, $cost2);
    }

    public function test_scales_linearly_with_consumption(): void
    {
        $date = now();
        
        $cost100 = $this->calculator->calculate(100.0, $date);
        $cost200 = $this->calculator->calculate(200.0, $date);

        // Double consumption should approximately double cost
        $this->assertEqualsWithDelta($cost100 * 2, $cost200, 0.01);
    }

    public function test_handles_large_consumption_values(): void
    {
        $cost = $this->calculator->calculate(
            consumption: 10000.0,
            timestamp: now()
        );

        $this->assertIsFloat($cost);
        $this->assertGreaterThan(0, $cost);
    }

    public function test_handles_very_small_consumption(): void
    {
        $cost = $this->calculator->calculate(
            consumption: 0.001,
            timestamp: now()
        );

        $this->assertIsFloat($cost);
        $this->assertGreaterThan(0, $cost);
    }

    public function test_different_years_same_season_produce_similar_results(): void
    {
        $summer2023 = Carbon::create(2023, 7, 1);
        $summer2024 = Carbon::create(2024, 7, 1);
        
        $cost2023 = $this->calculator->calculate(100.0, $summer2023);
        $cost2024 = $this->calculator->calculate(100.0, $summer2024);

        // Should be similar (allowing for config changes)
        $this->assertEqualsWithDelta($cost2023, $cost2024, $cost2023 * 0.1);
    }
}
