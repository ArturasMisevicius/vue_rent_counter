<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Invoices\OpenReadingInvoiceCycleAction;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\ServiceConfiguration;
use App\Models\User;
use App\Models\UtilityService;
use App\Notifications\Billing\InvoiceReadingRequestNotification;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('opens empty draft invoices and notifies tenants for active metered assignments', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    Notification::fake();

    [
        'organization' => $organization,
        'admin' => $admin,
        'tenant' => $tenant,
    ] = buildOpenReadingInvoiceCycleScenario();

    $result = app(OpenReadingInvoiceCycleAction::class)->handle($organization, [
        'billing_period_start' => '2026-05-01',
        'billing_period_end' => '2026-05-31',
        'due_date' => '2026-06-14',
    ], $admin);

    /** @var Invoice $invoice */
    $invoice = $result['created']->first();
    /** @var BillingPeriod $billingPeriod */
    $billingPeriod = $result['billing_period'];

    expect($result['created'])->toHaveCount(1)
        ->and($result['skipped'])->toBe([])
        ->and($result['notified'])->toBe(1)
        ->and($billingPeriod->organization_id)->toBe($organization->id)
        ->and($billingPeriod->name)->toBe('May 2026')
        ->and($billingPeriod->starts_at?->toDateString())->toBe('2026-05-01')
        ->and($billingPeriod->ends_at?->toDateString())->toBe('2026-05-31')
        ->and($billingPeriod->reading_submission_deadline?->toDateString())->toBe('2026-06-14')
        ->and($billingPeriod->invoice_generation_date?->toDateString())->toBe('2026-06-14')
        ->and($billingPeriod->payment_due_date?->toDateString())->toBe('2026-06-28')
        ->and($invoice->billing_period_id)->toBe($billingPeriod->id)
        ->and($invoice->status)->toBe(InvoiceStatus::DRAFT)
        ->and((float) $invoice->total_amount)->toBe(0.0)
        ->and($invoice->items)->toBe([])
        ->and($invoice->invoiceItems)->toHaveCount(0)
        ->and($invoice->automation_level)->toBe('reading_request')
        ->and($invoice->approval_status)->toBe('waiting_for_readings')
        ->and($invoice->generated_by)->toBe("user:{$admin->id}")
        ->and($invoice->approval_metadata['workflow'] ?? null)->toBe('meter_reading_request')
        ->and($invoice->approval_metadata['request_status'] ?? null)->toBe('waiting_for_readings')
        ->and($invoice->approval_metadata['billing_period_id'] ?? null)->toBe($billingPeriod->id)
        ->and($invoice->approval_metadata['reading_submission_deadline'] ?? null)->toBe('2026-06-14')
        ->and($invoice->approval_metadata['invoice_generation_date'] ?? null)->toBe('2026-06-14')
        ->and($invoice->approval_metadata['payment_due_date'] ?? null)->toBe('2026-06-28')
        ->and($invoice->approval_metadata['tenant']['id'] ?? null)->toBe($tenant->id)
        ->and($invoice->approval_metadata['linked_meters'])->toHaveCount(1)
        ->and($invoice->approval_metadata['linked_meters'][0]['identifier'] ?? null)->toBe('EL-APT-12')
        ->and($invoice->approval_metadata['linked_meters'][0]['status'] ?? null)->toBe('pending')
        ->and($invoice->approval_metadata['expected_services'])->toHaveCount(1)
        ->and($invoice->approval_metadata['expected_services'][0]['name'] ?? null)->toBe('Electricity')
        ->and($invoice->approval_metadata['expected_services'][0]['requires_reading'] ?? null)->toBeTrue()
        ->and($invoice->approval_metadata['required_inputs'])->toHaveCount(1)
        ->and($invoice->approval_metadata['required_inputs'][0]['meter_identifier'] ?? null)->toBe('EL-APT-12')
        ->and($invoice->approval_metadata['required_inputs'][0]['status'] ?? null)->toBe('pending');

    Notification::assertSentTo(
        $tenant,
        InvoiceReadingRequestNotification::class,
        function (InvoiceReadingRequestNotification $notification, array $channels) use ($invoice, $tenant): bool {
            $payload = $notification->toArray($tenant);
            $mail = $notification->toMail($tenant);

            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && $payload['invoice_id'] === $invoice->id
                && $payload['url'] === route('tenant.readings.create', ['invoice' => $invoice->id], false)
                && $payload['billing_period_name'] === 'May 2026'
                && $payload['reading_submission_deadline'] === '2026-06-14'
                && str_contains((string) $payload['body'], 'May 2026')
                && str_contains((string) $mail->subject, 'May 2026');
        },
    );
});

