<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Provider;
use App\Models\Tariff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TariffTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_identifies_manual_tariffs_correctly(): void
    {
        $manualTariff = Tariff::factory()->create(['provider_id' => null]);
        
        $this->assertTrue($manualTariff->isManual());
    }

    /** @test */
    public function it_identifies_provider_tariffs_correctly(): void
    {
        $provider = Provider::factory()->create();
        $providerTariff = Tariff::factory()->create(['provider_id' => $provider->id]);
        
        $this->assertFalse($providerTariff->isManual());
    }

    /** @test */
    public function remote_id_is_fillable(): void
    {
        $provider = Provider::factory()->create();
        
        $tariff = Tariff::create([
            'provider_id' => $provider->id,
            'remote_id' => 'EXT-12345',
            'name' => 'Test Tariff',
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
                'currency' => 'EUR',
            ],
            'active_from' => now(),
        ]);

        $this->assertNotNull($tariff->id);
        $this->assertEquals('EXT-12345', $tariff->remote_id);
    }

    /** @test */
    public function provider_relationship_is_nullable(): void
    {
        $tariff = Tariff::factory()->create(['provider_id' => null]);
        
        $this->assertNull($tariff->provider);
        $this->assertNull($tariff->provider_id);
    }

    /** @test */
    public function provider_relationship_works_when_set(): void
    {
        $provider = Provider::factory()->create();
        $tariff = Tariff::factory()->create(['provider_id' => $provider->id]);
        
        $this->assertNotNull($tariff->provider);
        $this->assertEquals($provider->id, $tariff->provider->id);
    }

    /** @test */
    public function remote_id_can_be_null(): void
    {
        $tariff = Tariff::factory()->create([
            'provider_id' => null,
            'remote_id' => null,
        ]);

        $this->assertNull($tariff->remote_id);
    }

    /** @test */
    public function remote_id_can_store_up_to_255_characters(): void
    {
        $provider = Provider::factory()->create();
        $longRemoteId = str_repeat('A', 255);
        
        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'remote_id' => $longRemoteId,
        ]);

        $this->assertEquals($longRemoteId, $tariff->remote_id);
        $this->assertEquals(255, strlen($tariff->remote_id));
    }

    /** @test */
    public function is_manual_accessor_returns_correct_value(): void
    {
        $manualTariff = Tariff::factory()->create(['provider_id' => null]);
        $providerTariff = Tariff::factory()->create([
            'provider_id' => Provider::factory()->create()->id,
        ]);

        // Test accessor if it exists
        if (method_exists($manualTariff, 'getAttribute')) {
            $this->assertTrue($manualTariff->is_manual ?? $manualTariff->isManual());
            $this->assertFalse($providerTariff->is_manual ?? $providerTariff->isManual());
        }
    }

    /** @test */
    public function can_convert_manual_tariff_to_provider_tariff(): void
    {
        $tariff = Tariff::factory()->create([
            'provider_id' => null,
            'remote_id' => null,
        ]);

        $this->assertTrue($tariff->isManual());

        $provider = Provider::factory()->create();
        $tariff->update([
            'provider_id' => $provider->id,
            'remote_id' => 'EXT-NEW-123',
        ]);

        $tariff->refresh();

        $this->assertFalse($tariff->isManual());
        $this->assertEquals($provider->id, $tariff->provider_id);
        $this->assertEquals('EXT-NEW-123', $tariff->remote_id);
    }

    /** @test */
    public function can_convert_provider_tariff_to_manual_tariff(): void
    {
        $provider = Provider::factory()->create();
        $tariff = Tariff::factory()->create([
            'provider_id' => $provider->id,
            'remote_id' => 'EXT-123',
        ]);

        $this->assertFalse($tariff->isManual());

        $tariff->update([
            'provider_id' => null,
            'remote_id' => null,
        ]);

        $tariff->refresh();

        $this->assertTrue($tariff->isManual());
        $this->assertNull($tariff->provider_id);
        $this->assertNull($tariff->remote_id);
    }
}
