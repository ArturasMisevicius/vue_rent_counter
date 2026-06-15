<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterType;
use App\Filament\Actions\Tenant\Readings\SubmitTenantReadingAction;
use App\Livewire\Tenant\SubmitReadingPage;
use App\Models\Invoice;
use App\Models\ManagerPermission;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use App\Notifications\Billing\TenantReadingsSubmittedForInvoiceNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;
use Tests\Support\TenantPortalFixture;

uses(RefreshDatabase::class);

function createTenantReadingRequestInvoice(TenantPortalFixture $tenant, array $attributes = []): Invoice
{
    return Invoice::factory()
        ->for($tenant->organization)
        ->for($tenant->property)
        ->for($tenant->user, 'tenant')
        ->create([
            'status' => InvoiceStatus::DRAFT,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'finalized_at' => null,
            'total_amount' => 0,
            'automation_level' => 'reading_request',
            'approval_status' => 'pending',
            'approval_metadata' => ['workflow' => 'meter_reading_request'],
            ...$attributes,
        ]);
}

it('requires an open reading request invoice before tenant readings can be submitted', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    Livewire::actingAs($tenant->user)
        ->test(SubmitReadingPage::class)
        ->set("readings.{$meter->id}.value", '245.125')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(['readings'])
        ->assertSeeText('No open reading request');

    expect(MeterReading::query()
        ->where('meter_id', $meter->id)
        ->where('submitted_by_user_id', $tenant->user->id)
        ->exists())->toBeFalse();
});

it('rejects direct tenant reading action calls without an open reading request invoice', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    expect(fn () => app(SubmitTenantReadingAction::class)->handle(
        tenant: $tenant->user,
        meterId: $meter->id,
        readingValue: '245.125',
        readingDate: now()->toDateString(),
    ))->toThrow(ValidationException::class, 'Readings can be submitted after your property manager opens a draft invoice request');

    expect(MeterReading::query()
        ->where('meter_id', $meter->id)
        ->where('submitted_by_user_id', $tenant->user->id)
        ->exists())->toBeFalse();
});

it('shows reading request invoice context from a tenant deep link', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $invoice = Invoice::factory()
        ->for($tenant->organization)
        ->for($tenant->property)
        ->for($tenant->user, 'tenant')
        ->create([
            'invoice_number' => 'REQ-2026-05',
            'status' => InvoiceStatus::DRAFT,
            'billing_period_start' => '2026-05-01',
            'billing_period_end' => '2026-05-31',
            'due_date' => '2026-06-14',
            'automation_level' => 'reading_request',
        ]);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->assertSet('invoiceId', (string) $invoice->id)
        ->assertSeeText('Reading request for invoice REQ-2026-05')
        ->assertSeeText('Billing period:')
        ->assertSeeText('Submit readings by')
        ->assertSeeText('Meter')
        ->assertSeeText('Previous reading')
        ->assertSeeText('Current reading')
        ->assertSeeText('Consumption');
});

it('does not allow tenant readings through another tenant invoice link', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();
    $otherTenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->create();

    $outsideInvoice = Invoice::factory()
        ->for($otherTenant->organization)
        ->for($otherTenant->property)
        ->for($otherTenant->user, 'tenant')
        ->create([
            'invoice_number' => 'REQ-OUTSIDE',
            'status' => InvoiceStatus::DRAFT,
            'automation_level' => 'reading_request',
        ]);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $outsideInvoice->id])
        ->test(SubmitReadingPage::class)
        ->assertDontSeeText('REQ-OUTSIDE')
        ->assertDontSee('data-tenant-reading-request-invoice', false)
        ->set("readings.{$tenant->meters->firstOrFail()->id}.value", '245.125')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(['readings'])
        ->assertSeeText('Reading request already handled');

    expect($outsideInvoice->fresh()->approval_status)->not->toBe('readings_submitted')
        ->and(MeterReading::query()
            ->where('submitted_by_user_id', $tenant->user->id)
            ->exists())->toBeFalse();
});

