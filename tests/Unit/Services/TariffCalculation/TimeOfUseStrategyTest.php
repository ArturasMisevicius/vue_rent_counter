<?php

declare(strict_types=1);

namespace Tests\Unit\Services\TariffCalculation;

use App\Models\Tariff;
use App\Services\TariffCalculation\TimeOfUseStrategy;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for TimeOfUseStrategy
 *
 * Tests:
 * - Time zone detection (day/night/peak)
 * - Weekend logic (apply_night_rate, apply_day_rate, apply_weekend_rate)
 * - Midnight crossing time ranges (23:00-07:00)
 * - Math precision for different rate tiers
 * - Edge cases (boundary times, missing zones)
 * - Strategy support detection
 */
final class TimeOfUseStrategyTest extends TestCase
{
    private TimeOfUseStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new TimeOfUseStrategy();
    }

    /** @test */
    public function it_supports_time_of_use_tariff_type(): void
    {
        $this->assertTrue($this->strategy->supports('time_of_use'));
    }

    /** @test */
    public function it_does_not_support_flat_tariff_type(): void
    {
        $this->assertFalse($this->strategy->supports('flat'));
    }

    /** @test */
    public function it_calculates_day_rate(): void
    {
        $tariff = $this->createTimeOfUseTariff();
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-15 14:00:00'); // Monday 14:00 - day rate

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Day rate: 0.20
        $this->assertEquals(20.0, $cost);
    }

    /** @test */
    public function it_calculates_night_rate(): void
    {
        $tariff = $this->createTimeOfUseTariff();
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-15 02:00:00'); // Monday 02:00 - night rate

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Night rate: 0.10
        $this->assertEquals(10.0, $cost);
    }

    /** @test */
    public function it_handles_time_at_zone_boundary_start(): void
    {
        $tariff = $this->createTimeOfUseTariff();
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-15 07:00:00'); // Exactly 07:00 - should be day rate

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Day rate starts at 07:00
        $this->assertEquals(20.0, $cost);
    }

    /** @test */
    public function it_handles_time_just_before_zone_boundary(): void
    {
        $tariff = $this->createTimeOfUseTariff();
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-15 06:59:00'); // Just before 07:00 - should be night rate

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Night rate (still before 07:00)
        $this->assertEquals(10.0, $cost);
    }

    /** @test */
    public function it_handles_midnight_crossing_range_after_midnight(): void
    {
        // Night rate: 23:00-07:00 (crosses midnight)
        $tariff = $this->createTimeOfUseTariff();
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-15 01:00:00'); // 01:00 - in night range

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Night rate: 0.10
        $this->assertEquals(10.0, $cost);
    }

    /** @test */
    public function it_handles_midnight_crossing_range_before_midnight(): void
    {
        // Night rate: 23:00-07:00 (crosses midnight)
        $tariff = $this->createTimeOfUseTariff();
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-15 23:30:00'); // 23:30 - in night range

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Night rate: 0.10
        $this->assertEquals(10.0, $cost);
    }

    /** @test */
    public function it_applies_weekend_night_rate_logic(): void
    {
        $tariff = $this->createTimeOfUseTariffWithWeekend('apply_night_rate');
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-20 14:00:00'); // Saturday 14:00

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Weekend applies night rate: 0.10
        $this->assertEquals(10.0, $cost);
    }

    /** @test */
    public function it_applies_weekend_day_rate_logic(): void
    {
        $tariff = $this->createTimeOfUseTariffWithWeekend('apply_day_rate');
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-20 02:00:00'); // Saturday 02:00 (normally night)

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Weekend applies day rate: 0.20
        $this->assertEquals(20.0, $cost);
    }

    /** @test */
    public function it_applies_dedicated_weekend_rate(): void
    {
        $tariff = $this->createTimeOfUseTariffWithWeekendZone();
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-21 12:00:00'); // Sunday 12:00

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Weekend rate: 0.12
        $this->assertEquals(12.0, $cost);
    }

    /** @test */
    public function it_uses_time_based_zones_when_no_weekend_logic_specified(): void
    {
        $tariff = $this->createTimeOfUseTariff(); // No weekend_logic
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-20 14:00:00'); // Saturday 14:00

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Falls back to time-based: 14:00 is day rate
        $this->assertEquals(20.0, $cost);
    }

    /** @test */
    public function it_falls_back_to_first_zone_if_no_match(): void
    {
        // Create tariff with incomplete time coverage
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => 'time_of_use',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.20],
                // Missing night zone
            ],
        ];

        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-15 01:00:00'); // 01:00 - no matching zone

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Falls back to first zone (day rate)
        $this->assertEquals(20.0, $cost);
    }

    /** @test */
    public function it_handles_decimal_consumption_with_time_of_use_rates(): void
    {
        $tariff = $this->createTimeOfUseTariff();
        $consumption = 123.45;
        $timestamp = Carbon::parse('2024-01-15 14:00:00'); // Day rate

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Day rate 0.20 × 123.45 = 24.69
        $this->assertEquals(24.69, $cost);
    }

    /** @test */
    public function it_handles_zero_consumption_with_time_of_use_rates(): void
    {
        $tariff = $this->createTimeOfUseTariff();
        $consumption = 0.0;
        $timestamp = Carbon::parse('2024-01-15 14:00:00');

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        $this->assertEquals(0.0, $cost);
    }

    /** @test */
    public function it_maintains_precision_across_different_rates(): void
    {
        $tariff = $this->createTimeOfUseTariff();
        $consumption = 87.654;

        // Day rate: 0.20 × 87.654 = 17.5308
        $dayCost = $this->strategy->calculate($tariff, $consumption, Carbon::parse('2024-01-15 14:00:00'));
        $this->assertEquals(17.5308, $dayCost);

        // Night rate: 0.10 × 87.654 = 8.7654
        $nightCost = $this->strategy->calculate($tariff, $consumption, Carbon::parse('2024-01-15 02:00:00'));
        $this->assertEquals(8.7654, $nightCost);
    }

    /** @test */
    public function it_correctly_identifies_sunday_as_weekend(): void
    {
        $tariff = $this->createTimeOfUseTariffWithWeekend('apply_night_rate');
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-21 14:00:00'); // Sunday 14:00

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Sunday is weekend, applies night rate
        $this->assertEquals(10.0, $cost);
    }

    /** @test */
    public function it_correctly_identifies_saturday_as_weekend(): void
    {
        $tariff = $this->createTimeOfUseTariffWithWeekend('apply_night_rate');
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-20 14:00:00'); // Saturday 14:00

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Saturday is weekend, applies night rate
        $this->assertEquals(10.0, $cost);
    }

    /** @test */
    public function it_correctly_identifies_weekday_not_as_weekend(): void
    {
        $tariff = $this->createTimeOfUseTariffWithWeekend('apply_night_rate');
        $consumption = 100.0;
        $timestamp = Carbon::parse('2024-01-15 14:00:00'); // Monday 14:00

        $cost = $this->strategy->calculate($tariff, $consumption, $timestamp);

        // Monday is weekday, uses time-based zones (day rate)
        $this->assertEquals(20.0, $cost);
    }

    /** @test */
    public function it_handles_three_tier_rate_structure(): void
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => 'time_of_use',
            'zones' => [
                ['id' => 'peak', 'start' => '17:00', 'end' => '21:00', 'rate' => 0.30],
                ['id' => 'day', 'start' => '07:00', 'end' => '17:00', 'rate' => 0.20],
                ['id' => 'night', 'start' => '21:00', 'end' => '07:00', 'rate' => 0.10],
            ],
        ];
        $consumption = 100.0;

        // Peak time
        $peakCost = $this->strategy->calculate($tariff, $consumption, Carbon::parse('2024-01-15 18:00:00'));
        $this->assertEquals(30.0, $peakCost);

        // Day time
        $dayCost = $this->strategy->calculate($tariff, $consumption, Carbon::parse('2024-01-15 10:00:00'));
        $this->assertEquals(20.0, $dayCost);

        // Night time
        $nightCost = $this->strategy->calculate($tariff, $consumption, Carbon::parse('2024-01-15 23:00:00'));
        $this->assertEquals(10.0, $nightCost);
    }

    /**
     * Helper to create a basic time-of-use tariff.
     */
    private function createTimeOfUseTariff(): Tariff
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => 'time_of_use',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.20],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
        ];

        return $tariff;
    }

    /**
     * Helper to create time-of-use tariff with weekend logic.
     */
    private function createTimeOfUseTariffWithWeekend(string $weekendLogic): Tariff
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => 'time_of_use',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.20],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
            'weekend_logic' => $weekendLogic,
        ];

        return $tariff;
    }

    /**
     * Helper to create time-of-use tariff with dedicated weekend zone.
     */
    private function createTimeOfUseTariffWithWeekendZone(): Tariff
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => 'time_of_use',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.20],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
                ['id' => 'weekend', 'start' => '00:00', 'end' => '23:59', 'rate' => 0.12],
            ],
            'weekend_logic' => 'apply_weekend_rate',
        ];

        return $tariff;
    }
}