it('does not create duplicate reading request invoices for the same tenant property period', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    Notification::fake();

    [
        'organization' => $organization,
        'admin' => $admin,
        'tenant' => $tenant,
    ] = buildOpenReadingInvoiceCycleScenario();

    $attributes = [
        'billing_period_start' => '2026-05-01',
        'billing_period_end' => '2026-05-31',
        'due_date' => '2026-06-14',
    ];

    app(OpenReadingInvoiceCycleAction::class)->handle($organization, $attributes, $admin);
    $secondRun = app(OpenReadingInvoiceCycleAction::class)->handle($organization, $attributes, $admin);

    expect($secondRun['created'])->toHaveCount(0)
        ->and($secondRun['skipped'])->toHaveCount(1)
        ->and($secondRun['skipped'][0]['reason'])->toBe('already_open')
        ->and(BillingPeriod::query()
            ->where('organization_id', $organization->id)
            ->whereDate('starts_at', '2026-05-01')
            ->whereDate('ends_at', '2026-05-31')
            ->count())->toBe(1)
        ->and(Invoice::query()
            ->where('organization_id', $organization->id)
            ->where('tenant_user_id', $tenant->id)
            ->whereDate('billing_period_start', '2026-05-01')
            ->whereDate('billing_period_end', '2026-05-31')
            ->count())->toBe(1);
});

it('opens a reading invoice cycle from the artisan command', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    Notification::fake();

    [
        'organization' => $organization,
        'tenant' => $tenant,
    ] = buildOpenReadingInvoiceCycleScenario();

    $this->artisan('billing:open-reading-invoice-cycle', [
        '--organization' => [$organization->id],
        '--period' => '2026-05',
    ])->assertExitCode(0);

    $invoice = Invoice::query()
        ->where('organization_id', $organization->id)
        ->where('tenant_user_id', $tenant->id)
        ->firstOrFail();

    expect($invoice->billing_period_start?->toDateString())->toBe('2026-05-01')
        ->and($invoice->billing_period_end?->toDateString())->toBe('2026-05-31')
        ->and($invoice->due_date?->toDateString())->toBe('2026-06-14')
        ->and($invoice->billingPeriod?->name)->toBe('May 2026')
        ->and($invoice->billingPeriod?->reading_submission_deadline?->toDateString())->toBe('2026-06-14')
        ->and($invoice->status)->toBe(InvoiceStatus::DRAFT);

    Notification::assertSentTo($tenant, InvoiceReadingRequestNotification::class);
});

it('schedules the automatic reading invoice cycle monthly', function (): void {
    $scheduledEvent = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'billing:open-reading-invoice-cycle'));

    expect($scheduledEvent)->not->toBeNull()
        ->and($scheduledEvent->getExpression())->toBe('0 8 1 * *');
});

it('opens a reading invoice cycle from the invoices list action', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    Notification::fake();

    [
        'organization' => $organization,
        'admin' => $admin,
        'tenant' => $tenant,
    ] = buildOpenReadingInvoiceCycleScenario();

    Livewire::actingAs($admin)
        ->test(ListInvoices::class)
        ->assertActionVisible('openReadingCycle')
        ->callAction('openReadingCycle', data: [
            'billing_period_start' => '2026-05-01',
            'billing_period_end' => '2026-05-31',
            'due_date' => '2026-06-14',
        ])
        ->assertHasNoActionErrors();

    $invoice = Invoice::query()
        ->where('organization_id', $organization->id)
        ->where('tenant_user_id', $tenant->id)
        ->firstOrFail();

    expect($invoice->automation_level)->toBe('reading_request')
        ->and($invoice->status)->toBe(InvoiceStatus::DRAFT)
        ->and((float) $invoice->total_amount)->toBe(0.0);

    Notification::assertSentTo($tenant, InvoiceReadingRequestNotification::class);
});

function buildOpenReadingInvoiceCycleScenario(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => '2026-01-01 00:00:00',
            'unassigned_at' => null,
        ]);

    Meter::factory()
        ->for($organization)
        ->for($property)
        ->create([
            'name' => 'Apartment 12 Electricity',
            'identifier' => 'EL-APT-12',
            'type' => MeterType::ELECTRICITY,
            'unit' => 'kWh',
        ]);

    $utilityService = UtilityService::factory()
        ->for($organization)
        ->create([
            'name' => 'Electricity',
            'unit_of_measurement' => 'kWh',
            'service_type_bridge' => ServiceType::ELECTRICITY,
            'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        ]);

    ServiceConfiguration::factory()
        ->for($organization)
        ->for($property)
        ->for($utilityService, 'utilityService')
        ->create([
            'service_name' => 'Electricity',
            'tenant_visible_name' => 'Electricity',
            'service_type' => ServiceType::ELECTRICITY,
            'pricing_model' => PricingModel::CONSUMPTION_BASED,
            'effective_from' => '2026-01-01 00:00:00',
            'effective_until' => null,
            'is_active' => true,
        ]);

    return [
        'organization' => $organization,
        'admin' => $admin,
        'tenant' => $tenant,
        'property' => $property,
    ];
}
