<?php

use App\Enums\InvoiceStatus;
use App\Filament\Actions\Admin\Invoices\OpenReadingInvoiceCycleAction;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use App\Notifications\Billing\InvoiceReadingRequestNotification;
use Carbon\Carbon;
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

    expect($result['created'])->toHaveCount(1)
        ->and($result['skipped'])->toBe([])
        ->and($result['notified'])->toBe(1)
        ->and($invoice->status)->toBe(InvoiceStatus::DRAFT)
        ->and((float) $invoice->total_amount)->toBe(0.0)
        ->and($invoice->items)->toBe([])
        ->and($invoice->invoiceItems)->toHaveCount(0)
        ->and($invoice->automation_level)->toBe('reading_request')
        ->and($invoice->generated_by)->toBe("user:{$admin->id}")
        ->and($invoice->approval_metadata['workflow'] ?? null)->toBe('meter_reading_request');

    Notification::assertSentTo(
        $tenant,
        InvoiceReadingRequestNotification::class,
        function (InvoiceReadingRequestNotification $notification, array $channels) use ($invoice, $tenant): bool {
            $payload = $notification->toArray($tenant);

            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && $payload['invoice_id'] === $invoice->id
                && $payload['url'] === route('tenant.readings.create', ['invoice' => $invoice->id], false);
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
        ->and($invoice->status)->toBe(InvoiceStatus::DRAFT);

    Notification::assertSentTo($tenant, InvoiceReadingRequestNotification::class);
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
        ->create();

    return [
        'organization' => $organization,
        'admin' => $admin,
        'tenant' => $tenant,
        'property' => $property,
    ];
}
