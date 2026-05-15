<?php

use App\Enums\DistributionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Pages\GenerateBulkInvoices;
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
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

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
            'is_shared_service' => false,
        ]);

        $meter = Meter::factory()->for($organization)->for($property)->create([
            'type' => MeterType::WATER,
        ]);

        MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
            'reading_value' => 50,
            'reading_date' => now()->startOfMonth()->subDay()->toDateString(),
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
        'due_date' => now()->endOfMonth()->addDays(14)->toDateString(),
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

it('renders the bulk invoice generation page contract with live preview warnings and disabled already billed tenants', function () {
    [
        'organization' => $organization,
        'admin' => $admin,
        'alreadyBilledTenant' => $alreadyBilledTenant,
        'validTenant' => $validTenant,
        'validProperty' => $validProperty,
        'missingReadingsTenant' => $missingReadingsTenant,
    ] = buildBulkInvoicePageScenario();

    actingAs($admin)
        ->get(route('filament.admin.pages.generate-bulk-invoices'))
        ->assertSuccessful()
        ->assertSeeText('Generate Bulk Invoices')
        ->assertSeeText('Billing Period')
        ->assertSeeText('Select Tenants')
        ->assertSeeText('Generate Invoices')
        ->assertSeeText('Cancel')
        ->assertSeeText('Select All')
        ->assertSeeText((string) $alreadyBilledTenant->name)
        ->assertSeeText((string) $validTenant->name)
        ->assertSeeText((string) $validProperty->name)
        ->assertSeeText('55 m²')
        ->assertSeeText('Already has an invoice for this period')
        ->assertSeeText('Number of invoices to be generated')
        ->assertSeeText('Estimated combined total')
        ->assertSeeText("17,50\u{00A0}€")
        ->assertSeeText('Tenants with no meter readings')
        ->assertSeeText((string) $missingReadingsTenant->name)
        ->assertDontSeeText('INV-OTHER-ORG-001');

    expect(Invoice::query()->where('organization_id', $organization->id)->count())->toBe(1);
});

it('blocks bulk invoice generation without an admin organization context', function () {
    $superadmin = User::factory()->superadmin()->create([
        'organization_id' => null,
    ]);

    actingAs($superadmin)
        ->get(route('filament.admin.pages.generate-bulk-invoices'))
        ->assertForbidden();
});

it('generates selected bulk invoices and exposes a filtered view of the created invoices', function () {
    [
        'organization' => $organization,
        'admin' => $admin,
        'alreadyBilledInvoice' => $alreadyBilledInvoice,
        'validTenant' => $validTenant,
    ] = buildBulkInvoicePageScenario();

    $component = Livewire::actingAs($admin)
        ->test(GenerateBulkInvoices::class)
        ->assertSeeText('Generate Bulk Invoices')
        ->assertSeeText('Number of invoices to be generated');

    $preview = $component->get('preview');

    $validAssignmentKey = collect($preview['valid'] ?? [])
        ->firstWhere('tenant_name', $validTenant->name)['assignment_key'] ?? null;

    expect($validAssignmentKey)->not->toBeNull();

    $component
        ->set('selectedAssignments', [$validAssignmentKey])
        ->call('generateInvoices')
        ->assertHasNoErrors()
        ->assertSeeText('View Created Invoices');

    $summary = $component->get('summary');

    $createdInvoice = Invoice::query()
        ->where('organization_id', $organization->id)
        ->where('tenant_user_id', $validTenant->id)
        ->whereDate('billing_period_start', now()->startOfMonth())
        ->whereDate('billing_period_end', now()->endOfMonth())
        ->latest('id')
        ->first();

    expect($createdInvoice)->not->toBeNull()
        ->and($summary['created'])->toBe(1)
        ->and($summary['failed'])->toBe(0)
        ->and($summary['view_url'])->toBe(route('filament.admin.resources.invoices.index', [
            'created_invoice_ids' => (string) $createdInvoice->id,
        ]));

    actingAs($admin)
        ->get($summary['view_url'])
        ->assertSuccessful()
        ->assertSeeText((string) $createdInvoice->invoice_number)
        ->assertDontSeeText((string) $alreadyBilledInvoice->invoice_number);
});