it('marks the linked reading request invoice and notifies billing reviewers after tenant submission', function () {
    Notification::fake();

    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $tenant->organization->id,
    ]);
    $tenant->organization->forceFill([
        'owner_user_id' => $admin->id,
    ])->save();
    $manager = User::factory()->manager()->create([
        'organization_id' => $tenant->organization->id,
    ]);
    $restrictedManager = User::factory()->manager()->create([
        'organization_id' => $tenant->organization->id,
    ]);

    ManagerPermission::syncForManager($manager, $tenant->organization, [
        'invoices' => ['can_edit' => true],
    ]);

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $invoice = Invoice::factory()
        ->for($tenant->organization)
        ->for($tenant->property)
        ->for($tenant->user, 'tenant')
        ->create([
            'invoice_number' => 'REQ-READY-001',
            'status' => InvoiceStatus::DRAFT,
            'automation_level' => 'reading_request',
            'approval_status' => 'pending',
            'approval_metadata' => [
                'workflow' => 'meter_reading_request',
                'request_status' => 'waiting_for_readings',
                'linked_meters' => [[
                    'id' => $meter->id,
                    'identifier' => $meter->identifier,
                    'status' => 'pending',
                ]],
                'required_inputs' => [[
                    'type' => 'meter_reading',
                    'meter_id' => $meter->id,
                    'meter_name' => $meter->name,
                    'meter_identifier' => $meter->identifier,
                    'unit' => $meter->unit,
                    'status' => 'pending',
                ]],
            ],
        ]);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set("readings.{$meter->id}.value", '245.125')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasNoErrors();

    $invoice->refresh();
    $submittedReadingId = MeterReading::query()
        ->where('meter_id', $meter->id)
        ->value('id');

    expect($invoice->approval_status)->toBe('readings_submitted')
        ->and($invoice->approval_metadata['submitted_by_tenant_user_id'] ?? null)->toBe($tenant->user->id)
        ->and($invoice->approval_metadata['submitted_meter_reading_ids'] ?? [])->toBe([(int) $submittedReadingId])
        ->and($invoice->approval_metadata['submitted_reading_count'] ?? null)->toBe(1)
        ->and($invoice->approval_metadata['request_status'] ?? null)->toBe('readings_submitted')
        ->and($invoice->approval_metadata['linked_meters'][0]['status'] ?? null)->toBe('submitted')
        ->and($invoice->approval_metadata['required_inputs'][0]['status'] ?? null)->toBe('submitted')
        ->and($invoice->approval_metadata['required_inputs'][0]['submitted_meter_reading_id'] ?? null)->toBe((int) $submittedReadingId);

    Notification::assertSentTo(
        $admin,
        TenantReadingsSubmittedForInvoiceNotification::class,
        function (TenantReadingsSubmittedForInvoiceNotification $notification, array $channels) use ($admin, $invoice): bool {
            $payload = $notification->toArray($admin);

            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && $payload['invoice_id'] === $invoice->id
                && $payload['url'] === route('filament.admin.resources.invoices.edit', ['record' => $invoice], false);
        },
    );
    Notification::assertSentTo($manager, TenantReadingsSubmittedForInvoiceNotification::class);
    Notification::assertNotSentTo($restrictedManager, TenantReadingsSubmittedForInvoiceNotification::class);
});

it('prevents tenant resubmission after a reading request invoice has been submitted', function () {
    Notification::fake();

    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set("readings.{$meter->id}.value", '245.125')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasNoErrors();

    expect($invoice->fresh()->approval_status)->toBe('readings_submitted');

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set("readings.{$meter->id}.value", '246.000')
        ->set('readingDate', now()->addDay()->toDateString())
        ->call('submit')
        ->assertHasErrors(['readings'])
        ->assertSeeText('Reading request already handled');

    expect(MeterReading::query()
        ->where('meter_id', $meter->id)
        ->where('submitted_by_user_id', $tenant->user->id)
        ->count())->toBe(1);
});

it('updates linked reading request invoice metadata without notifying when reviewer notifications are disabled', function () {
    Notification::fake();

    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();
    $tenant->settings->forceFill([
        'notification_preferences' => [
            'new_invoice_generated' => false,
            'invoice_overdue' => false,
            'tenant_submits_reading' => false,
            'subscription_expiring' => false,
        ],
    ])->save();
    $admin = User::factory()->admin()->create([
        'organization_id' => $tenant->organization->id,
    ]);
    $tenant->organization->forceFill([
        'owner_user_id' => $admin->id,
    ])->save();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $invoice = Invoice::factory()
        ->for($tenant->organization)
        ->for($tenant->property)
        ->for($tenant->user, 'tenant')
        ->create([
            'status' => InvoiceStatus::DRAFT,
            'automation_level' => 'reading_request',
            'approval_status' => 'pending',
        ]);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set("readings.{$meter->id}.value", '245.125')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasNoErrors();

    expect($invoice->fresh()->approval_status)->toBe('readings_submitted');

    Notification::assertNotSentTo($admin, TenantReadingsSubmittedForInvoiceNotification::class);
});

it('prevents duplicate tenant readings for the same meter and date', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $readingDate = now()->toDateString();

    $invoice = createTenantReadingRequestInvoice($tenant);
    MeterReading::factory()
        ->for($tenant->organization)
        ->for($tenant->property)
        ->for($meter)
        ->create([
            'submitted_by_user_id' => $tenant->user->id,
            'reading_value' => 245.125,
            'reading_date' => $readingDate,
            'submission_method' => MeterReadingSubmissionMethod::TENANT_PORTAL,
        ]);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set("readings.{$meter->id}.value", '246.000')
        ->set('readingDate', $readingDate)
        ->call('submit')
        ->assertHasErrors(["readings.{$meter->id}.value"]);

    expect(MeterReading::query()
        ->where('meter_id', $meter->id)
        ->whereDate('reading_date', $readingDate)
        ->count())->toBe(1);
});

