<?php

use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\BillingReview\ApproveInvoice;
use App\Filament\Actions\Admin\BillingReview\ApproveReading;
use App\Filament\Actions\Admin\BillingReview\CorrectReading;
use App\Filament\Actions\Admin\BillingReview\RecalculateInvoice;
use App\Filament\Actions\Admin\BillingReview\RejectReading;
use App\Filament\Actions\Admin\BillingReview\SendInvoiceToTenant;
use App\Filament\Actions\Admin\BillingReview\SendReadingReminder;
use App\Filament\Actions\Admin\BillingReview\VoidReading;
use App\Filament\Support\Admin\BillingReview\BuildBillingReviewForPeriod;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\ManagerPermission;
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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-05-31 12:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('allows admins to access the billing review center', function (): void {
    $workspace = billingReviewWorkspace();

    $this->actingAs($workspace['admin'])
        ->get(route('filament.admin.pages.billing-review-center'))
        ->assertSuccessful()
        ->assertSee('Billing Review Center');
});

it('allows managers to access the billing review center when allowed', function (): void {
    $workspace = billingReviewWorkspace();
    $manager = User::factory()->manager()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    ManagerPermission::syncForManager($manager, $workspace['organization'], [
        'billing' => ['can_create' => false, 'can_edit' => true, 'can_delete' => false],
    ]);

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.billing-review-center'))
        ->assertSuccessful();
});

it('blocks tenants from the billing review center', function (): void {
    $workspace = billingReviewWorkspace();

    $this->actingAs($workspace['tenant'])
        ->get(route('filament.admin.pages.billing-review-center'))
        ->assertForbidden();
});

it('isolates billing review invoices by organization', function (): void {
    $workspace = billingReviewWorkspace([
        'invoice_number' => 'INV-OWNED',
        'tenant_name' => 'Owned Tenant',
    ]);
    billingReviewWorkspace([
        'invoice_number' => 'INV-OTHER',
        'tenant_name' => 'Other Tenant',
    ]);

    $this->actingAs($workspace['admin'])
        ->get(route('filament.admin.pages.billing-review-center'))
        ->assertSuccessful()
        ->assertSee('Owned Tenant')
        ->assertDontSee('Other Tenant')
        ->assertDontSee('INV-OTHER');
});

