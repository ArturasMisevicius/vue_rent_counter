<?php

declare(strict_types=1);

use App\Enums\AuditLogAction;
use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Actions\Admin\Invoices\RecordInvoicePaymentAction;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceGenerationAudit;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('captures actor, workspace, and before-after context for invoice finalization and payment mutations', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 125.00,
            'amount_paid' => 0,
            'paid_amount' => 0,
            'payment_reference' => null,
            'finalized_at' => null,
        ]);

    $this->actingAs($admin);

    $finalized = app(FinalizeInvoiceAction::class)->handle($invoice, [
        'total_amount' => 150.00,
    ]);

    $paid = app(RecordInvoicePaymentAction::class)->handle($finalized, [
        'amount_paid' => 150.00,
        'payment_reference' => 'BANK-150',
        'paid_at' => now()->toDateTimeString(),
    ]);

    $finalizationAudit = AuditLog::query()
        ->forSubject($invoice)
        ->forAction(AuditLogAction::APPROVED)
        ->latest('id')
        ->first();

    $paymentAudit = AuditLog::query()
        ->forSubject($invoice)
        ->forAction(AuditLogAction::UPDATED)
        ->latest('id')
        ->first();

    expect(AuditLog::query()
        ->forSubject($invoice)
        ->forAction(AuditLogAction::APPROVED)
        ->count())->toBe(1);

    expect(AuditLog::query()
        ->forSubject($invoice)
        ->forAction(AuditLogAction::UPDATED)
        ->count())->toBe(1);

    expect($finalizationAudit)
        ->not->toBeNull()
        ->organization_id->toBe($organization->id)
        ->actor_user_id->toBe($admin->id)
        ->and(data_get($finalizationAudit?->metadata, 'before.status'))->toBe(InvoiceStatus::DRAFT->value)
        ->and(data_get($finalizationAudit?->metadata, 'after.status'))->toBe(InvoiceStatus::FINALIZED->value)
        ->and(data_get($finalizationAudit?->metadata, 'before.total_amount'))->toBe(125)
        ->and(data_get($finalizationAudit?->metadata, 'after.total_amount'))->toBe(150)
        ->and(data_get($finalizationAudit?->metadata, 'context.mutation'))->toBe('invoice.finalized');

    expect($paymentAudit)
        ->not->toBeNull()
        ->organization_id->toBe($organization->id)
        ->actor_user_id->toBe($admin->id)
        ->and(data_get($paymentAudit?->metadata, 'before.status'))->toBe(InvoiceStatus::FINALIZED->value)
        ->and(data_get($paymentAudit?->metadata, 'after.status'))->toBe(InvoiceStatus::PAID->value)
        ->and(data_get($paymentAudit?->metadata, 'before.amount_paid'))->toBe(0)
        ->and(data_get($paymentAudit?->metadata, 'after.amount_paid'))->toBe(150)
        ->and(data_get($paymentAudit?->metadata, 'after.payment_reference'))->toBe('BANK-150')
        ->and(data_get($paymentAudit?->metadata, 'context.mutation'))->toBe('invoice.payment_recorded');

    $finalizationActivity = OrganizationActivityLog::query()
        ->forOrganization($organization->id)
        ->forResource($invoice)
        ->where('action', AuditLogAction::APPROVED->value)
        ->latest('id')
        ->first();

    $paymentActivity = OrganizationActivityLog::query()
        ->forOrganization($organization->id)
        ->forResource($invoice)
        ->where('action', AuditLogAction::UPDATED->value)
        ->latest('id')
        ->first();

    expect(OrganizationActivityLog::query()
        ->forOrganization($organization->id)
        ->forResource($invoice)
        ->where('action', AuditLogAction::APPROVED->value)
        ->count())->toBe(1);

    expect(OrganizationActivityLog::query()
        ->forOrganization($organization->id)
        ->forResource($invoice)
        ->where('action', AuditLogAction::UPDATED->value)
        ->count())->toBe(1);

    expect($finalizationActivity)
        ->not->toBeNull()
        ->user_id->toBe($admin->id)
        ->and(data_get($finalizationActivity?->metadata, 'after.status'))->toBe(InvoiceStatus::FINALIZED->value)
        ->and(data_get($finalizationActivity?->metadata, 'context.mutation'))->toBe('invoice.finalized');

    expect($paymentActivity)
        ->not->toBeNull()
        ->user_id->toBe($admin->id)
        ->and(data_get($paymentActivity?->metadata, 'after.status'))->toBe($paid->status->value)
        ->and(data_get($paymentActivity?->metadata, 'context.mutation'))->toBe('invoice.payment_recorded');
});

it('records bulk invoice generation audits with actor and workspace context', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-1',
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

    $this->actingAs($admin);

    $result = app(GenerateBulkInvoicesAction::class)->handle($organization, [
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
    ], $admin);

    $invoice = $result['created']->sole();

    $generationAudit = InvoiceGenerationAudit::query()
        ->where('invoice_id', $invoice->id)
        ->latest('id')
        ->first();

    expect($generationAudit)
        ->not->toBeNull()
        ->organization_id->toBe($organization->id)
        ->tenant_user_id->toBe($tenant->id)
        ->user_id->toBe($admin->id)
        ->items_count->toBe(count($invoice->items))
        ->and(data_get($generationAudit?->metadata, 'workspace.organization_id'))->toBe($organization->id)
        ->and(data_get($generationAudit?->metadata, 'workspace.property_id'))->toBe($property->id)
        ->and(data_get($generationAudit?->metadata, 'workspace.tenant_user_id'))->toBe($tenant->id)
        ->and(data_get($generationAudit?->metadata, 'context.mutation'))->toBe('invoice.generated')
        ->and(data_get($generationAudit?->metadata, 'invoice_number'))->toBe($invoice->invoice_number);
});