it('prevents duplicate tenant readings for the same meter and invoice period', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $invoice = createTenantReadingRequestInvoice($tenant, [
        'billing_period_start' => '2026-05-01',
        'billing_period_end' => '2026-05-31',
        'due_date' => '2026-06-14',
        'approval_metadata' => [
            'workflow' => 'meter_reading_request',
            'reading_submission_deadline' => '2026-06-14',
            'required_inputs' => [[
                'type' => 'meter_reading',
                'meter_id' => $meter->id,
                'meter_name' => $meter->name,
                'meter_identifier' => $meter->identifier,
                'unit' => $meter->unit,
                'status' => 'pending',
            ]],
        ],
    ]);

    MeterReading::factory()
        ->for($tenant->organization)
        ->for($tenant->property)
        ->for($meter)
        ->create([
            'submitted_by_user_id' => $tenant->user->id,
            'reading_value' => 245.125,
            'reading_date' => '2026-05-10',
            'submission_method' => MeterReadingSubmissionMethod::TENANT_PORTAL,
        ]);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set("readings.{$meter->id}.value", '246.000')
        ->set('readingDate', '2026-05-20')
        ->call('submit')
        ->assertHasErrors(["readings.{$meter->id}.value"])
        ->assertSeeText('already been submitted for this invoice period');

    expect(MeterReading::query()
        ->where('meter_id', $meter->id)
        ->where('submitted_by_user_id', $tenant->user->id)
        ->whereBetween('reading_date', ['2026-05-01', '2026-06-14'])
        ->count())->toBe(1);
});

it('allows a tenant to submit readings for multiple meters from one form', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(2)
        ->create();

    /** @var Meter $firstMeter */
    $firstMeter = $tenant->meters->values()->get(0);
    /** @var Meter $secondMeter */
    $secondMeter = $tenant->meters->values()->get(1);
    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set("readings.{$firstMeter->id}.value", '245.125')
        ->set("readings.{$firstMeter->id}.notes", 'Kitchen meter.')
        ->set("readings.{$secondMeter->id}.value", '310.500')
        ->set("readings.{$secondMeter->id}.notes", 'Bathroom meter.')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSeeText('2 Readings Submitted!')
        ->assertSeeText($firstMeter->identifier)
        ->assertSeeText($secondMeter->identifier)
        ->assertSeeText('245.125')
        ->assertSeeText('310.500');

    $readings = MeterReading::query()
        ->whereIn('meter_id', [$firstMeter->id, $secondMeter->id])
        ->where('submitted_by_user_id', $tenant->user->id)
        ->get();

    expect($readings)->toHaveCount(2);

    $readings->each(function (MeterReading $reading): void {
        expect($reading->submission_method)->toBe(MeterReadingSubmissionMethod::TENANT_PORTAL);
    });
});

it('shows a live consumption preview for the selected meter', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withReadings()
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '150.750')
        ->assertSeeText('Consumption Preview')
        ->assertSeeText('5.250');
});

it('shows full lithuanian month names in previous reading dates', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();

    MeterReading::factory()
        ->for($tenant->organization)
        ->for($tenant->property)
        ->for($meter)
        ->create([
            'submitted_by_user_id' => $tenant->user->id,
            'reading_value' => 145.500,
            'reading_date' => CarbonImmutable::parse('2026-03-02')->toDateString(),
        ]);

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    app()->setLocale('lt');

    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user->fresh())
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->assertSeeText('2026 m. kovo 2 d.')
        ->assertDontSeeText('kov 2, 2026');
});

it('renders a localized modal calendar selector for the tenant reading date', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    app()->setLocale('lt');

    $invoice = createTenantReadingRequestInvoice($tenant);

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.tenant-submit-meter-reading', ['invoice' => $invoice->id]))
        ->assertSuccessful()
        ->assertSee('data-calendar-picker', false)
        ->assertSee('data-calendar-dialog', false)
        ->assertSeeText('Pasirinkite datą')
        ->assertSeeText('Atidaryti kalendorių')
        ->assertSeeText('Šiandien')
        ->assertDontSee('type="date"', false);
});

