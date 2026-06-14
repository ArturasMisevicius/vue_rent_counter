<?php

declare(strict_types=1);

use App\Enums\AssignmentScope;
use App\Enums\AuditLogAction;
use App\Enums\BillingFrequency;
use App\Enums\BillingMethod;
use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Enums\ServiceConfigurationStatus;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Actions\Admin\Invoices\OpenReadingInvoiceCycleAction;
use App\Filament\Actions\Admin\ServiceConfigurations\CreateServiceConfigurationAction;
use App\Filament\Resources\ServiceConfigurations\Pages\CreateServiceConfiguration;
use App\Filament\Resources\ServiceConfigurations\Pages\ListServiceConfigurations;
use App\Filament\Support\Admin\ServiceConfigurations\ValidateServiceConfiguration;
use App\Models\AuditLog;
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
use App\Services\Billing\InvoicePresentationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('wizard can create a meter based service configuration', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    $fixture = serviceConfigurationWizardFixture(ServiceType::WATER);

    Livewire::actingAs($fixture['admin'])
        ->test(CreateServiceConfiguration::class)
        ->fillForm(meterBasedWizardData($fixture))
        ->call('create')
        ->assertHasNoFormErrors();

    $configuration = ServiceConfiguration::query()
        ->where('organization_id', $fixture['organization']->id)
        ->where('service_name', 'Cold water by meter')
        ->firstOrFail();

    expect($configuration->billing_method)->toBe(BillingMethod::METER_BASED)
        ->and($configuration->status)->toBe(ServiceConfigurationStatus::ACTIVE)
        ->and($configuration->meter_rules['minimum_readings'])->toBe(2)
        ->and($configuration->tenant_visible_name)->toBe('Cold water');
});

it('wizard can create a fixed monthly service configuration', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    $fixture = serviceConfigurationWizardFixture(ServiceType::INTERNET);

    Livewire::actingAs($fixture['admin'])
        ->test(CreateServiceConfiguration::class)
        ->fillForm(fixedMonthlyWizardData($fixture))
        ->call('create')
        ->assertHasNoFormErrors();

    $configuration = ServiceConfiguration::query()
        ->where('organization_id', $fixture['organization']->id)
        ->where('service_name', 'Internet package')
        ->firstOrFail();

    expect($configuration->billing_method)->toBe(BillingMethod::FIXED_MONTHLY)
        ->and($configuration->fixed_amount)->toBe('49.90')
        ->and($configuration->billing_frequency)->toBe(BillingFrequency::MONTHLY)
        ->and((float) $configuration->rate_schedule['unit_rate'])->toBe(49.9);
});

it('blocks a meter based service without a tariff', function (): void {
    $result = app(ValidateServiceConfiguration::class)->handle([
        'service_name' => 'Water without tariff',
        'service_type' => ServiceType::WATER->value,
        'billing_method' => BillingMethod::METER_BASED->value,
        'unit' => 'm3',
        'meter_rules' => ['require_readings' => true, 'minimum_readings' => 2],
    ]);

    expect($result['status'])->toBe(ServiceConfigurationStatus::CONFIGURATION_ERROR->value)
        ->and($result['blocking_errors'])->toContain(__('admin.service_configurations.validation.errors.meter_tariff_required'));
});

it('blocks a fixed monthly service without an amount', function (): void {
    $result = app(ValidateServiceConfiguration::class)->handle([
        'service_name' => 'Internet package',
        'service_type' => ServiceType::INTERNET->value,
        'billing_method' => BillingMethod::FIXED_MONTHLY->value,
        'currency' => 'EUR',
        'billing_frequency' => BillingFrequency::MONTHLY->value,
    ]);

    expect($result['status'])->toBe(ServiceConfigurationStatus::CONFIGURATION_ERROR->value)
        ->and($result['blocking_errors'])->toContain(__('admin.service_configurations.validation.errors.fixed_amount_required'));
});

it('requires a tenant visible description when tenant visibility is enabled', function (): void {
    $result = app(ValidateServiceConfiguration::class)->handle([
        'service_name' => 'Garbage collection',
        'service_type' => ServiceType::WASTE->value,
        'billing_method' => BillingMethod::FIXED_MONTHLY->value,
        'fixed_amount' => '8.00',
        'currency' => 'EUR',
        'billing_frequency' => BillingFrequency::MONTHLY->value,
        'tenant_visible' => true,
        'tenant_visible_name' => 'Garbage collection',
    ]);

    expect($result['status'])->toBe(ServiceConfigurationStatus::CONFIGURATION_ERROR->value)
        ->and($result['blocking_errors'])->toContain(__('admin.service_configurations.validation.errors.tenant_description_required'));
});

