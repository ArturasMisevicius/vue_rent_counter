<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Provider;
use App\Models\Tariff;
use App\Services\TariffCalculation\FlatRateStrategy;
use App\Services\TariffCalculation\TariffCalculationStrategy;
use App\Services\TariffCalculation\TimeOfUseStrategy;
use App\Services\TariffResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for TariffResolver
 *
 * Tests:
 * - Strategy pattern: Correct strategy selection based on tariff type
 * - Tariff resolution: Finding active tariff for provider on specific date
 * - Fallback behavior: Returns 0.0 when no strategy supports type
 * - Edge cases: Date boundaries, overlapping tariffs, missing tariffs
 * - Cost calculation routing
 */
final class TariffResolverTest extends TestCase
{
    use RefreshDatabase;

    private TariffResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new TariffResolver();
    }

    /** @test */
    public function it_routes_flat_rate_tariff_to_flat_rate_strategy(): void
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => 'flat',
            'rate' => 0.15,
        ];

        $cost = $this->resolver->calculateCost($tariff, 100.0, now());

        // 100 × 0.15 = 15.0
        $this->assertEquals(15.0, $cost);
    }

    /** @test */
    public function it_routes_time_of_use_tariff_to_time_of_use_strategy(): void
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => 'time_of_use',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.20],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
        ];

        $dayCost = $this->resolver->calculateCost(
            $tariff,
            100.0,
            Carbon::parse('2024-01-15 14:00:00')
        );

        // 100 × 0.20 (day rate) = 20.0
        $this->assertEquals(20.0, $dayCost);
    }

    /** @test */
    public function it_returns_zero_for_unsupported_tariff_type(): void
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => 'unknown_type',
            'rate' => 0.15,
        ];

        $cost = $this->resolver->calculateCost($tariff, 100.0, now());

        $this->assertEquals(0.0, $cost);
    }

    /** @test */
    public function it_returns_zero_for_missing_tariff_type(): void
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'rate' => 0.15,
            // Missing 'type' key
        ];

        $cost = $this->resolver->calculateCost($tariff, 100.0, now());

        $this->assertEquals(0.0, $cost);
    }

    /** @test */
    public function it_returns_zero_for_empty_tariff_type(): void
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => '',
            'rate' => 0.15,
        ];

        $cost = $this->resolver->calculateCost($tariff, 100.0, now());

        $this->assertEquals(0.0, $cost);
    }

    /** @test */
    public function it_uses_custom_strategies_when_provided(): void
    {
        $mockStrategy = $this->createMock(TariffCalculationStrategy::class);
        $mockStrategy->expects($this->once())
            ->method('supports')
            ->with('custom_type')
            ->willReturn(true);

        $mockStrategy->expects($this->once())
            ->method('calculate')
            ->willReturn(42.0);

        $resolver = new TariffResolver([$mockStrategy]);

        $tariff = new Tariff();
        $tariff->configuration = ['type' => 'custom_type'];

        $cost = $resolver->calculateCost($tariff, 100.0, now());

        $this->assertEquals(42.0, $cost);
    }

    /** @test */
    public function it_initializes_with_default_strategies_when_empty_array_provided(): void
    {
        $resolver = new TariffResolver([]);

        $flatTariff = new Tariff();
        $flatTariff->configuration = [
            'type' => 'flat',
            'rate' => 0.15,
        ];

        $cost = $resolver->calculateCost($flatTariff, 100.0, now());

        // Should use default FlatRateStrategy
        $this->assertEquals(15.0, $cost);
    }

    /** @test */
    public function it_uses_current_timestamp_when_not_provided(): void
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => 'flat',
            'rate' => 0.15,
        ];

        // Not providing timestamp, should use now()
        $cost = $this->resolver->calculateCost($tariff, 100.0);

        $this->assertEquals(15.0, $cost);
    }

    /** @test */
    public function it_resolves_active_tariff_for_provider_on_current_date(): void
    {
        $provider = Provider::factory()->create();

        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'active_from' => Carbon::parse('2024-01-01'),
            'active_until' => null, // Open-ended
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
            ],
        ]);

        $resolved = $this->resolver->resolve($provider, Carbon::parse('2024-06-15'));

        $this->assertEquals($tariff->id, $resolved->id);
    }

    /** @test */
    public function it_resolves_tariff_within_valid_date_range(): void
    {
        $provider = Provider::factory()->create();

        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'active_from' => Carbon::parse('2024-01-01'),
            'active_until' => Carbon::parse('2024-12-31'),
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
            ],
        ]);

        $resolved = $this->resolver->resolve($provider, Carbon::parse('2024-06-15'));

        $this->assertEquals($tariff->id, $resolved->id);
    }

    /** @test */
    public function it_throws_exception_when_no_active_tariff_exists(): void
    {
        $provider = Provider::factory()->create();

        // Create tariff that's not active on query date
        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'active_from' => Carbon::parse('2025-01-01'),
            'active_until' => null,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
            ],
        ]);

        $this->expectException(ModelNotFoundException::class);

        $this->resolver->resolve($provider, Carbon::parse('2024-06-15'));
    }

    /** @test */
    public function it_resolves_most_recent_tariff_when_multiple_active(): void
    {
        $provider = Provider::factory()->create();

        // Older tariff
        $olderTariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'active_from' => Carbon::parse('2024-01-01'),
            'active_until' => null,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.12,
            ],
        ]);

        // Newer tariff (should be selected)
        $newerTariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'active_from' => Carbon::parse('2024-06-01'),
            'active_until' => null,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
            ],
        ]);

        $resolved = $this->resolver->resolve($provider, Carbon::parse('2024-07-15'));

        // Should get the newer tariff
        $this->assertEquals($newerTariff->id, $resolved->id);
        $this->assertEquals(0.15, $resolved->configuration['rate']);
    }

    /** @test */
    public function it_resolves_tariff_at_exact_active_from_boundary(): void
    {
        $provider = Provider::factory()->create();

        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'active_from' => Carbon::parse('2024-06-01 00:00:00'),
            'active_until' => null,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
            ],
        ]);

        // Query at exact start time
        $resolved = $this->resolver->resolve($provider, Carbon::parse('2024-06-01 00:00:00'));

        $this->assertEquals($tariff->id, $resolved->id);
    }

    /** @test */
    public function it_resolves_tariff_at_exact_active_until_boundary(): void
    {
        $provider = Provider::factory()->create();

        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'active_from' => Carbon::parse('2024-01-01'),
            'active_until' => Carbon::parse('2024-12-31 23:59:59'),
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
            ],
        ]);

        // Query at exact end time
        $resolved = $this->resolver->resolve($provider, Carbon::parse('2024-12-31 23:59:59'));

        $this->assertEquals($tariff->id, $resolved->id);
    }

    /** @test */
    public function it_does_not_resolve_tariff_before_active_from(): void
    {
        $provider = Provider::factory()->create();

        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'active_from' => Carbon::parse('2024-06-01'),
            'active_until' => null,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
            ],
        ]);

        $this->expectException(ModelNotFoundException::class);

        // Query before active_from
        $this->resolver->resolve($provider, Carbon::parse('2024-05-31'));
    }

    /** @test */
    public function it_does_not_resolve_tariff_after_active_until(): void
    {
        $provider = Provider::factory()->create();

        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'active_from' => Carbon::parse('2024-01-01'),
            'active_until' => Carbon::parse('2024-12-31'),
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
            ],
        ]);

        $this->expectException(ModelNotFoundException::class);

        // Query after active_until
        $this->resolver->resolve($provider, Carbon::parse('2025-01-01'));
    }

    /** @test */
    public function it_calculates_cost_with_different_strategies_correctly(): void
    {
        $flatTariff = new Tariff();
        $flatTariff->configuration = [
            'type' => 'flat',
            'rate' => 0.15,
        ];

        $touTariff = new Tariff();
        $touTariff->configuration = [
            'type' => 'time_of_use',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.20],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
        ];

        $consumption = 123.45;

        // Flat rate calculation
        $flatCost = $this->resolver->calculateCost($flatTariff, $consumption, now());
        $this->assertEquals(18.5175, $flatCost);

        // Time-of-use day rate calculation
        $touDayCost = $this->resolver->calculateCost(
            $touTariff,
            $consumption,
            Carbon::parse('2024-01-15 14:00:00')
        );
        $this->assertEquals(24.69, $touDayCost);

        // Time-of-use night rate calculation
        $touNightCost = $this->resolver->calculateCost(
            $touTariff,
            $consumption,
            Carbon::parse('2024-01-15 02:00:00')
        );
        $this->assertEquals(12.345, $touNightCost);
    }

    /** @test */
    public function it_handles_zero_consumption_across_strategies(): void
    {
        $flatTariff = new Tariff();
        $flatTariff->configuration = [
            'type' => 'flat',
            'rate' => 0.15,
        ];

        $cost = $this->resolver->calculateCost($flatTariff, 0.0, now());

        $this->assertEquals(0.0, $cost);
    }

    /** @test */
    public function it_maintains_precision_in_cost_calculations(): void
    {
        $tariff = new Tariff();
        $tariff->configuration = [
            'type' => 'flat',
            'rate' => 0.12345,
        ];

        $consumption = 87.654;

        $cost = $this->resolver->calculateCost($tariff, $consumption, now());

        // 0.12345 × 87.654 = ~10.82088
        $this->assertEqualsWithDelta(10.82088, $cost, 0.0001);
    }
}
