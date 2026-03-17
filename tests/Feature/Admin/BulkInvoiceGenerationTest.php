<?php

use App\Enums\DistributionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Models\Building;
use App\Models\Invoice;
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

it('bulk generates invoices while skipping tenants already billed for the selected period', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $building = Building::factory()->for($organization)->create();

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

    $tenantA = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $tenantB = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $propertyA = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-1',
    ]);
    $propertyB = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-2',
    ]);

    PropertyAssignment::factory()->for($organization)->for($propertyA)->for($tenantA, 'tenant')->create();
    PropertyAssignment::factory()->for($organization)->for($propertyB)->for($tenantB, 'tenant')->create();

    foreach ([$propertyA, $propertyB] as $property) {
        ServiceConfiguration::factory()->for($organization)->for($property)->for($utilityService)->for($provider)->for($tariff)->create([
            'pricing_model' => PricingModel::CONSUMPTION_BASED,
            'distribution_method' => DistributionMethod::BY_CONSUMPTION,
            'rate_schedule' => ['unit_rate' => 1.75],
        ]);

        $meter = Meter::factory()->for($organization)->for($property)->create([
            'type' => MeterType::WATER,
        ]);

        MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
            'reading_value' => 50,
            'reading_date' => now()->subMonth()->endOfMonth()->toDateString(),
            'validation_status' => MeterReadingValidationStatus::VALID,
        ]);

        MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
            'reading_value' => 60,
            'reading_date' => now()->endOfMonth()->toDateString(),
            'validation_status' => MeterReadingValidationStatus::VALID,
        ]);
    }

    Invoice::factory()->for($organization)->for($propertyA)->for($tenantA, 'tenant')->create([
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
    ]);

    $result = app(GenerateBulkInvoicesAction::class)->handle($organization, [
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
    ], $admin);

    expect($result['created'])->toHaveCount(1)
        ->and($result['skipped'])->toHaveCount(1)
        ->and($result['skipped'][0]['tenant_id'])->toBe($tenantA->id)
        ->and(Invoice::query()
            ->where('organization_id', $organization->id)
            ->where('tenant_user_id', $tenantB->id)
            ->whereDate('billing_period_start', now()->startOfMonth())
            ->exists())->toBeTrue();
});
