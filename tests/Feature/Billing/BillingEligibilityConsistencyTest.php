<?php

declare(strict_types=1);

use App\Enums\DistributionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Support\Admin\Invoices\BulkInvoicePreviewBuilder;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('skips billing candidates when the period-ending reading is not comparable', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-1',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Nora Tenant',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $provider = Provider::factory()->for($organization)->create([
        'service_type' => ServiceType::WATER,
    ]);
    $tariff = Tariff::factory()->for($provider)->create([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 1.75,
        ],
    ]);
    $utilityService = UtilityService::factory()->for($organization)->create([
        'name' => 'Water',
        'unit_of_measurement' => 'm3',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'service_type_bridge' => ServiceType::WATER,
    ]);

    ServiceConfiguration::factory()
        ->for($organization)
        ->for($property)
        ->for($utilityService)
        ->for($provider)
        ->for($tariff)
        ->create([
            'pricing_model' => PricingModel::CONSUMPTION_BASED,
            'distribution_method' => DistributionMethod::BY_CONSUMPTION,
            'rate_schedule' => ['unit_rate' => 1.75],
        ]);

    $meter = Meter::factory()->for($organization)->for($property)->create([
        'type' => MeterType::WATER,
    ]);

    $periodStart = now()->startOfMonth();
    $periodEnd = now()->endOfMonth();

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 50,
        'reading_date' => $periodStart->copy()->subDay()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 60,
        'reading_date' => $periodEnd->toDateString(),
        'validation_status' => MeterReadingValidationStatus::REJECTED,
    ]);

    $attributes = [
        'billing_period_start' => $periodStart->toDateString(),
        'billing_period_end' => $periodEnd->toDateString(),
        'due_date' => $periodEnd->copy()->addDays(14)->toDateString(),
    ];

    $preview = app(BulkInvoicePreviewBuilder::class)->handle($organization, $attributes);
    $generated = app(GenerateBulkInvoicesAction::class)->handle($organization, $attributes, $admin);

    expect($preview['valid'])->toBeEmpty()
        ->and($preview['skipped'])->toHaveCount(1)
        ->and($preview['skipped'][0]['reason'])->toBe('ineligible_meter_readings')
        ->and($generated['created'])->toBeEmpty()
        ->and($generated['skipped'])->toHaveCount(1)
        ->and($generated['skipped'][0]['reason'])->toBe('ineligible_meter_readings');
});
