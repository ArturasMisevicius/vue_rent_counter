<?php

use App\Enums\BillingFrequency;
use App\Enums\BillingMethod;
use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\PropertyAssignmentStatus;
use App\Enums\ServiceType;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Billing\GenerateDraftInvoicesForBillingPeriod;
use App\Filament\Support\Notifications\DomainNotificationCatalog;
use App\Models\BillingGenerationLog;
use App\Models\BillingGenerationLogItem;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UtilityService;
use App\Notifications\Billing\BillingGenerationSummaryNotification;
use App\Notifications\Billing\InvoiceReadingRequestNotification;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('scheduled generation creates a billing period', function (): void {
    Carbon::setTestNow('2026-06-15 08:00:00');
    Notification::fake();

    ['organization' => $organization] = automaticDraftInvoiceFixture();

    $this->artisan('billing:generate-draft-invoices', [
        '--organization' => [$organization->id],
        '--date' => '2026-06-15',
    ])->assertExitCode(0);

    $period = BillingPeriod::query()
        ->where('organization_id', $organization->id)
        ->firstOrFail();

    expect($period->name)->toBe('May 2026')
        ->and($period->starts_at?->toDateString())->toBe('2026-05-01')
        ->and($period->ends_at?->toDateString())->toBe('2026-05-31')
        ->and($period->reading_submission_deadline?->toDateString())->toBe('2026-06-20')
        ->and($period->invoice_generation_date?->toDateString())->toBe('2026-06-15')
        ->and($period->payment_due_date?->toDateString())->toBe('2026-06-30');
});

it('scheduled generation creates draft invoices', function (): void {
    Carbon::setTestNow('2026-06-15 08:00:00');
    Notification::fake();

    ['organization' => $organization, 'tenant' => $tenant] = automaticDraftInvoiceFixture();

    $this->artisan('billing:generate-draft-invoices', [
        '--organization' => [$organization->id],
        '--date' => '2026-06-15',
    ])->assertExitCode(0);

    $invoice = Invoice::query()
        ->where('organization_id', $organization->id)
        ->where('tenant_user_id', $tenant->id)
        ->firstOrFail();

    expect($invoice->status)->toBe(InvoiceStatus::DRAFT)
        ->and($invoice->approval_status)->toBe('waiting_for_readings')
        ->and($invoice->automation_level)->toBe('reading_request')
        ->and($invoice->billingPeriod?->name)->toBe('May 2026')
        ->and((float) $invoice->total_amount)->toBe(0.0)
        ->and(BillingGenerationLog::query()->where('organization_id', $organization->id)->value('source'))->toBe('scheduled');
});

it('does not create duplicate active invoices for the same tenant property period', function (): void {
    Carbon::setTestNow('2026-06-15 08:00:00');
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin, 'tenant' => $tenant] = automaticDraftInvoiceFixture();

    $attributes = automaticDraftPeriodData();

    app(GenerateDraftInvoicesForBillingPeriod::class)->handle($organization, $attributes, $admin);
    $secondRun = app(GenerateDraftInvoicesForBillingPeriod::class)->handle($organization, $attributes, $admin);

    expect($secondRun['created'])->toHaveCount(0)
        ->and($secondRun['skipped'])->toHaveCount(1)
        ->and($secondRun['skipped'][0]['code'])->toBe('duplicate_active_invoice')
        ->and(Invoice::query()
            ->where('organization_id', $organization->id)
            ->where('tenant_user_id', $tenant->id)
            ->whereDate('billing_period_start', '2026-05-01')
            ->whereDate('billing_period_end', '2026-05-31')
            ->count())->toBe(1)
        ->and(BillingGenerationLog::query()->where('organization_id', $organization->id)->count())->toBe(2);
});

