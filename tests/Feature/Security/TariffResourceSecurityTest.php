<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\TariffResource;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Livewire\Livewire;

use Illuminate\Support\Facades\Log;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $this->tenant = User::factory()->create(['role' => UserRole::TENANT]);
    $this->provider = Provider::factory()->create();
});

describe('TariffResource Security Tests', function () {
    test('prevents XSS injection in tariff name', function () {
        actingAs($this->admin);
        
        $xssPayload = '<script>alert("XSS")</script>';
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => $xssPayload,
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'regex']);
    });

    test('prevents HTML injection in tariff name', function () {
        actingAs($this->admin);
        
        $htmlPayload = '<b>Bold Name</b>';
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => $htmlPayload,
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'regex']);
    });

    test('prevents numeric overflow in rate field', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Overflow Test',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 9999999.9999, // Exceeds max
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.rate' => 'max']);
    });

    test('prevents numeric overflow in zone rate', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Zone Overflow Test',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        [
                            'id' => 'day',
                            'start' => '07:00',
                            'end' => '23:00',
                            'rate' => 9999999.9999, // Exceeds max
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones.0.rate' => 'max']);
    });

    test('prevents numeric overflow in fixed fee', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Fixed Fee Overflow Test',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                    'fixed_fee' => 9999999.99, // Exceeds max
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.fixed_fee' => 'max']);
    });

    test('prevents invalid characters in zone ID', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Zone ID Test',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        [
                            'id' => 'day<script>',
                            'start' => '07:00',
                            'end' => '23:00',
                            'rate' => 0.20,
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones.0.id' => 'regex']);
    });

    test('enforces zone ID max length', function () {
        actingAs($this->admin);
        
        $longZoneId = str_repeat('a', 51); // Exceeds 50 char limit
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Zone ID Length Test',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        [
                            'id' => $longZoneId,
                            'start' => '07:00',
                            'end' => '23:00',
                            'rate' => 0.20,
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones.0.id' => 'max']);
    });

    test('prevents unauthorized tariff creation by manager', function () {
        actingAs($this->manager);
        
        expect(TariffResource::canCreate())->toBeFalse();
    });

    test('prevents unauthorized tariff creation by tenant', function () {
        actingAs($this->tenant);
        
        expect(TariffResource::canCreate())->toBeFalse();
    });

    test('prevents unauthorized tariff update by manager', function () {
        $tariff = Tariff::factory()->for($this->provider)->create();
        
        actingAs($this->manager);
        
        expect(TariffResource::canEdit($tariff))->toBeFalse();
    });

    test('prevents unauthorized tariff deletion by manager', function () {
        $tariff = Tariff::factory()->for($this->provider)->create();
        
        actingAs($this->manager);
        
        expect(TariffResource::canDelete($tariff))->toBeFalse();
    });

    test('logs tariff creation in audit log', function () {
        actingAs($this->admin);
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('Tariff created', \Mockery::type('array'));
        
        Tariff::factory()->for($this->provider)->create();
    });

    test('logs tariff update in audit log', function () {
        $tariff = Tariff::factory()->for($this->provider)->create();
        
        actingAs($this->admin);
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->with('Tariff updated', \Mockery::type('array'));
        
        $tariff->update(['name' => 'Updated Name']);
    });

    test('logs tariff deletion in audit log', function () {
        $tariff = Tariff::factory()->for($this->provider)->create();
        
        actingAs($this->admin);
        
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->with('Tariff deleted', \Mockery::type('array'));
        
        $tariff->delete();
    });

    test('sanitizes HTML from tariff name on save', function () {
        actingAs($this->admin);
        
        // Use allowed characters but with HTML-like structure
        $name = 'Standard Rate (2024)';
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => $name,
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        
        $tariff = Tariff::where('name', $name)->first();
        expect($tariff)->not->toBeNull();
        expect($tariff->name)->toBe($name);
    });

    test('validates provider exists and is accessible', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => 99999, // Non-existent provider
                'name' => 'Test Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['provider_id' => 'exists']);
    });

    test('prevents negative rate values', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Negative Rate Test',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => -0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.rate' => 'min']);
    });

    test('prevents negative zone rates', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Negative Zone Rate Test',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        [
                            'id' => 'day',
                            'start' => '07:00',
                            'end' => '23:00',
                            'rate' => -0.20,
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones.0.rate' => 'min']);
    });

    test('prevents negative fixed fees', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Negative Fixed Fee Test',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                    'fixed_fee' => -5.00,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.fixed_fee' => 'min']);
    });
});

describe('TariffResource CSRF Protection', function () {
    test('verifies CSRF token is required for tariff creation', function () {
        actingAs($this->admin);
        
        // Attempt to create tariff without CSRF token
        $response = post(route('filament.admin.resources.tariffs.create'), [
            'provider_id' => $this->provider->id,
            'name' => 'CSRF Test',
            'active_from' => now()->toDateString(),
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => 0.15,
            ],
        ], [
            'X-CSRF-TOKEN' => 'invalid-token',
        ]);
        
        $response->assertStatus(419); // CSRF token mismatch
    });
});

describe('TariffResource Security Headers', function () {
    test('verifies security headers are present', function () {
        actingAs($this->admin);
        
        $response = get(route('filament.admin.resources.tariffs.index'));
        
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    });

    test('verifies CSP header is present', function () {
        actingAs($this->admin);
        
        $response = get(route('filament.admin.resources.tariffs.index'));
        
        $response->assertHeader('Content-Security-Policy');
        
        $csp = $response->headers->get('Content-Security-Policy');
        expect($csp)->toContain("default-src 'self'");
        expect($csp)->toContain("frame-ancestors 'self'");
    });
});