it('localizes seeded operations demo meter names on the tenant reading page', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $meter->forceFill([
        'name' => 'Operations Demo Meter',
        'type' => MeterType::ELECTRICITY,
    ])->save();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    app()->setLocale('lt');

    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user->fresh())
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->assertSeeText('Operacijų demonstracinis skaitiklis: Elektra')
        ->assertDontSeeText('Operations Demo Meter')
        ->set("readings.{$meter->id}.value", '245.125')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSeeText('Operacijų demonstracinis skaitiklis: Elektra')
        ->assertDontSeeText('Operations Demo Meter');
});

it('shows a live warning when the entered reading is lower than the previous value', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withReadings()
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '120.000')
        ->assertSeeText('will be rejected on submission');
});

it('shows a validation error when the submitted reading decreases', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withReadings()
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $meter->id)
        ->set('readingValue', '120.000')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(['readingValue']);
});

it('shows localized tenant validation reasons for invalid reading values', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    app()->setLocale('lt');

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user->fresh())
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set("readings.{$meter->id}.value", '0')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(["readings.{$meter->id}.value"])
        ->assertSeeText('Rodmuo turi būti didesnis už 0');
});

it('explains why a tenant reading below the previous value is rejected', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withReadings()
        ->create();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    app()->setLocale('lt');

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user->fresh())
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set("readings.{$meter->id}.value", '120.000')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(["readings.{$meter->id}.value"])
        ->assertSeeText('neigiamą sunaudojimą');
});

it('rejects future-dated tenant readings with a plain language reason', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set("readings.{$meter->id}.value", '245.125')
        ->set('readingDate', now()->addDay()->toDateString())
        ->call('submit')
        ->assertHasErrors(['readingDate'])
        ->assertSeeText('The reading date cannot be in the future');
});

it('provides tenant reading validation messages for every supported locale', function () {
    $keys = [
        'tenant.pages.readings.no_open_request_title',
        'tenant.pages.readings.no_open_request',
        'tenant.pages.readings.invoice_request_unavailable_title',
        'tenant.pages.readings.invoice_request_unavailable',
        'tenant.pages.readings.validation.meter_required',
        'tenant.pages.readings.validation.meter_invalid',
        'tenant.pages.readings.validation.reading_value_required',
        'tenant.pages.readings.validation.reading_value_numeric',
        'tenant.pages.readings.validation.reading_value_positive',
        'tenant.pages.readings.validation.reading_date_required',
        'tenant.pages.readings.validation.reading_date_invalid',
        'tenant.pages.readings.validation.reading_date_not_future',
        'tenant.pages.readings.validation.notes_too_long',
        'tenant.pages.readings.validation.duplicate_reading_for_date',
        'tenant.pages.readings.validation.duplicate_reading_for_invoice_period',
        'tenant.pages.readings.submitted_review_status',
        'tenant.pages.readings.previous_reading_column',
        'tenant.pages.readings.current_reading_column',
        'tenant.pages.readings.consumption_column',
        'tenant.pages.readings.preview_empty_short',
        'tenant.actions.submit_readings',
        'tenant.pages.home.current_invoice',
    ];

    foreach (['en', 'lt', 'es', 'ru'] as $locale) {
        foreach ($keys as $key) {
            $message = __($key, [], $locale);

            expect($message)
                ->not->toBe($key)
                ->not->toBe('');
        }
    }
});

it('preselects and locks the meter picker for single-meter tenant accounts', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    /** @var Meter $meter */
    $meter = $tenant->meters->firstOrFail();
    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->assertSet('meterId', (string) $meter->id)
        ->assertSeeText($meter->name)
        ->assertSee('data-tenant-reading-batch-form', false)
        ->assertSee('data-tenant-reading-row', false)
        ->assertDontSee('id="meterId"', false);
});

it('shows the tenant phone on the submit reading page', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $tenant->user->forceFill([
        'phone' => '+37066667777',
    ])->save();

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.tenant-submit-meter-reading'))
        ->assertSuccessful()
        ->assertSeeText('+37066667777');
});

it('does not expose outside meters and rejects submissions for them', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $outsideMeter = Meter::factory()->create([
        'name' => 'Outside Meter',
    ]);
    $invoice = createTenantReadingRequestInvoice($tenant);

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->assertDontSeeText('Outside Meter')
        ->set('meterId', (string) $outsideMeter->id)
        ->set('readingValue', '10.000')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(['meterId']);

    expect(
        MeterReading::query()
            ->where('meter_id', $outsideMeter->id)
            ->where('submitted_by_user_id', $tenant->user->id)
            ->exists()
    )->toBeFalse();
});

it('refreshes translated submit reading copy when the shell locale changes', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();
    $invoice = createTenantReadingRequestInvoice($tenant);

    $component = Livewire::actingAs($tenant->user)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->assertSeeText(__('tenant.pages.readings.title', [], 'en'));

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($tenant->user->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('tenant.pages.readings.title', [], 'lt'))
        ->assertSeeText(__('tenant.pages.readings.preview_heading', [], 'lt'));
});