function buildBulkInvoicePageScenario(): array
{
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

    $alreadyBilledTenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $validTenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $missingReadingsTenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $alreadyBilledProperty = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-1',
    ]);
    $validProperty = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-2',
    ]);
    $missingReadingsProperty = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-3',
    ]);

    $alreadyBilledAssignment = PropertyAssignment::factory()->for($organization)->for($alreadyBilledProperty)->for($alreadyBilledTenant, 'tenant')->create([
        'unit_area_sqm' => 45,
    ]);
    $validAssignment = PropertyAssignment::factory()->for($organization)->for($validProperty)->for($validTenant, 'tenant')->create([
        'unit_area_sqm' => 55,
    ]);
    $missingReadingsAssignment = PropertyAssignment::factory()->for($organization)->for($missingReadingsProperty)->for($missingReadingsTenant, 'tenant')->create([
        'unit_area_sqm' => 62,
    ]);

    foreach ([$alreadyBilledProperty, $validProperty, $missingReadingsProperty] as $property) {
        ServiceConfiguration::factory()->for($organization)->for($property)->for($utilityService)->for($provider)->for($tariff)->create([
            'pricing_model' => PricingModel::CONSUMPTION_BASED,
            'distribution_method' => DistributionMethod::BY_CONSUMPTION,
            'rate_schedule' => ['unit_rate' => 1.75],
            'is_shared_service' => false,
        ]);
    }

    $alreadyBilledMeter = Meter::factory()->for($organization)->for($alreadyBilledProperty)->create([
        'type' => MeterType::WATER,
    ]);
    $validMeter = Meter::factory()->for($organization)->for($validProperty)->create([
        'type' => MeterType::WATER,
    ]);
    $missingReadingsMeter = Meter::factory()->for($organization)->for($missingReadingsProperty)->create([
        'type' => MeterType::WATER,
    ]);

    MeterReading::factory()->for($organization)->for($alreadyBilledProperty)->for($alreadyBilledMeter)->create([
        'reading_value' => 50,
        'reading_date' => now()->startOfMonth()->subDay()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);
    MeterReading::factory()->for($organization)->for($alreadyBilledProperty)->for($alreadyBilledMeter)->create([
        'reading_value' => 61,
        'reading_date' => now()->endOfMonth()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    MeterReading::factory()->for($organization)->for($validProperty)->for($validMeter)->create([
        'reading_value' => 50,
        'reading_date' => now()->startOfMonth()->subDay()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);
    MeterReading::factory()->for($organization)->for($validProperty)->for($validMeter)->create([
        'reading_value' => 60,
        'reading_date' => now()->endOfMonth()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    MeterReading::factory()->for($organization)->for($missingReadingsProperty)->for($missingReadingsMeter)->create([
        'reading_value' => 22,
        'reading_date' => now()->endOfMonth()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    $alreadyBilledInvoice = Invoice::factory()->for($organization)->for($alreadyBilledProperty)->for($alreadyBilledTenant, 'tenant')->create([
        'invoice_number' => 'INV-ALREADY-001',
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
    ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for($otherBuilding)->create();
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    Invoice::factory()->for($otherOrganization)->for($otherProperty)->for($otherTenant, 'tenant')->create([
        'invoice_number' => 'INV-OTHER-ORG-001',
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
    ]);

    return [
        'organization' => $organization,
        'admin' => $admin,
        'building' => $building,
        'provider' => $provider,
        'tariff' => $tariff,
        'utilityService' => $utilityService,
        'alreadyBilledTenant' => $alreadyBilledTenant,
        'validTenant' => $validTenant,
        'missingReadingsTenant' => $missingReadingsTenant,
        'alreadyBilledProperty' => $alreadyBilledProperty,
        'validProperty' => $validProperty,
        'missingReadingsProperty' => $missingReadingsProperty,
        'alreadyBilledAssignment' => $alreadyBilledAssignment,
        'validAssignment' => $validAssignment,
        'missingReadingsAssignment' => $missingReadingsAssignment,
        'alreadyBilledInvoice' => $alreadyBilledInvoice,
    ];
}