it('dry run previews generation without persisting data', function (): void {
    Carbon::setTestNow('2026-06-15 08:00:00');
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin] = automaticDraftInvoiceFixture();

    $result = app(GenerateDraftInvoicesForBillingPeriod::class)->handle(
        organization: $organization,
        billingPeriod: automaticDraftPeriodData(),
        actor: $admin,
        dryRun: true,
        source: 'manual_preview',
    );

    expect($result['preview'])->toHaveCount(1)
        ->and($result['summary']['eligible'])->toBe(1)
        ->and($result['summary']['created'])->toBe(0)
        ->and(BillingPeriod::query()->where('organization_id', $organization->id)->count())->toBe(0)
        ->and(Invoice::query()->where('organization_id', $organization->id)->count())->toBe(0)
        ->and(BillingGenerationLog::query()->where('organization_id', $organization->id)->count())->toBe(0);

    Notification::assertNothingSent();
});

it('skips inactive tenants', function (): void {
    Carbon::setTestNow('2026-06-15 08:00:00');
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin] = automaticDraftInvoiceFixture([
        'tenant_attributes' => [
            'status' => UserStatus::INACTIVE,
            'tenant_status' => TenantStatus::INACTIVE,
        ],
    ]);

    $result = app(GenerateDraftInvoicesForBillingPeriod::class)->handle($organization, automaticDraftPeriodData(), $admin);

    expect($result['created'])->toHaveCount(0)
        ->and($result['skipped'])->toHaveCount(1)
        ->and($result['skipped'][0]['code'])->toBe('inactive_tenant')
        ->and(Invoice::query()->where('organization_id', $organization->id)->count())->toBe(0)
        ->and(BillingGenerationLogItem::query()->where('organization_id', $organization->id)->where('code', 'inactive_tenant')->exists())->toBeTrue();
});

it('skips inactive property assignments', function (): void {
    Carbon::setTestNow('2026-06-15 08:00:00');
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin] = automaticDraftInvoiceFixture([
        'assignment_attributes' => [
            'status' => PropertyAssignmentStatus::ENDED,
        ],
    ]);

    $result = app(GenerateDraftInvoicesForBillingPeriod::class)->handle($organization, automaticDraftPeriodData(), $admin);

    expect($result['created'])->toHaveCount(0)
        ->and($result['skipped'])->toHaveCount(1)
        ->and($result['skipped'][0]['code'])->toBe('inactive_assignment')
        ->and(Invoice::query()->where('organization_id', $organization->id)->count())->toBe(0)
        ->and(BillingGenerationLogItem::query()->where('organization_id', $organization->id)->where('code', 'inactive_assignment')->exists())->toBeTrue();
});

it('blocks tenant notification when a metered service is missing a tariff', function (): void {
    Carbon::setTestNow('2026-06-15 08:00:00');
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin, 'tenant' => $tenant] = automaticDraftInvoiceFixture([
        'service_mode' => 'missing_tariff',
    ]);

    $result = app(GenerateDraftInvoicesForBillingPeriod::class)->handle($organization, automaticDraftPeriodData(), $admin);

    $invoice = Invoice::query()
        ->where('organization_id', $organization->id)
        ->where('tenant_user_id', $tenant->id)
        ->firstOrFail();

    expect($result['errors'])->toHaveCount(1)
        ->and($result['notified'])->toBe(0)
        ->and($invoice->approval_status)->toBe('configuration_error')
        ->and($invoice->automation_level)->toBe('automatic_draft')
        ->and(BillingGenerationLogItem::query()->where('organization_id', $organization->id)->where('code', 'configuration_error')->exists())->toBeTrue();

    Notification::assertNotSentTo($tenant, InvoiceReadingRequestNotification::class);
});