it('blocks activation when configuration validation has blocking errors', function (): void {
    $fixture = serviceConfigurationWizardFixture(ServiceType::WATER);
    $data = meterBasedWizardData($fixture);
    unset($data['tariff_id']);

    expect(fn () => app(CreateServiceConfigurationAction::class)->handle($fixture['organization'], $data))
        ->toThrow(ValidationException::class);

    expect(ServiceConfiguration::query()->where('organization_id', $fixture['organization']->id)->count())->toBe(0);
});

it('adds a valid fixed service to generated invoices', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    $fixture = serviceConfigurationWizardFixture(ServiceType::INTERNET);
    $configuration = createFixedMonthlyConfiguration($fixture, '49.90');

    $result = app(GenerateBulkInvoicesAction::class)->handle($fixture['organization'], billingPeriodAttributes(), $fixture['admin']);

    /** @var Invoice $invoice */
    $invoice = $result['created']->sole();
    $item = $invoice->invoiceItems()->firstOrFail();

    expect($invoice->status)->toBe(InvoiceStatus::FINALIZED)
        ->and((float) $invoice->total_amount)->toBe(49.9)
        ->and($item->service_configuration_id)->toBe($configuration->id)
        ->and($item->description_for_tenant)->toBe('Internet')
        ->and($item->tenant_visible)->toBeTrue();
});

it('creates a reading request for a valid meter based service', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    Notification::fake();
    $fixture = serviceConfigurationWizardFixture(ServiceType::WATER);
    createMeterBasedConfiguration($fixture);

    Meter::factory()
        ->for($fixture['organization'])
        ->for($fixture['property'])
        ->create(['type' => MeterType::WATER]);

    $result = app(OpenReadingInvoiceCycleAction::class)->handle($fixture['organization'], billingPeriodAttributes(), $fixture['admin']);

    /** @var Invoice $invoice */
    $invoice = $result['created']->sole();

    expect($invoice->automation_level)->toBe('reading_request')
        ->and($invoice->approval_metadata['workflow'] ?? null)->toBe('meter_reading_request')
        ->and((float) $invoice->total_amount)->toBe(0.0)
        ->and($result['notified'])->toBe(1);
});

it('stores tariff snapshots on invoice items so old invoices do not change', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    $fixture = serviceConfigurationWizardFixture(ServiceType::WATER);
    $configuration = createMeterBasedConfiguration($fixture);

    $meter = Meter::factory()->for($fixture['organization'])->for($fixture['property'])->create([
        'type' => MeterType::WATER,
    ]);
    MeterReading::factory()->for($fixture['organization'])->for($fixture['property'])->for($meter)->create([
        'reading_value' => 10,
        'reading_date' => '2026-04-30',
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);
    MeterReading::factory()->for($fixture['organization'])->for($fixture['property'])->for($meter)->create([
        'reading_value' => 20,
        'reading_date' => '2026-05-31',
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);

    $invoice = app(GenerateBulkInvoicesAction::class)
        ->handle($fixture['organization'], billingPeriodAttributes(), $fixture['admin'])['created']
        ->sole();
    $item = $invoice->invoiceItems()->firstOrFail();

    $configuration->tariff->update([
        'name' => 'Changed tariff name',
        'configuration' => ['type' => 'flat', 'currency' => 'EUR', 'rate' => 99],
    ]);

    expect($item->tariff_snapshot['id'])->toBe($fixture['tariff']->id)
        ->and($item->tariff_snapshot['name'])->toBe('Standard tariff')
        ->and($item->tariff_snapshot['configuration']['rate'])->toBe(1.75);
});

it('does not duplicate service invoice items through multiple assignments', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    $fixture = serviceConfigurationWizardFixture(ServiceType::INTERNET);
    createFixedMonthlyConfiguration($fixture, '25.00');

    PropertyAssignment::factory()
        ->for($fixture['organization'])
        ->for($fixture['property'])
        ->for($fixture['tenant'], 'tenant')
        ->create([
            'assigned_at' => '2026-01-15 00:00:00',
            'unassigned_at' => null,
        ]);

    $result = app(GenerateBulkInvoicesAction::class)->handle($fixture['organization'], billingPeriodAttributes(), $fixture['admin']);

    /** @var Invoice $invoice */
    $invoice = $result['created']->sole();

    expect($invoice->invoiceItems()->count())->toBe(1)
        ->and(Invoice::query()
            ->where('organization_id', $fixture['organization']->id)
            ->where('tenant_user_id', $fixture['tenant']->id)
            ->whereDate('billing_period_start', '2026-05-01')
            ->count())->toBe(1);
});

