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
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Models\User;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Enums\DistributionMethod;
use App\Enums\PricingModel;

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

    $electricityTariff = Tariff::factory()->create([
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

    $waterTariff = Tariff::factory()->create([
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

    $this->attachConsumptionServiceToMeter(
        meter: $electricityMeter,
        serviceName: 'Electricity',
        unitOfMeasurement: 'kWh',
        unitRate: $electricityRate,
        bridgeType: ServiceType::ELECTRICITY,
        effectiveFrom: $periodStart->copy()->subMonth(),
        providerId: $electricityProvider->id,
        tariffId: $electricityTariff->id,
    );

    $waterService = UtilityService::factory()->create([
        'tenant_id' => $tenantId,
        'name' => 'Water',
        'slug' => 'water-' . uniqid(),
        'unit_of_measurement' => 'm3',
        'default_pricing_model' => PricingModel::HYBRID,
        'service_type_bridge' => ServiceType::WATER,
        'is_global_template' => false,
        'is_active' => true,
    ]);

    $waterServiceConfiguration = ServiceConfiguration::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'utility_service_id' => $waterService->id,
        'pricing_model' => PricingModel::HYBRID,
        'rate_schedule' => [
            'fixed_fee' => $waterFixedFee,
            'unit_rate' => $waterSupplyRate + $waterSewageRate,
        ],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => $periodStart->copy()->subMonth(),
        'effective_until' => null,
        'provider_id' => $waterProvider->id,
        'tariff_id' => $waterTariff->id,
        'configuration_overrides' => [],
        'is_active' => true,
    ]);

    $waterMeter->update(['service_configuration_id' => $waterServiceConfiguration->id]);

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

    expect($invoice->items->count())->toBe(3);

    $electricityItem = $invoice->items->first(function ($item) use ($electricityMeter) {
        return (int) ($item->meter_reading_snapshot['meters'][0]['meter_id'] ?? 0) === $electricityMeter->id;
    });

    $waterItems = $invoice->items->filter(function ($item) use ($waterMeter) {
        return (int) ($item->meter_reading_snapshot['meters'][0]['meter_id'] ?? 0) === $waterMeter->id;
    });

    expect($electricityItem)->not->toBeNull();
    expect($waterItems)->toHaveCount(2);

    $expectedElectricityTotal = $electricityConsumption * $electricityRate;
    expect(abs((float) $electricityItem->total - $expectedElectricityTotal))->toBeLessThan(0.01);

    $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
    $proRatedFixedFee = $waterFixedFee * ($daysInPeriod / $periodStart->daysInMonth);

    $expectedWaterTotal = round($waterConsumption * ($waterSupplyRate + $waterSewageRate), 2) + round($proRatedFixedFee, 2);
    $actualWaterTotal = $waterItems->sum(fn ($item) => (float) $item->total);
    expect(abs($actualWaterTotal - $expectedWaterTotal))->toBeLessThan(0.01);

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