it('counts the billing review summary correctly', function (): void {
    $workspace = billingReviewWorkspace(['invoice_number' => 'INV-WAITING', 'current_status' => null]);
    billingReviewWorkspace(['organization' => $workspace['organization'], 'admin' => $workspace['admin'], 'invoice_number' => 'INV-SUBMITTED', 'current_status' => MeterReadingValidationStatus::PENDING]);
    billingReviewWorkspace(['organization' => $workspace['organization'], 'admin' => $workspace['admin'], 'invoice_number' => 'INV-READY']);
    billingReviewWorkspace(['organization' => $workspace['organization'], 'admin' => $workspace['admin'], 'invoice_number' => 'INV-CONFIG', 'with_tariff' => false, 'with_rate_schedule' => false]);
    $sent = billingReviewWorkspace([
        'organization' => $workspace['organization'],
        'admin' => $workspace['admin'],
        'invoice_number' => 'INV-SENT',
        'invoice_status' => InvoiceStatus::FINALIZED,
        'approval_status' => 'approved',
        'due_date' => '2026-05-01',
        'total_amount' => '50.00',
    ]);

    InvoiceEmailLog::query()->create([
        'invoice_id' => $sent['invoice']->id,
        'organization_id' => $workspace['organization']->id,
        'sent_by_user_id' => $workspace['admin']->id,
        'recipient_email' => $sent['tenant']->email,
        'subject' => 'Invoice',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $review = app(BuildBillingReviewForPeriod::class)->handle($workspace['organization']->id, '2026-05-01', '2026-05-31');
    $summary = $review['summary']->toArray();

    expect($summary)->toMatchArray([
        'total_invoices' => 5,
        'waiting_for_readings' => 1,
        'submitted_readings' => 4,
        'ready_for_review' => 1,
        'configuration_errors' => 1,
        'approved' => 1,
        'sent' => 1,
        'overdue' => 1,
    ]);
});

it('does not approve an invoice with missing readings', function (): void {
    $workspace = billingReviewWorkspace(['current_status' => null]);
    $this->actingAs($workspace['admin']);

    expect(fn () => app(ApproveInvoice::class)->handle($workspace['invoice'], $workspace['admin']))
        ->toThrow(ValidationException::class);
});

it('does not approve an invoice with rejected readings', function (): void {
    $workspace = billingReviewWorkspace(['current_status' => MeterReadingValidationStatus::REJECTED]);
    $this->actingAs($workspace['admin']);

    expect(fn () => app(ApproveInvoice::class)->handle($workspace['invoice'], $workspace['admin']))
        ->toThrow(ValidationException::class);
});

it('does not approve an invoice with a missing tariff', function (): void {
    $workspace = billingReviewWorkspace(['with_tariff' => false, 'with_rate_schedule' => false]);
    $this->actingAs($workspace['admin']);

    expect(fn () => app(ApproveInvoice::class)->handle($workspace['invoice'], $workspace['admin']))
        ->toThrow(ValidationException::class);
});

it('approves an invoice when all required readings are approved', function (): void {
    Notification::fake();

    $workspace = billingReviewWorkspace();
    $this->actingAs($workspace['admin']);

    $invoice = app(ApproveInvoice::class)->handle($workspace['invoice'], $workspace['admin']);

    expect($invoice->status)->toBe(InvoiceStatus::FINALIZED)
        ->and($invoice->approval_status)->toBe('approved')
        ->and($invoice->approved_by)->toBe($workspace['admin']->id);
});

it('requires a correction reason', function (): void {
    $workspace = billingReviewWorkspace();
    $this->actingAs($workspace['admin']);

    expect(fn () => app(CorrectReading::class)->handle($workspace['current_reading'], [
        'reading_value' => '132',
    ], $workspace['admin']))->toThrow(ValidationException::class);
});

it('requires a tenant-visible rejection comment', function (): void {
    $workspace = billingReviewWorkspace();
    $this->actingAs($workspace['admin']);

    expect(fn () => app(RejectReading::class)->handle($workspace['current_reading'], '', $workspace['admin']))
        ->toThrow(ValidationException::class);
});

it('recalculates invoices using only approved readings', function (): void {
    Notification::fake();

    $workspace = billingReviewWorkspace(['current_value' => '130']);
    MeterReading::factory()
        ->for($workspace['organization'])
        ->for($workspace['property'])
        ->for($workspace['meter'])
        ->for($workspace['tenant'], 'submittedBy')
        ->create([
            'reading_value' => '999',
            'reading_date' => '2026-05-31',
            'validation_status' => MeterReadingValidationStatus::PENDING,
        ]);
    $this->actingAs($workspace['admin']);

    $invoice = app(RecalculateInvoice::class)->handle($workspace['invoice'], $workspace['admin']);

    expect((string) $invoice->total_amount)->toBe('60.00')
        ->and($invoice->approval_status)->toBe('needs_attention');
});

it('creates audit and history records for billing review actions', function (): void {
    Notification::fake();

    $workspace = billingReviewWorkspace();
    $this->actingAs($workspace['admin']);

    app(ApproveReading::class)->handle($workspace['current_reading'], $workspace['admin']);
    app(CorrectReading::class)->handle($workspace['current_reading']->fresh(), [
        'reading_value' => '126',
        'reason' => 'Photo correction',
    ], $workspace['admin']);
    app(RecalculateInvoice::class)->handle($workspace['invoice'], $workspace['admin']);
    $approvedInvoice = app(ApproveInvoice::class)->handle($workspace['invoice']->fresh(), $workspace['admin']);
    app(SendInvoiceToTenant::class)->handle($approvedInvoice, $workspace['admin']);

    $rejected = billingReviewWorkspace([
        'organization' => $workspace['organization'],
        'admin' => $workspace['admin'],
        'invoice_number' => 'INV-REJECT-AUDIT',
    ]);
    app(RejectReading::class)->handle($rejected['current_reading'], 'Please resubmit a clear photo.', $workspace['admin']);

    $voided = billingReviewWorkspace([
        'organization' => $workspace['organization'],
        'admin' => $workspace['admin'],
        'invoice_number' => 'INV-VOID-AUDIT',
    ]);
    app(VoidReading::class)->handle($voided['current_reading'], 'Duplicate submission.', $workspace['admin']);

    $missing = billingReviewWorkspace([
        'organization' => $workspace['organization'],
        'admin' => $workspace['admin'],
        'invoice_number' => 'INV-REMINDER-AUDIT',
        'current_status' => null,
    ]);
    app(SendReadingReminder::class)->handle($missing['invoice'], $workspace['admin']);

    expect(AuditLog::query()->forOrganization($workspace['organization']->id)->count())->toBeGreaterThanOrEqual(7)
        ->and(OrganizationActivityLog::query()->forOrganization($workspace['organization']->id)->count())->toBeGreaterThanOrEqual(7);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array{
 *     organization: Organization,
 *     admin: User,
 *     tenant: User,
 *     property: Property,
 *     meter: Meter,
 *     invoice: Invoice,
 *     previous_reading: MeterReading,
 *     current_reading: MeterReading|null
 * }
 */
function billingReviewWorkspace(array $overrides = []): array
{
    $organization = $overrides['organization'] ?? createOrgWithAdmin()['organization'];
    $admin = $overrides['admin'] ?? User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => $overrides['tenant_name'] ?? 'Billing Tenant',
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => $overrides['property_name'] ?? 'Review Unit',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => '2026-01-01 00:00:00',
            'unassigned_at' => null,
        ]);

    $provider = Provider::factory()->for($organization)->create([
        'service_type' => ServiceType::WATER,
    ]);
    $tariff = ($overrides['with_tariff'] ?? true)
        ? Tariff::factory()->for($provider)->create([
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => '2.00',
            ],
        ])
        : null;
    $utilityService = UtilityService::factory()->for($organization)->create([
        'name' => 'Water',
        'unit_of_measurement' => 'm3',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'service_type_bridge' => ServiceType::WATER,
    ]);

    $serviceConfiguration = ServiceConfiguration::factory()
        ->for($organization)
        ->for($property)
        ->for($utilityService)
        ->for($provider)
        ->create([
            'tariff_id' => $tariff?->id,
            'pricing_model' => PricingModel::CONSUMPTION_BASED,
            'distribution_method' => DistributionMethod::BY_CONSUMPTION,
            'rate_schedule' => ($overrides['with_rate_schedule'] ?? true) ? ['unit_rate' => '2.00'] : [],
            'is_shared_service' => false,
            'effective_from' => '2026-01-01',
            'effective_until' => null,
        ]);

    if ($tariff instanceof Tariff) {
        $serviceConfiguration->tariff()->associate($tariff);
        $serviceConfiguration->save();
    }

    $meter = Meter::factory()
        ->for($organization)
        ->for($property)
        ->create([
            'type' => MeterType::WATER,
            'unit' => 'm3',
        ]);

    $previousReading = MeterReading::factory()
        ->for($organization)
        ->for($property)
        ->for($meter)
        ->for($admin, 'submittedBy')
        ->create([
            'reading_value' => $overrides['previous_value'] ?? '100',
            'reading_date' => '2026-04-30',
            'validation_status' => MeterReadingValidationStatus::VALID,
        ]);

    $currentReading = null;
    $currentStatus = array_key_exists('current_status', $overrides)
        ? $overrides['current_status']
        : MeterReadingValidationStatus::VALID;

    if ($currentStatus instanceof MeterReadingValidationStatus) {
        $currentReading = MeterReading::factory()
            ->for($organization)
            ->for($property)
            ->for($meter)
            ->for($tenant, 'submittedBy')
            ->create([
                'reading_value' => $overrides['current_value'] ?? '125',
                'reading_date' => '2026-05-31',
                'validation_status' => $currentStatus,
            ]);
    }

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => $overrides['invoice_number'] ?? 'INV-REVIEW-'.str()->ulid(),
            'billing_period_start' => '2026-05-01',
            'billing_period_end' => '2026-05-31',
            'status' => $overrides['invoice_status'] ?? InvoiceStatus::DRAFT,
            'total_amount' => $overrides['total_amount'] ?? '0.00',
            'amount_paid' => '0.00',
            'paid_amount' => '0.00',
            'due_date' => $overrides['due_date'] ?? '2026-06-14',
            'finalized_at' => ($overrides['invoice_status'] ?? InvoiceStatus::DRAFT) === InvoiceStatus::DRAFT ? null : now(),
            'items' => [],
            'snapshot_data' => [],
            'snapshot_created_at' => now(),
            'approval_status' => $overrides['approval_status'] ?? 'readings_submitted',
            'automation_level' => 'reading_request',
            'approval_metadata' => [],
        ]);

    return [
        'organization' => $organization->fresh(),
        'admin' => $admin->fresh(),
        'tenant' => $tenant->fresh(),
        'property' => $property->fresh(),
        'meter' => $meter->fresh(),
        'invoice' => $invoice->fresh(),
        'previous_reading' => $previousReading->fresh(),
        'current_reading' => $currentReading?->fresh(),
    ];
}