it('does not expose service internal notes to tenant invoice presentation', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    $fixture = serviceConfigurationWizardFixture(ServiceType::INTERNET);
    createFixedMonthlyConfiguration($fixture, '49.90', [
        'internal_note' => 'Secret supplier escalation note',
    ]);

    $invoice = app(GenerateBulkInvoicesAction::class)
        ->handle($fixture['organization'], billingPeriodAttributes(), $fixture['admin'])['created']
        ->sole();
    $presentation = app(InvoicePresentationService::class)->present($invoice);
    $item = $invoice->invoiceItems()->firstOrFail();

    expect($item->internal_note)->toBeNull()
        ->and($item->service_snapshot)->not->toHaveKey('internal_note')
        ->and(json_encode($presentation, JSON_THROW_ON_ERROR))->not->toContain('Secret supplier escalation note');
});

it('keeps service configurations isolated by organization', function (): void {
    $fixture = serviceConfigurationWizardFixture(ServiceType::INTERNET);
    $otherFixture = serviceConfigurationWizardFixture(ServiceType::INTERNET);
    $visibleConfiguration = createFixedMonthlyConfiguration($fixture, '49.90');
    $hiddenConfiguration = createFixedMonthlyConfiguration($otherFixture, '39.90');

    Livewire::actingAs($fixture['admin'])
        ->test(ListServiceConfigurations::class)
        ->assertCanSeeTableRecords([$visibleConfiguration])
        ->assertCanNotSeeTableRecords([$hiddenConfiguration]);
});

it('audits service configuration changes', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    $fixture = serviceConfigurationWizardFixture(ServiceType::INTERNET);

    Livewire::actingAs($fixture['admin'])
        ->test(CreateServiceConfiguration::class)
        ->fillForm(fixedMonthlyWizardData($fixture))
        ->call('create')
        ->assertHasNoFormErrors();

    $configuration = ServiceConfiguration::query()
        ->where('organization_id', $fixture['organization']->id)
        ->where('service_name', 'Internet package')
        ->firstOrFail();
    $auditLog = AuditLog::query()->forSubject($configuration)->firstOrFail();

    expect($auditLog->organization_id)->toBe($fixture['organization']->id)
        ->and($auditLog->actor_user_id)->toBe($fixture['admin']->id)
        ->and($auditLog->action)->toBe(AuditLogAction::CREATED)
        ->and(data_get($auditLog->metadata, 'context.mutation'))->toBe('service_configuration.created');
});

/**
 * @return array{
 *     organization: Organization,
 *     admin: User,
 *     tenant: User,
 *     building: Building,
 *     property: Property,
 *     assignment: PropertyAssignment,
 *     utilityService: UtilityService,
 *     provider: Provider,
 *     tariff: Tariff
 * }
 */
function serviceConfigurationWizardFixture(ServiceType $serviceType): array
{
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create(['name' => 'A-101']);
    $tenant = User::factory()->tenant()->create(['organization_id' => $organization->id]);
    $assignment = PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => '2026-01-01 00:00:00',
            'unassigned_at' => null,
        ]);
    $provider = Provider::factory()->for($organization)->create([
        'name' => 'Provider One',
        'service_type' => $serviceType,
    ]);
    $tariff = Tariff::factory()->for($provider)->create([
        'name' => 'Standard tariff',
        'configuration' => ['type' => 'flat', 'currency' => 'EUR', 'rate' => 1.75],
    ]);
    $utilityService = UtilityService::factory()->for($organization)->create([
        'name' => $serviceType->getLabel(),
        'unit_of_measurement' => $serviceType->defaultUnit()->value,
        'service_type_bridge' => $serviceType,
    ]);

    return [
        'organization' => $organization,
        'admin' => $admin,
        'tenant' => $tenant,
        'building' => $building,
        'property' => $property,
        'assignment' => $assignment,
        'utilityService' => $utilityService,
        'provider' => $provider,
        'tariff' => $tariff,
    ];
}

/**
 * @param  array<string, mixed>  $fixture
 * @return array<string, mixed>
 */
