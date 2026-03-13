<?php

declare(strict_types=1);

namespace Tests\Unit\Services\TariffCalculation;

use App\Models\Tariff;
use App\Services\TariffCalculation\FlatRateStrategy;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for FlatRateStrategy
 *
 * Tests:
 * - Basic flat rate calculations
 * - Math precision (decimal accuracy, rounding)
 * - Edge cases (zero consumption, high consumption)
 * - Strategy support detection
 * - Timestamp independence (flat rate ignores time)
 */
final class FlatRateStrategyTest extends TestCase
{
    private FlatRateStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new FlatRateStrategy();
    }

    /** @test */
    public function it_supports_flat_tariff_type(): void
    {
        $this->assertTrue($this->strategy->supports('flat'));
    }

    /** @test */
    public function it_does_not_support_time_of_use_tariff_type(): void
    {
        $this->assertFalse($this->strategy->supports('time_of_use'));
    }

    /** @test */
    public function it_does_not_support_unknown_tariff_types(): void
    {
        $this->assertFalse($this->strategy->supports('unknown'));
        $this->assertFalse($this->strategy->supports('tiered'));
        $this->assertFalse($this->strategy->supports(''));
    }

    /** @test */
    public function it_calculates_basic_flat_rate_cost(): void
    {
        $tariff = $this->createFlatRateTariff(0.15);
        $consumption = 100.0;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        $this->assertEquals(15.0, $cost);
    }

    /** @test */
    public function it_calculates_with_decimal_rate(): void
    {
        $tariff = $this->createFlatRateTariff(0.12345);
        $consumption = 100.0;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        $this->assertEquals(12.345, $cost);
    }

    /** @test */
    public function it_calculates_with_decimal_consumption(): void
    {
        $tariff = $this->createFlatRateTariff(0.15);
        $consumption = 123.45;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        $this->assertEquals(18.5175, $cost);
    }

    /** @test */
    public function it_handles_zero_consumption(): void
    {
        $tariff = $this->createFlatRateTariff(0.15);
        $consumption = 0.0;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        $this->assertEquals(0.0, $cost);
    }

    /** @test */
    public function it_handles_very_small_consumption(): void
    {
        $tariff = $this->createFlatRateTariff(0.15);
        $consumption = 0.01;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        $this->assertEquals(0.0015, $cost);
    }

    /** @test */
    public function it_handles_large_consumption(): void
    {
        $tariff = $this->createFlatRateTariff(0.15);
        $consumption = 10000.0;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        $this->assertEquals(1500.0, $cost);
    }

    /** @test */
    public function it_is_timestamp_independent(): void
    {
        $tariff = $this->createFlatRateTariff(0.15);
        $consumption = 100.0;

        $morningCost = $this->strategy->calculate($tariff, $consumption, Carbon::parse('2024-01-15 08:00:00'));
        $nightCost = $this->strategy->calculate($tariff, $consumption, Carbon::parse('2024-01-15 23:00:00'));
        $weekendCost = $this->strategy->calculate($tariff, $consumption, Carbon::parse('2024-01-20 12:00:00')); // Saturday

        $this->assertEquals($morningCost, $nightCost);
        $this->assertEquals($morningCost, $weekendCost);
        $this->assertEquals(15.0, $morningCost);
    }

    /** @test */
    public function it_handles_zero_rate(): void
    {
        $tariff = $this->createFlatRateTariff(0.0);
        $consumption = 100.0;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        $this->assertEquals(0.0, $cost);
    }

    /** @test */
    public function it_calculates_with_high_precision_rate(): void
    {
        // Test with 5 decimal places
        $tariff = $this->createFlatRateTariff(0.12345);
        $consumption = 87.654;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        // Expected: 0.12345 × 87.654 = 10.8208863 (PHP floating-point result)
        // Use delta for float comparison
        $this->assertEqualsWithDelta(10.82088, $cost, 0.0001);
    }

    /** @test */
    public function it_maintains_precision_for_billing_amounts(): void
    {
        // Real-world scenario: electricity at €0.15/kWh for 234.56 kWh
        $tariff = $this->createFlatRateTariff(0.15);
        $consumption = 234.56;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        // Expected: 0.15 × 234.56 = 35.184
        $this->assertEquals(35.184, $cost);

        // Verify it can be rounded to 2 decimal places for invoicing
        $this->assertEquals(35.18, round($cost, 2));
    }

    /** @test */
    public function it_handles_realistic_monthly_electricity_consumption(): void
    {
        // Average household: 300 kWh at €0.15/kWh
        $tariff = $this->createFlatRateTariff(0.15);
        $consumption = 300.0;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        $this->assertEquals(45.0, $cost);
    }

    /** @test */
    public function it_handles_realistic_water_consumption(): void
    {
        // Average household: 15 m³ at €2.20/m³ (supply + sewage combined)
        $tariff = $this->createFlatRateTariff(2.20);
        $consumption = 15.0;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        $this->assertEquals(33.0, $cost);
    }

    /** @test */
    public function it_calculates_negative_consumption_as_mathematical_operation(): void
    {
        // Edge case: negative consumption (e.g., meter rollback or correction)
        $tariff = $this->createFlatRateTariff(0.15);
        $consumption = -10.0;

        $cost = $this->strategy->calculate($tariff, $consumption, now());

        // Mathematical result (business logic should prevent this scenario)
        $this->assertEquals(-1.5, $cost);
    }

    /**
     * Helper to create a flat rate tariff mock.
     */
    private function createFlatRateTariff(float $rate): Tariff
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => 'flat',
            'rate' => $rate,
        ];

        return $tariff;
    }
}
