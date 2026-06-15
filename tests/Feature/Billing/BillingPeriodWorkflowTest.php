<?php

use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Enums\BillingMethod;
use App\Enums\DistributionMethod;
use App\Filament\Resources\BillingPeriods\Pages\CreateBillingPeriod;
use App\Filament\Resources\BillingPeriods\Pages\ListBillingPeriods;
use App\Filament\Support\Admin\BillingPeriods\BillingPeriodScopeSnapshotBuilder;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UtilityService;
use App\Notifications\Billing\InvoiceReadingRequestNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('shows organization-scoped billing period resource pages', function (): void {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $period = BillingPeriod::factory()->for($organization)->create([
        'name' => 'January 2026',
        'starts_at' => '2026-01-01',
        'ends_at' => '2026-01-31',
        'reading_submission_deadline' => '2026-02-05',
        'invoice_generation_date' => '2026-02-06',
        'payment_due_date' => '2026-02-20',
    ]);

    $hiddenPeriod = BillingPeriod::factory()->for($otherOrganization)->create([
        'name' => 'Hidden February 2026',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.billing-periods.index'))
        ->assertSuccessful()
        ->assertSeeText('Billing Periods')
        ->assertSeeText('January 2026')
        ->assertSeeText('Reading submission deadline')
        ->assertDontSeeText('Hidden February 2026');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.billing-periods.create'))
        ->assertSuccessful()
        ->assertSeeText('New Billing Period')
        ->assertSeeText('Invoice generation date');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.billing-periods.view', $period))
        ->assertSuccessful()
        ->assertSeeText('January 2026')
        ->assertSeeText('Payment due date');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.billing-periods.edit', $period))
        ->assertSuccessful()
        ->assertSeeText('Save changes');

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.billing-periods.index'))
        ->assertSuccessful()
        ->assertSeeText('January 2026');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.billing-periods.view', $hiddenPeriod))
        ->assertNotFound();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.billing-periods.index'))
        ->assertSuccessful()
        ->assertSeeText('January 2026')
        ->assertSeeText('Hidden February 2026');
});

it('creates a billing period with reading, generation, and payment deadlines', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($admin)
        ->test(CreateBillingPeriod::class)
        ->fillForm([
            'name' => 'March 2026',
            'starts_at' => '2026-03-01',
            'ends_at' => '2026-03-31',
            'reading_submission_deadline' => '2026-04-05',
            'invoice_generation_date' => '2026-04-06',
            'payment_due_date' => '2026-04-20',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $period = BillingPeriod::query()->sole();

    expect($period->organization_id)->toBe($organization->id)
        ->and($period->name)->toBe('March 2026')
        ->and($period->starts_at?->toDateString())->toBe('2026-03-01')
        ->and($period->ends_at?->toDateString())->toBe('2026-03-31')
        ->and($period->reading_submission_deadline?->toDateString())->toBe('2026-04-05')
        ->and($period->invoice_generation_date?->toDateString())->toBe('2026-04-06')
        ->and($period->payment_due_date?->toDateString())->toBe('2026-04-20');
});

it('builds a billing period scope snapshot with tenants, properties, meters, services, and tariffs', function (): void {
    [
        'billingPeriod' => $billingPeriod,
        'tenant' => $tenant,
        'property' => $property,
        'meter' => $meter,
        'serviceConfiguration' => $serviceConfiguration,
        'tariff' => $tariff,
    ] = billingPeriodWorkflowFixture();

    Organization::factory()
        ->has(BillingPeriod::factory()->state([
            'name' => 'Hidden Organization Period',
            'starts_at' => '2026-05-01',
            'ends_at' => '2026-05-31',
        ]))
        ->create();

    $snapshot = app(BillingPeriodScopeSnapshotBuilder::class)->handle($billingPeriod);

    expect($snapshot['billing_period']['name'])->toBe('May 2026')
        ->and($snapshot['totals'])->toMatchArray([
            'assignments' => 1,
            'tenants' => 1,
            'properties' => 1,
            'meters' => 1,
            'services' => 1,
            'tariffs' => 1,
        ])
        ->and($snapshot['assignments'][0]['tenant'])->toMatchArray([
            'id' => $tenant->id,
            'name' => $tenant->name,
        ])
        ->and($snapshot['assignments'][0]['property'])->toMatchArray([
            'id' => $property->id,
            'name' => $property->displayName(),
        ])
        ->and($snapshot['assignments'][0]['meters'][0])->toMatchArray([
            'id' => $meter->id,
            'identifier' => 'EL-2026-001',
            'type' => MeterType::ELECTRICITY->value,
        ])
        ->and($snapshot['assignments'][0]['services'][0])->toMatchArray([
            'id' => $serviceConfiguration->id,
            'name' => 'Electricity May',
            'pricing_model' => PricingModel::TIME_OF_USE->value,
            'tariff' => [
                'id' => $tariff->id,
                'name' => $tariff->name,
            ],
        ]);
});

it('opens a reading cycle directly from a billing period', function (): void {
    Carbon::setTestNow('2026-06-01 08:00:00');
    Notification::fake();

    [
        'billingPeriod' => $billingPeriod,
        'tenant' => $tenant,
        'admin' => $admin,
    ] = billingPeriodWorkflowFixture();

    Livewire::actingAs($admin)
        ->test(ListBillingPeriods::class)
        ->assertTableActionExists('openReadingCycle', record: $billingPeriod)
        ->callTableAction('openReadingCycle', $billingPeriod)
        ->assertHasNoTableActionErrors();

    $invoice = Invoice::query()
        ->where('billing_period_id', $billingPeriod->id)
        ->where('tenant_user_id', $tenant->id)
        ->firstOrFail();

    expect($invoice->billing_period_start?->toDateString())->toBe('2026-05-01')
        ->and($invoice->billing_period_end?->toDateString())->toBe('2026-05-31')
        ->and($invoice->due_date?->toDateString())->toBe('2026-06-05')
        ->and($invoice->approval_metadata['invoice_generation_date'] ?? null)->toBe('2026-06-06')
        ->and($invoice->approval_metadata['payment_due_date'] ?? null)->toBe('2026-06-20');

    Notification::assertSentTo($tenant, InvoiceReadingRequestNotification::class);
});

function billingPeriodWorkflowFixture(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Billing Period Tenant',
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Apartment 501',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => '2026-01-01 00:00:00',
            'unassigned_at' => null,
        ]);

    $meter = Meter::factory()
        ->for($organization)
        ->for($property)
        ->create([
            'name' => 'Main Electricity',
            'identifier' => 'EL-2026-001',
            'type' => MeterType::ELECTRICITY,
            'unit' => MeterType::ELECTRICITY->defaultUnit()->value,
        ]);

    $provider = Provider::factory()->forOrganization($organization)->create([
        'name' => 'Ignitis',
        'service_type' => ServiceType::ELECTRICITY,
    ]);
    $tariff = Tariff::factory()->for($provider)->timeOfUse()->create([
        'name' => 'Electricity TOU 2026',
        'active_from' => '2026-01-01 00:00:00',
        'active_until' => null,
    ]);
    $utilityService = UtilityService::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Electricity',
        'service_type_bridge' => ServiceType::ELECTRICITY,
        'default_pricing_model' => PricingModel::TIME_OF_USE,
    ]);
    $serviceConfiguration = ServiceConfiguration::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'service_name' => 'Electricity May',
        'service_type' => ServiceType::ELECTRICITY,
        'billing_method' => BillingMethod::METER_BASED,
        'distribution_method' => DistributionMethod::BY_CONSUMPTION,
        'unit' => MeterType::ELECTRICITY->defaultUnit()->value,
        'tenant_visible_name' => 'Electricity May',
        'pricing_model' => PricingModel::TIME_OF_USE,
        'provider_id' => $provider->id,
        'tariff_id' => $tariff->id,
        'effective_from' => '2026-01-01 00:00:00',
        'effective_until' => null,
    ]);

    $billingPeriod = BillingPeriod::factory()->for($organization)->create([
        'name' => 'May 2026',
        'starts_at' => '2026-05-01',
        'ends_at' => '2026-05-31',
        'reading_submission_deadline' => '2026-06-05',
        'invoice_generation_date' => '2026-06-06',
        'payment_due_date' => '2026-06-20',
    ]);

    return [
        'organization' => $organization,
        'admin' => $admin,
        'tenant' => $tenant,
        'property' => $property,
        'meter' => $meter,
        'provider' => $provider,
        'tariff' => $tariff,
        'utilityService' => $utilityService,
        'serviceConfiguration' => $serviceConfiguration,
        'billingPeriod' => $billingPeriod,
    ];
}