function meterBasedWizardData(array $fixture): array
{
    return [
        'service_name' => 'Cold water by meter',
        'service_type' => ServiceType::WATER->value,
        'utility_service_id' => $fixture['utilityService']->id,
        'invoice_description' => 'Cold water usage',
        'billing_method' => BillingMethod::METER_BASED->value,
        'currency' => 'EUR',
        'billing_frequency' => BillingFrequency::MONTHLY->value,
        'rate_schedule' => ['unit_rate' => '1.75', 'base_fee' => '0'],
        'provider_id' => $fixture['provider']->id,
        'tariff_id' => $fixture['tariff']->id,
        'unit' => 'm3',
        'meter_rules' => ['require_readings' => true, 'allow_estimates' => false, 'minimum_readings' => 2],
        'property_id' => $fixture['property']->id,
        'assignment_scope' => AssignmentScope::PROPERTY->value,
        'starts_at' => '2026-05-01',
        'ends_at' => null,
        'effective_from' => '2026-05-01',
        'effective_until' => null,
        'status' => ServiceConfigurationStatus::ACTIVE->value,
        'assignment_rules' => ['prevent_duplicate_invoice_items' => true],
        'is_shared_service' => false,
        'distribution_method' => DistributionMethod::BY_CONSUMPTION->value,
        'tenant_visible' => true,
        'tenant_visible_name' => 'Cold water',
        'tenant_visible_description' => 'Cold water billed from approved readings.',
        'show_formula_to_tenant' => false,
        'show_provider_to_tenant' => true,
        'show_readings_to_tenant' => true,
        'internal_note' => null,
    ];
}

/**
 * @param  array<string, mixed>  $fixture
 * @return array<string, mixed>
 */
function fixedMonthlyWizardData(array $fixture): array
{
    return [
        'service_name' => 'Internet package',
        'service_type' => ServiceType::INTERNET->value,
        'utility_service_id' => $fixture['utilityService']->id,
        'invoice_description' => 'Internet package',
        'billing_method' => BillingMethod::FIXED_MONTHLY->value,
        'fixed_amount' => '49.90',
        'currency' => 'EUR',
        'billing_frequency' => BillingFrequency::MONTHLY->value,
        'rate_schedule' => ['unit_rate' => '49.90', 'base_fee' => '0'],
        'provider_id' => $fixture['provider']->id,
        'tariff_id' => $fixture['tariff']->id,
        'unit' => 'month',
        'meter_rules' => null,
        'property_id' => $fixture['property']->id,
        'assignment_scope' => AssignmentScope::PROPERTY->value,
        'starts_at' => '2026-05-01',
        'ends_at' => null,
        'effective_from' => '2026-05-01',
        'effective_until' => null,
        'status' => ServiceConfigurationStatus::ACTIVE->value,
        'assignment_rules' => ['prevent_duplicate_invoice_items' => true],
        'is_shared_service' => false,
        'distribution_method' => DistributionMethod::EQUAL->value,
        'tenant_visible' => true,
        'tenant_visible_name' => 'Internet',
        'tenant_visible_description' => 'Monthly internet access.',
        'show_formula_to_tenant' => false,
        'show_provider_to_tenant' => true,
        'show_readings_to_tenant' => false,
        'internal_note' => null,
    ];
}

/**
 * @param  array<string, mixed>  $fixture
 */
function createMeterBasedConfiguration(array $fixture): ServiceConfiguration
{
    return ServiceConfiguration::factory()
        ->for($fixture['organization'])
        ->for($fixture['property'])
        ->for($fixture['utilityService'])
        ->for($fixture['provider'])
        ->for($fixture['tariff'])
        ->create(meterBasedWizardData($fixture));
}

/**
 * @param  array<string, mixed>  $fixture
 * @param  array<string, mixed>  $overrides
 */
function createFixedMonthlyConfiguration(array $fixture, string $amount, array $overrides = []): ServiceConfiguration
{
    return ServiceConfiguration::factory()
        ->fixedMonthly($amount)
        ->for($fixture['organization'])
        ->for($fixture['property'])
        ->for($fixture['utilityService'])
        ->for($fixture['provider'])
        ->for($fixture['tariff'])
        ->create([
            ...fixedMonthlyWizardData($fixture),
            'fixed_amount' => $amount,
            'rate_schedule' => ['unit_rate' => $amount, 'base_fee' => '0'],
            ...$overrides,
        ]);
}

/**
 * @return array{billing_period_start: string, billing_period_end: string, due_date: string}
 */
function billingPeriodAttributes(): array
{
    return [
        'billing_period_start' => '2026-05-01',
        'billing_period_end' => '2026-05-31',
        'due_date' => '2026-06-14',
    ];
}
