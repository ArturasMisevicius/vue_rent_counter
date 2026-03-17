<?php

use App\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Actions\Admin\Invoices\RecordInvoicePaymentAction;
use App\Actions\Admin\Invoices\SaveInvoiceDraftAction;
use App\Actions\Admin\Invoices\SendInvoiceEmailAction;
use App\Actions\Admin\Invoices\SendInvoiceReminderAction;
use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Enums\PaymentMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\InvoicePayment;
use App\Models\InvoiceReminderLog;
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

uses(RefreshDatabase::class);

it('shows organization-scoped invoice resource pages and the bulk generation page', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
    ]);

    $invoice = Invoice::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create([
        'invoice_number' => 'INV-2026-001',
        'status' => InvoiceStatus::FINALIZED,
    ]);

    $otherOrganization = Organization::factory()->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for(Building::factory()->for($otherOrganization))->create();
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
    ]);
    $otherInvoice = Invoice::factory()->for($otherOrganization)->for($otherProperty)->for($otherTenant, 'tenant')->create([
        'invoice_number' => 'INV-FOREIGN-001',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText('Invoices')
        ->assertSeeText($invoice->invoice_number)
        ->assertSeeText('Finalized')
        ->assertDontSeeText($otherInvoice->invoice_number);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.create'))
        ->assertSuccessful()
        ->assertSeeText('Property')
        ->assertSeeText('Billing Period Start')
        ->assertSeeText('Billing Period End');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.view', $invoice))
        ->assertSuccessful()
        ->assertSeeText('Invoice Details')
        ->assertSeeText($invoice->invoice_number)
        ->assertSeeText($tenant->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.generate-bulk-invoices'))
        ->assertSuccessful()
        ->assertSeeText('Generate Bulk Invoices')
        ->assertSeeText('Billing Period Start');
});

it('creates drafts, finalizes invoices, records payments, and logs delivery actions', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'email' => 'tenant@example.com',
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create();

    $provider = Provider::factory()->for($organization)->create([
        'service_type' => ServiceType::ELECTRICITY,
    ]);
    $tariff = Tariff::factory()->for($provider)->create([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.25,
        ],
    ]);
    $utilityService = UtilityService::factory()->for($organization)->create([
        'name' => 'Electricity',
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'service_type_bridge' => ServiceType::ELECTRICITY,
    ]);
    ServiceConfiguration::factory()->for($organization)->for($property)->for($utilityService)->for($provider)->for($tariff)->create([
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'distribution_method' => DistributionMethod::BY_CONSUMPTION,
        'rate_schedule' => ['unit_rate' => 0.25],
    ]);

    $meter = Meter::factory()->for($organization)->for($property)->create([
        'type' => MeterType::ELECTRICITY,
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 100,
        'reading_date' => now()->subMonth()->endOfMonth()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 140,
        'reading_date' => now()->endOfMonth()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    $draft = app(SaveInvoiceDraftAction::class)->handle($organization, [
        'property_id' => $property->id,
        'tenant_user_id' => $tenant->id,
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
        'notes' => 'Generated from meter usage.',
    ], $admin);

    expect($draft->status)->toBe(InvoiceStatus::DRAFT)
        ->and($draft->invoiceItems)->toHaveCount(1)
        ->and((float) $draft->total_amount)->toBeGreaterThan(0);

    $finalized = app(FinalizeInvoiceAction::class)->handle($draft->fresh(), $admin);

    expect($finalized->status)->toBe(InvoiceStatus::FINALIZED)
        ->and($finalized->finalized_at)->not->toBeNull();

    expect(fn () => app(SaveInvoiceDraftAction::class)->handle($organization, [
        'invoice_id' => $finalized->id,
        'property_id' => $property->id,
        'tenant_user_id' => $tenant->id,
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
        'due_date' => now()->addDays(21)->toDateString(),
        'notes' => 'Should not update a finalized invoice.',
    ], $admin))->toThrow(ValidationException::class);

    $partiallyPaid = app(RecordInvoicePaymentAction::class)->handle($finalized->fresh(), [
        'amount' => 5.00,
        'method' => PaymentMethod::BANK_TRANSFER,
        'reference' => 'PAY-001',
        'paid_at' => now(),
    ]);

    expect($partiallyPaid->status)->toBe(InvoiceStatus::PARTIALLY_PAID);

    $paid = app(RecordInvoicePaymentAction::class)->handle($partiallyPaid->fresh(), [
        'amount' => (float) $partiallyPaid->fresh()->total_amount - 5.00,
        'method' => PaymentMethod::CARD,
        'reference' => 'PAY-002',
        'paid_at' => now(),
    ]);

    expect($paid->status)->toBe(InvoiceStatus::PAID)
        ->and($paid->paid_at)->not->toBeNull();

    app(SendInvoiceEmailAction::class)->handle($paid->fresh(), $admin);
    app(SendInvoiceReminderAction::class)->handle($paid->fresh(), $admin);

    expect(InvoicePayment::query()->where('invoice_id', $paid->id)->count())->toBe(2)
        ->and(InvoiceEmailLog::query()->where('invoice_id', $paid->id)->count())->toBe(1)
        ->and(InvoiceReminderLog::query()->where('invoice_id', $paid->id)->count())->toBe(1);
});
