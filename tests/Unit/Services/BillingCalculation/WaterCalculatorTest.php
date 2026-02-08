<?php

declare(strict_types=1);

namespace Tests\Unit\Services\BillingCalculation;

use App\Models\Meter;
use App\Models\Tariff;
use App\Services\BillingCalculation\WaterCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for WaterCalculator
 *
 * Tests:
 * - Water billing formula: Total = (consumption × supply_rate) + (consumption × sewage_rate) + fixed_fee
 * - Default rate fallbacks (supply: 0.97, sewage: 1.23, fixed: 0.85)
 * - Custom rates from tariff configuration
 * - Unit price calculation (supply_rate + sewage_rate)
 * - Math precision for billing amounts
 * - Edge cases: zero consumption, very high consumption, missing rates
 */
final class WaterCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private WaterCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new WaterCalculator();
    }

    /** @test */
    public function it_calculates_water_cost_with_default_rates(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = []; // No rates specified, should use defaults

        $consumption = 10.0; // m³
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Default: supply 0.97, sewage 1.23, fixed 0.85
        // Total = (10 × 0.97) + (10 × 1.23) + 0.85 = 9.7 + 12.3 + 0.85 = 22.85
        $this->assertEquals(22.85, $result['total']);

        // Unit price = 0.97 + 1.23 = 2.20
        $this->assertEquals(2.20, $result['unit_price']);
    }

    /** @test */
    public function it_calculates_water_cost_with_custom_rates(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 1.50,
            'sewage_rate' => 2.00,
            'fixed_fee' => 5.00,
        ];

        $consumption = 15.0; // m³
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Total = (15 × 1.50) + (15 × 2.00) + 5.00 = 22.5 + 30.0 + 5.0 = 57.5
        $this->assertEquals(57.5, $result['total']);

        // Unit price = 1.50 + 2.00 = 3.50
        $this->assertEquals(3.50, $result['unit_price']);
    }

    /** @test */
    public function it_calculates_realistic_monthly_water_consumption(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 0.97,
            'sewage_rate' => 1.23,
            'fixed_fee' => 0.85,
        ];

        // Realistic scenario: Average household uses 15 m³/month
        $consumption = 15.0;
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Total = (15 × 0.97) + (15 × 1.23) + 0.85 = 14.55 + 18.45 + 0.85 = 33.85
        $this->assertEquals(33.85, $result['total']);
        $this->assertEquals(2.20, $result['unit_price']);
    }

    /** @test */
    public function it_handles_zero_consumption(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 0.97,
            'sewage_rate' => 1.23,
            'fixed_fee' => 0.85,
        ];

        $consumption = 0.0;
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Total = (0 × 0.97) + (0 × 1.23) + 0.85 = 0 + 0 + 0.85 = 0.85
        // Only fixed fee should be charged
        $this->assertEquals(0.85, $result['total']);
        $this->assertEquals(2.20, $result['unit_price']);
    }

    /** @test */
    public function it_handles_very_small_consumption(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 0.97,
            'sewage_rate' => 1.23,
            'fixed_fee' => 0.85,
        ];

        $consumption = 0.5; // Half a cubic meter
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Total = (0.5 × 0.97) + (0.5 × 1.23) + 0.85 = 0.485 + 0.615 + 0.85 = 1.95
        $this->assertEqualsWithDelta(1.95, $result['total'], 0.01);
    }

    /** @test */
    public function it_handles_high_consumption(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 0.97,
            'sewage_rate' => 1.23,
            'fixed_fee' => 0.85,
        ];

        // High consumption scenario (e.g., commercial property)
        $consumption = 500.0;
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Total = (500 × 0.97) + (500 × 1.23) + 0.85 = 485 + 615 + 0.85 = 1100.85
        $this->assertEquals(1100.85, $result['total']);
        $this->assertEquals(2.20, $result['unit_price']);
    }

    /** @test */
    public function it_handles_decimal_consumption_values(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 0.97,
            'sewage_rate' => 1.23,
            'fixed_fee' => 0.85,
        ];

        $consumption = 12.345; // Precise meter reading
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Total = (12.345 × 0.97) + (12.345 × 1.23) + 0.85
        // = 11.97465 + 15.18435 + 0.85 = 28.009
        $this->assertEqualsWithDelta(28.009, $result['total'], 0.001);
    }

    /** @test */
    public function it_uses_config_defaults_when_tariff_rates_missing(): void
    {
        // Set config values
        config([
            'billing.water_tariffs.default_supply_rate' => 1.10,
            'billing.water_tariffs.default_sewage_rate' => 1.40,
            'billing.water_tariffs.default_fixed_fee' => 2.00,
        ]);

        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = []; // No rates in tariff

        $consumption = 10.0;
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Total = (10 × 1.10) + (10 × 1.40) + 2.00 = 11.0 + 14.0 + 2.0 = 27.0
        $this->assertEquals(27.0, $result['total']);

        // Unit price = 1.10 + 1.40 = 2.50
        $this->assertEquals(2.50, $result['unit_price']);
    }

    /** @test */
    public function it_handles_partial_rate_override(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 1.50, // Custom
            // sewage_rate and fixed_fee will use defaults
        ];

        $consumption = 10.0;
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Total = (10 × 1.50) + (10 × 1.23) + 0.85 = 15.0 + 12.3 + 0.85 = 28.15
        $this->assertEqualsWithDelta(28.15, $result['total'], 0.01);

        // Unit price = 1.50 + 1.23 = 2.73
        $this->assertEquals(2.73, $result['unit_price']);
    }

    /** @test */
    public function it_maintains_precision_for_billing(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 0.12345,
            'sewage_rate' => 0.67890,
            'fixed_fee' => 1.11111,
        ];

        $consumption = 87.654;
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Supply charge: 87.654 × 0.12345 = 10.8208863
        // Sewage charge: 87.654 × 0.67890 = 59.5069106
        // Total: 10.8208863 + 59.5069106 + 1.11111 = 71.4389069
        $this->assertEqualsWithDelta(71.44, $result['total'], 0.01);

        // Unit price: 0.12345 + 0.67890 = 0.80235
        $this->assertEqualsWithDelta(0.80235, $result['unit_price'], 0.00001);
    }

    /** @test */
    public function it_handles_zero_rates(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 0.0,
            'sewage_rate' => 0.0,
            'fixed_fee' => 0.0,
        ];

        $consumption = 10.0;
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // All zeros
        $this->assertEquals(0.0, $result['total']);
        $this->assertEquals(0.0, $result['unit_price']);
    }

    /** @test */
    public function it_handles_only_fixed_fee_without_variable_rates(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 0.0,
            'sewage_rate' => 0.0,
            'fixed_fee' => 15.00,
        ];

        $consumption = 100.0; // High consumption but zero rates
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Only fixed fee charged
        $this->assertEquals(15.00, $result['total']);
        $this->assertEquals(0.0, $result['unit_price']);
    }

    /** @test */
    public function it_calculates_correctly_for_different_periods(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 0.97,
            'sewage_rate' => 1.23,
            'fixed_fee' => 0.85,
        ];

        $consumption = 10.0;

        // Test different period dates - results should be the same
        // (water calculation doesn't depend on date)
        $result1 = $this->calculator->calculate($meter, $consumption, $tariff, Carbon::parse('2024-01-01'), null);
        $result2 = $this->calculator->calculate($meter, $consumption, $tariff, Carbon::parse('2024-06-15'), null);
        $result3 = $this->calculator->calculate($meter, $consumption, $tariff, Carbon::parse('2024-12-31'), null);

        $this->assertEquals($result1['total'], $result2['total']);
        $this->assertEquals($result1['total'], $result3['total']);
        $this->assertEquals(22.85, $result1['total']);
    }

    /** @test */
    public function it_returns_both_unit_price_and_total_in_result(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 1.00,
            'sewage_rate' => 2.00,
            'fixed_fee' => 5.00,
        ];

        $consumption = 10.0;
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Verify structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('unit_price', $result);
        $this->assertArrayHasKey('total', $result);

        // Verify values
        $this->assertEquals(3.00, $result['unit_price']); // 1.00 + 2.00
        $this->assertEquals(35.00, $result['total']); // (10 × 1.00) + (10 × 2.00) + 5.00
    }

    /** @test */
    public function it_handles_negative_consumption_as_mathematical_operation(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 0.97,
            'sewage_rate' => 1.23,
            'fixed_fee' => 0.85,
        ];

        // Edge case: negative consumption (meter rollback or correction)
        $consumption = -5.0;
        $periodStart = Carbon::parse('2024-01-01');

        $result = $this->calculator->calculate($meter, $consumption, $tariff, $periodStart, null);

        // Total = (-5 × 0.97) + (-5 × 1.23) + 0.85 = -4.85 + -6.15 + 0.85 = -10.15
        // Mathematical result (business logic should prevent this scenario)
        $this->assertEquals(-10.15, $result['total']);
    }

    /** @test */
    public function it_calculates_for_hot_water_and_cold_water_separately(): void
    {
        $meter = new Meter();
        $tariff = new Tariff();
        $tariff->configuration = [
            'supply_rate' => 0.97,
            'sewage_rate' => 1.23,
            'fixed_fee' => 0.85,
        ];

        // Cold water
        $coldWaterConsumption = 8.0;
        $resultCold = $this->calculator->calculate($meter, $coldWaterConsumption, $tariff, Carbon::parse('2024-01-01'), null);

        // Hot water
        $hotWaterConsumption = 7.0;
        $resultHot = $this->calculator->calculate($meter, $hotWaterConsumption, $tariff, Carbon::parse('2024-01-01'), null);

        // Combined
        $totalConsumption = $coldWaterConsumption + $hotWaterConsumption;
        $resultCombined = $this->calculator->calculate($meter, $totalConsumption, $tariff, Carbon::parse('2024-01-01'), null);

        // Cold: (8 × 0.97) + (8 × 1.23) + 0.85 = 7.76 + 9.84 + 0.85 = 18.45
        $this->assertEqualsWithDelta(18.45, $resultCold['total'], 0.01);

        // Hot: (7 × 0.97) + (7 × 1.23) + 0.85 = 6.79 + 8.61 + 0.85 = 16.25
        $this->assertEqualsWithDelta(16.25, $resultHot['total'], 0.01);

        // Combined: (15 × 0.97) + (15 × 1.23) + 0.85 = 14.55 + 18.45 + 0.85 = 33.85
        $this->assertEqualsWithDelta(33.85, $resultCombined['total'], 0.01);

        // Note: Individual totals don't sum to combined total due to fixed fee
        // (18.45 + 16.25 = 34.70 vs 33.85 for combined)
    }
}