it('notifies tenants when generated invoices are ready for readings', function (): void {
    Carbon::setTestNow('2026-06-15 08:00:00');
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin, 'tenant' => $tenant] = automaticDraftInvoiceFixture();

    $result = app(GenerateDraftInvoicesForBillingPeriod::class)->handle($organization, automaticDraftPeriodData(), $admin);
    $invoice = $result['created']->first();

    expect($result['notified'])->toBe(1)
        ->and($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->approval_status)->toBe('waiting_for_readings');

    Notification::assertSentTo(
        $tenant,
        InvoiceReadingRequestNotification::class,
        fn (InvoiceReadingRequestNotification $notification): bool => $notification->toArray($tenant)['invoice_id'] === $invoice->id,
    );
});

it('notifies admins with the generation summary', function (): void {
    Carbon::setTestNow('2026-06-15 08:00:00');
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin] = automaticDraftInvoiceFixture();

    app(GenerateDraftInvoicesForBillingPeriod::class)->handle($organization, automaticDraftPeriodData(), $admin);

    Notification::assertSentTo(
        $admin,
        BillingGenerationSummaryNotification::class,
        function (BillingGenerationSummaryNotification $notification) use ($admin): bool {
            $payload = $notification->toArray($admin);

            return $payload['created_count'] === 1
                && $payload['error_count'] === 0
                && $payload['notified_tenants_count'] === 1;
        },
    );
});

it('sends reading reminders from organization billing settings', function (): void {
    Carbon::setTestNow('2026-06-15 08:00:00');
    Mail::fake();
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin, 'tenant' => $tenant] = automaticDraftInvoiceFixture();

    app(GenerateDraftInvoicesForBillingPeriod::class)->handle($organization, automaticDraftPeriodData(), $admin);

    $this->artisan('billing:send-reading-reminders', [
        '--organization' => [$organization->id],
        '--date' => '2026-06-17',
    ])->assertExitCode(0);

    expect($tenant->notifications()
        ->where('type', DomainNotificationCatalog::READING_REMINDER)
        ->count())->toBe(1);
});

it('enforces organization isolation during generation', function (): void {
    Carbon::setTestNow('2026-06-15 08:00:00');
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin] = automaticDraftInvoiceFixture();
    ['organization' => $otherOrganization] = automaticDraftInvoiceFixture();

    app(GenerateDraftInvoicesForBillingPeriod::class)->handle($organization, automaticDraftPeriodData(), $admin);

    expect(Invoice::query()->where('organization_id', $organization->id)->count())->toBe(1)
        ->and(Invoice::query()->where('organization_id', $otherOrganization->id)->count())->toBe(0)
        ->and(BillingGenerationLog::query()->where('organization_id', $organization->id)->count())->toBe(1)
        ->and(BillingGenerationLog::query()->where('organization_id', $otherOrganization->id)->count())->toBe(0);
});

it('schedules automatic draft invoice generation daily', function (): void {
    $scheduledEvent = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'billing:generate-draft-invoices'));

    expect($scheduledEvent)->not->toBeNull()
        ->and($scheduledEvent->getExpression())->toBe('15 8 * * *');
});

it('schedules reading reminders daily', function (): void {
    $scheduledEvent = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'billing:send-reading-reminders'));

    expect($scheduledEvent)->not->toBeNull()
        ->and($scheduledEvent->getExpression())->toBe('15 7 * * *');
});

function automaticDraftPeriodData(array $overrides = []): array
{
    return [
        'billing_period_start' => '2026-05-01',
        'billing_period_end' => '2026-05-31',
        'reading_submission_deadline' => '2026-06-20',
        'invoice_generation_date' => '2026-06-15',
        'payment_due_date' => '2026-06-30',
        'default_currency' => 'EUR',
        'send_created_notification' => true,
        ...$overrides,
    ];
}

