<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Provider;
use App\Models\Tariff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for Tariff Model
 *
 * Tests:
 * - Mass assignment and fillable attributes
 * - Attribute casting (array/JSON, datetime)
 * - Relationships (provider)
 * - Query scopes (active, forProvider, flatRate, timeOfUse)
 * - Business logic (isActiveOn, isFlatRate, isTimeOfUse, getFlatRate)
 * - Computed attributes (is_currently_active)
 */
final class TariffTest extends TestCase
{
    use RefreshDatabase;

    private Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a provider for testing
        $this->provider = Provider::factory()->create();
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $tariff = new Tariff();

        $expectedFillable = [
            'provider_id',
            'name',
            'configuration',
            'active_from',
            'active_until',
        ];

        $this->assertEquals($expectedFillable, $tariff->getFillable());
    }

    /** @test */
    public function it_can_be_created_with_mass_assignment(): void
    {
        $data = [
            'provider_id' => $this->provider->id,
            'name' => 'Standard Electricity Rate',
            'configuration' => ['type' => 'flat', 'rate' => 0.15],
            'active_from' => '2024-01-01 00:00:00',
            'active_until' => '2024-12-31 23:59:59',
        ];

        $tariff = Tariff::create($data);

        $this->assertDatabaseHas('tariffs', [
            'id' => $tariff->id,
            'provider_id' => $this->provider->id,
            'name' => 'Standard Electricity Rate',
        ]);
    }

    /** @test */
    public function it_casts_configuration_to_array(): void
    {
        $tariff = Tariff::factory()->create([
            'configuration' => ['type' => 'flat', 'rate' => 0.15],
        ]);

        $this->assertIsArray($tariff->configuration);
        $this->assertEquals('flat', $tariff->configuration['type']);
        $this->assertEquals(0.15, $tariff->configuration['rate']);
    }

    /** @test */
    public function it_casts_active_from_to_datetime(): void
    {
        $tariff = Tariff::factory()->create([
            'active_from' => '2024-01-01 00:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $tariff->active_from);
        $this->assertEquals('2024-01-01', $tariff->active_from->format('Y-m-d'));
    }

    /** @test */
    public function it_casts_active_until_to_datetime(): void
    {
        $tariff = Tariff::factory()->create([
            'active_until' => '2024-12-31 23:59:59',
        ]);

        $this->assertInstanceOf(Carbon::class, $tariff->active_until);
        $this->assertEquals('2024-12-31', $tariff->active_until->format('Y-m-d'));
    }

    /** @test */
    public function it_belongs_to_a_provider(): void
    {
        $tariff = Tariff::factory()->create([
            'provider_id' => $this->provider->id,
        ]);

        $this->assertInstanceOf(Provider::class, $tariff->provider);
        $this->assertEquals($this->provider->id, $tariff->provider->id);
    }

    /** @test */
    public function it_has_timestamps(): void
    {
        $tariff = Tariff::factory()->create();

        $this->assertNotNull($tariff->created_at);
        $this->assertNotNull($tariff->updated_at);
    }

    /** @test */
    public function is_active_on_returns_true_for_date_within_range(): void
    {
        $tariff = Tariff::factory()->create([
            'active_from' => '2024-01-01 00:00:00',
            'active_until' => '2024-12-31 23:59:59',
        ]);

        $testDate = Carbon::parse('2024-06-15');

        $this->assertTrue($tariff->isActiveOn($testDate));
    }

    /** @test */
    public function is_active_on_returns_false_for_date_before_range(): void
    {
        $tariff = Tariff::factory()->create([
            'active_from' => '2024-01-01 00:00:00',
            'active_until' => '2024-12-31 23:59:59',
        ]);

        $testDate = Carbon::parse('2023-12-31');

        $this->assertFalse($tariff->isActiveOn($testDate));
    }

    /** @test */
    public function is_active_on_returns_false_for_date_after_range(): void
    {
        $tariff = Tariff::factory()->create([
            'active_from' => '2024-01-01 00:00:00',
            'active_until' => '2024-12-31 23:59:59',
        ]);

        $testDate = Carbon::parse('2025-01-01');

        $this->assertFalse($tariff->isActiveOn($testDate));
    }

    /** @test */
    public function is_active_on_returns_true_when_active_until_is_null(): void
    {
        $tariff = Tariff::factory()->create([
            'active_from' => '2024-01-01 00:00:00',
            'active_until' => null,
        ]);

        $futureDate = Carbon::parse('2030-01-01');

        $this->assertTrue($tariff->isActiveOn($futureDate));
    }

    /** @test */
    public function is_flat_rate_returns_true_for_flat_rate_tariff(): void
    {
        $tariff = Tariff::factory()->create([
            'configuration' => ['type' => 'flat', 'rate' => 0.15],
        ]);

        $this->assertTrue($tariff->isFlatRate());
        $this->assertFalse($tariff->isTimeOfUse());
    }

    /** @test */
    public function is_time_of_use_returns_true_for_time_of_use_tariff(): void
    {
        $tariff = Tariff::factory()->create([
            'configuration' => ['type' => 'time_of_use', 'rates' => ['peak' => 0.20, 'off_peak' => 0.10]],
        ]);

        $this->assertTrue($tariff->isTimeOfUse());
        $this->assertFalse($tariff->isFlatRate());
    }

    /** @test */
    public function get_flat_rate_returns_rate_for_flat_rate_tariff(): void
    {
        $tariff = Tariff::factory()->create([
            'configuration' => ['type' => 'flat', 'rate' => 0.15],
        ]);

        $this->assertEquals(0.15, $tariff->getFlatRate());
    }

    /** @test */
    public function get_flat_rate_returns_null_for_non_flat_rate_tariff(): void
    {
        $tariff = Tariff::factory()->create([
            'configuration' => ['type' => 'time_of_use', 'rates' => ['peak' => 0.20]],
        ]);

        $this->assertNull($tariff->getFlatRate());
    }

    /** @test */
    public function scope_active_returns_only_currently_active_tariffs(): void
    {
        // Active tariff (no end date)
        Tariff::factory()->create([
            'active_from' => now()->subDays(30),
            'active_until' => null,
        ]);

        // Active tariff (within range)
        Tariff::factory()->create([
            'active_from' => now()->subDays(30),
            'active_until' => now()->addDays(30),
        ]);

        // Expired tariff
        Tariff::factory()->create([
            'active_from' => now()->subDays(60),
            'active_until' => now()->subDays(30),
        ]);

        // Future tariff
        Tariff::factory()->create([
            'active_from' => now()->addDays(30),
            'active_until' => now()->addDays(60),
        ]);

        $activeTariffs = Tariff::active()->get();

        $this->assertCount(2, $activeTariffs);
    }

    /** @test */
    public function scope_active_accepts_specific_date(): void
    {
        Tariff::factory()->create([
            'active_from' => '2024-01-01 00:00:00',
            'active_until' => '2024-06-30 23:59:59',
        ]);

        Tariff::factory()->create([
            'active_from' => '2024-07-01 00:00:00',
            'active_until' => '2024-12-31 23:59:59',
        ]);

        $testDate = Carbon::parse('2024-03-15');
        $activeTariffs = Tariff::active($testDate)->get();

        $this->assertCount(1, $activeTariffs);
    }

    /** @test */
    public function scope_for_provider_filters_by_provider_id(): void
    {
        $provider1 = Provider::factory()->create();
        $provider2 = Provider::factory()->create();

        Tariff::factory()->count(3)->create(['provider_id' => $provider1->id]);
        Tariff::factory()->count(2)->create(['provider_id' => $provider2->id]);

        $provider1Tariffs = Tariff::forProvider($provider1->id)->get();
        $provider2Tariffs = Tariff::forProvider($provider2->id)->get();

        $this->assertCount(3, $provider1Tariffs);
        $this->assertCount(2, $provider2Tariffs);
    }

    /** @test */
    public function scope_flat_rate_returns_only_flat_rate_tariffs(): void
    {
        Tariff::factory()->count(3)->create([
            'configuration' => ['type' => 'flat', 'rate' => 0.15],
        ]);

        Tariff::factory()->count(2)->create([
            'configuration' => ['type' => 'time_of_use', 'rates' => ['peak' => 0.20]],
        ]);

        $flatRateTariffs = Tariff::flatRate()->get();

        $this->assertCount(3, $flatRateTariffs);
        $flatRateTariffs->each(fn($tariff) => $this->assertTrue($tariff->isFlatRate()));
    }

    /** @test */
    public function scope_time_of_use_returns_only_time_of_use_tariffs(): void
    {
        Tariff::factory()->count(2)->create([
            'configuration' => ['type' => 'flat', 'rate' => 0.15],
        ]);

        Tariff::factory()->count(4)->create([
            'configuration' => ['type' => 'time_of_use', 'rates' => ['peak' => 0.20]],
        ]);

        $timeOfUseTariffs = Tariff::timeOfUse()->get();

        $this->assertCount(4, $timeOfUseTariffs);
        $timeOfUseTariffs->each(fn($tariff) => $this->assertTrue($tariff->isTimeOfUse()));
    }

    /** @test */
    public function is_currently_active_attribute_is_computed_correctly(): void
    {
        $activeTariff = Tariff::factory()->create([
            'active_from' => now()->subDays(30),
            'active_until' => now()->addDays(30),
        ]);

        $expiredTariff = Tariff::factory()->create([
            'active_from' => now()->subDays(60),
            'active_until' => now()->subDays(30),
        ]);

        $this->assertTrue($activeTariff->is_currently_active);
        $this->assertFalse($expiredTariff->is_currently_active);
    }

    /** @test */
    public function can_combine_multiple_scopes(): void
    {
        $provider = Provider::factory()->create();

        // Active flat rate for this provider
        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => ['type' => 'flat', 'rate' => 0.15],
            'active_from' => now()->subDays(30),
            'active_until' => now()->addDays(30),
        ]);

        // Active time-of-use for this provider
        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => ['type' => 'time_of_use', 'rates' => []],
            'active_from' => now()->subDays(30),
            'active_until' => now()->addDays(30),
        ]);

        // Expired flat rate for this provider
        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => ['type' => 'flat', 'rate' => 0.15],
            'active_from' => now()->subDays(60),
            'active_until' => now()->subDays(30),
        ]);

        $result = Tariff::forProvider($provider->id)
            ->active()
            ->flatRate()
            ->get();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->isFlatRate());
    }
}
