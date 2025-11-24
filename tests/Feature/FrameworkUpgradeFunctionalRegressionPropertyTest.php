<?php

use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Feature: framework-upgrade, Property 1: Functional regression prevention
// Validates: Requirements 1.2, 7.1, 7.2, 7.3
test('baseline upgrade flows preserve functional correctness', function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $tenantId = fake()->numberBetween(1, 5000);
    session(['tenant_id' => $tenantId]);

    $property = Property::factory()->create([
        'tenant_id' => $tenantId,
        'type' => PropertyType::APARTMENT,
    ]);

    $tenant = Tenant::factory()->forProperty($property)->create([
        'tenant_id' => $tenantId,
    ]);

    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);

    $reactivationPassword = 'upgrade-pass-' . fake()->numberBetween(1000, 9999);
    $inactiveUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make($reactivationPassword),
        'is_active' => false,
    ]);

    $periodEnd = Carbon::now();
    $periodStart = $periodEnd->copy()->subDays(30);

    $electricityRate = fake()->randomFloat(4, 0.10, 0.30);
    $electricityProvider = Provider::factory()->create([
        'service_type' => ServiceType::ELECTRICITY,
    ]);

    Tariff::factory()->create([
        'provider_id' => $electricityProvider->id,
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => $electricityRate,
        ],
        'active_from' => $periodStart->copy()->subMonth(),
        'active_until' => null,
    ]);

    $waterSupplyRate = fake()->randomFloat(2, 0.80, 1.20);
    $waterSewageRate = fake()->randomFloat(2, 1.00, 1.70);
    $waterFixedFee = fake()->randomFloat(2, 0.50, 2.00);
    $waterProvider = Provider::factory()->create([
        'service_type' => ServiceType::WATER,
    ]);

    Tariff::factory()->create([
        'provider_id' => $waterProvider->id,
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => $waterSupplyRate + $waterSewageRate,
            'supply_rate' => $waterSupplyRate,
            'sewage_rate' => $waterSewageRate,
            'fixed_fee' => $waterFixedFee,
        ],
        'active_from' => $periodStart->copy()->subMonth(),
        'active_until' => null,
    ]);

    $electricityMeter = Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => false,
    ]);

    $waterMeter = Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'type' => MeterType::WATER_COLD,
        'supports_zones' => false,
    ]);

    $electricityStartValue = fake()->numberBetween(500, 1500);
    $electricityConsumption = fake()->numberBetween(50, 300);
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $electricityMeter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => $electricityStartValue,
        'entered_by' => $manager->id,
    ]);

    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $electricityMeter->id,
        'reading_date' => $periodEnd,
        'value' => $electricityStartValue + $electricityConsumption,
        'entered_by' => $manager->id,
    ]);

    $waterStartValue = fake()->numberBetween(100, 500);
    $waterConsumption = fake()->numberBetween(5, 30);
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $waterMeter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => $waterStartValue,
        'entered_by' => $manager->id,
    ]);

    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $waterMeter->id,
        'reading_date' => $periodEnd,
        'value' => $waterStartValue + $waterConsumption,
        'entered_by' => $manager->id,
    ]);

    // Monotonicity guard stays enforced for existing meters
    $this->actingAs($manager);
    $response = $this->post(route('manager.meter-readings.store'), [
        'meter_id' => $electricityMeter->id,
        'reading_date' => $periodEnd->toDateString(),
        'value' => $electricityStartValue - fake()->numberBetween(1, 25),
    ]);

    $response->assertSessionHasErrors('value');

    // Invoice generation still derives totals from readings + tariffs
    $invoice = app(BillingService::class)->generateInvoice($tenant, $periodStart, $periodEnd);

    expect($invoice->items->count())->toBe(2);

    $electricityItem = $invoice->items->first(function ($item) use ($electricityMeter) {
        return (int) ($item->meter_reading_snapshot['meter_id'] ?? 0) === $electricityMeter->id;
    });
    $waterItem = $invoice->items->first(function ($item) use ($waterMeter) {
        return (int) ($item->meter_reading_snapshot['meter_id'] ?? 0) === $waterMeter->id;
    });

    expect($electricityItem)->not->toBeNull();
    expect($waterItem)->not->toBeNull();

    $expectedElectricityTotal = $electricityConsumption * $electricityRate;
    expect(abs((float) $electricityItem->total - $expectedElectricityTotal))->toBeLessThan(0.01);

    $expectedWaterTotal = ($waterConsumption * $waterSupplyRate) + ($waterConsumption * $waterSewageRate) + $waterFixedFee;
    expect(abs((float) $waterItem->total - $expectedWaterTotal))->toBeLessThan(0.01);

    Auth::logout();

    // Reactivation path matches pre-upgrade behavior
    $response = $this->post('/login', [
        'email' => $inactiveUser->email,
        'password' => $reactivationPassword,
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();

    $inactiveUser->update(['is_active' => true]);

    $response = $this->post('/login', [
        'email' => $inactiveUser->email,
        'password' => $reactivationPassword,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($inactiveUser);

    Auth::logout();
    $this->app['session.store']->flush();
})->repeat(20);
