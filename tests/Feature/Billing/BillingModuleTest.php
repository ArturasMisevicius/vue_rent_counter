<?php

use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Enums\PaymentMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Actions\Admin\Invoices\RecordInvoicePaymentAction;
use App\Filament\Actions\Admin\Invoices\SaveInvoiceDraftAction;
use App\Filament\Actions\Admin\MeterReadings\CreateMeterReadingAction;
use App\Livewire\Tenant\SubmitReadingPage;
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
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('creates a meter reading record from the tenant submission flow with valid data', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '245.125')
        ->set('readingDate', now()->toDateString())
        ->set('notes', 'Submitted from the billing feature test.')
        ->call('submit')
        ->assertHasNoErrors();

    expect(MeterReading::query()
        ->where('meter_id', $meter->id)
        ->where('submitted_by_user_id', $tenant->user->id)
        ->exists())->toBeTrue();
});

it('fails tenant submission validation when the reading value is lower than the previous reading', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withReadings()
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '120.000')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(['readingValue']);
});

it('fails tenant submission validation when the reading date is in the future', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '245.125')
        ->set('readingDate', now()->addDay()->toDateString())
        ->call('submit')
        ->assertHasErrors(['readingDate']);
});

it('saves anomalous readings but flags them for review', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 50,
        'reading_date' => now()->subDays(100)->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 100,
        'reading_date' => now()->subDays(70)->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    $flagged = app(CreateMeterReadingAction::class)->handle(
        meter: $meter,
        readingValue: 350,
        readingDate: now()->toDateString(),
        submittedBy: null,
        submissionMethod: MeterReadingSubmissionMethod::ADMIN_MANUAL,
    );

    expect($flagged->validation_status)->toBe(MeterReadingValidationStatus::FLAGGED)
        ->and($flagged->notes)->toContain('anomalous spike');
});

it('calculates invoice totals from valid meter readings and tariff rates', function () {
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
    ]);
    PropertyAssignment::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create();

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
        'reading_date' => now()->subMonth()->endOfMonth()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 60,
        'reading_date' => now()->endOfMonth()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    $result = app(GenerateBulkInvoicesAction::class)->handle($organization, [
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
    ], $admin);

    /** @var Invoice $invoice */
    $invoice = $result['created']->sole();

    expect((float) $invoice->total_amount)->toBe(17.5)
        ->and($invoice->status)->toBe(InvoiceStatus::FINALIZED);
});

it('prevents commercial edits after an invoice is finalized', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 100.00,
        'items' => [
            ['description' => 'Water usage', 'amount' => 100.00],
        ],
        'finalized_at' => null,
    ]);

    $finalized = app(FinalizeInvoiceAction::class)->handle($invoice, [
        'total_amount' => 125.00,
        'items' => [
            ['description' => 'Final water usage', 'amount' => 125.00],
        ],
    ]);

    expect(fn () => app(SaveInvoiceDraftAction::class)->handle($finalized->fresh(), [
        'total_amount' => 200.00,
        'items' => [
            ['description' => 'Changed after finalization', 'amount' => 200.00],
        ],
    ]))->toThrow(ValidationException::class);
});

it('marks a finalized invoice as paid when a payment is recorded', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $invoice = Invoice::factory()->for($organization)->create([
        'status' => InvoiceStatus::FINALIZED,
        'total_amount' => 125.00,
        'amount_paid' => 0,
        'paid_amount' => 0,
    ]);

    $paid = app(RecordInvoicePaymentAction::class)->handle($invoice, [
        'amount_paid' => 125.00,
        'method' => PaymentMethod::BANK_TRANSFER,
        'payment_reference' => 'BANK-001',
        'paid_at' => now(),
        'notes' => 'Settled in full.',
    ]);

    expect($paid->status)->toBe(InvoiceStatus::PAID)
        ->and((float) $paid->amount_paid)->toBe(125.0)
        ->and($paid->payment_reference)->toBe('BANK-001');
});