function automaticDraftInvoiceFixture(array $options = []): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'email' => 'billing-admin-'.$organization->id.'@example.test',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'email' => 'billing-tenant-'.$organization->id.'@example.test',
        ...($options['tenant_attributes'] ?? []),
    ]);

    OrganizationSetting::factory()->for($organization)->create([
        'auto_generation_enabled' => true,
        'billing_frequency' => BillingFrequency::MONTHLY->value,
        'invoice_generation_day' => 15,
        'reading_deadline_day' => 20,
        'payment_due_days' => 10,
        'send_created_notification' => true,
        'send_reminders' => true,
        'reminder_days_before_deadline' => [3, 1],
        'timezone' => 'UTC',
        'default_currency' => 'EUR',
    ]);

    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Automatic Draft Apartment '.$organization->id,
    ]);
    $assignment = PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => '2026-01-01 00:00:00',
            'unassigned_at' => null,
            'status' => PropertyAssignmentStatus::ACTIVE,
            ...($options['assignment_attributes'] ?? []),
        ]);

    $serviceMode = $options['service_mode'] ?? 'metered';
    $meter = null;
    $provider = null;
    $tariff = null;
    $utilityService = null;
    $serviceConfiguration = null;

    if ($serviceMode !== 'none') {
        if ($serviceMode !== 'fixed') {
            $meter = Meter::factory()
                ->for($organization)
                ->for($property)
                ->create([
                    'name' => 'Main Electricity',
                    'identifier' => 'AUTO-EL-'.$organization->id,
                    'type' => MeterType::ELECTRICITY,
                    'unit' => MeterType::ELECTRICITY->defaultUnit()->value,
                ]);
        }

        $provider = Provider::factory()->forOrganization($organization)->create([
            'name' => 'Automatic Draft Provider '.$organization->id,
            'service_type' => ServiceType::ELECTRICITY,
        ]);
        $tariff = $serviceMode === 'missing_tariff'
            ? null
            : Tariff::factory()->for($provider)->flat()->create([
                'name' => 'Automatic Draft Tariff '.$organization->id,
                'active_from' => '2026-01-01 00:00:00',
                'active_until' => null,
            ]);
        $utilityService = UtilityService::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Electricity',
            'service_type_bridge' => ServiceType::ELECTRICITY,
            'default_pricing_model' => $serviceMode === 'fixed'
                ? PricingModel::FIXED_MONTHLY
                : PricingModel::CONSUMPTION_BASED,
        ]);
        $serviceConfigurationFactory = ServiceConfiguration::factory();

        if ($serviceMode === 'fixed') {
            $serviceConfigurationFactory = $serviceConfigurationFactory->fixedMonthly('25.00');
        }

        $serviceConfiguration = $serviceConfigurationFactory->create([
            'organization_id' => $organization->id,
            'property_id' => $property->id,
            'utility_service_id' => $utilityService->id,
            'service_name' => 'Electricity',
            'service_type' => ServiceType::ELECTRICITY,
            'billing_method' => $serviceMode === 'fixed' ? BillingMethod::FIXED_MONTHLY : BillingMethod::METER_BASED,
            'distribution_method' => $serviceMode === 'fixed' ? DistributionMethod::EQUAL : DistributionMethod::BY_CONSUMPTION,
            'unit' => MeterType::ELECTRICITY->defaultUnit()->value,
            'tenant_visible_name' => 'Electricity',
            'tenant_visible_description' => 'Electricity service.',
            'pricing_model' => $serviceMode === 'fixed' ? PricingModel::FIXED_MONTHLY : PricingModel::CONSUMPTION_BASED,
            'provider_id' => $provider->id,
            'tariff_id' => $tariff?->id,
            'fixed_amount' => $serviceMode === 'fixed' ? '25.00' : null,
            'effective_from' => '2026-01-01 00:00:00',
            'effective_until' => null,
            'starts_at' => '2026-01-01 00:00:00',
            'ends_at' => null,
            'is_active' => true,
        ]);
    }

    return [
        'organization' => $organization,
        'admin' => $admin,
        'tenant' => $tenant,
        'building' => $building,
        'property' => $property,
        'assignment' => $assignment,
        'meter' => $meter,
        'provider' => $provider,
        'tariff' => $tariff,
        'utilityService' => $utilityService,
        'serviceConfiguration' => $serviceConfiguration,
    ];
}
