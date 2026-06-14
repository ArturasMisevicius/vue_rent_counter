<?php

use App\Enums\BillingMethod;
use App\Enums\InvoiceItemSourceType;
use App\Enums\InvoiceStatus;
use App\Enums\KycVerificationStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\RentalContractStatus;
use App\Enums\ServiceConfigurationStatus;
use App\Enums\UserStatus;
use App\Filament\Support\Admin\Dashboard\BuildAdminAttentionDashboard;
use App\Models\Attachment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\InvoiceItem;
use App\Models\ManagerPermission;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\RentalContract;
use App\Models\ServiceConfiguration;
use App\Models\User;
use App\Models\UserKycProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-03-15 09:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('lets an admin access the organization attention dashboard', function (): void {
    $workspace = attentionDashboardWorkspace();
    attentionInvoice($workspace, [
        'invoice_number' => 'INV-ATTENTION-001',
        'automation_level' => 'reading_request',
        'approval_status' => 'pending',
    ]);

    $this->actingAs($workspace['admin'])
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Admin Attention Dashboard')
        ->assertSeeText('Needs action today')
        ->assertSeeText('Waiting for readings')
        ->assertSeeText('Billing progress for March 2026')
        ->assertSee('wire:poll.visible.30s="refreshDashboardOnInterval"', false);
});

it('does not include another organization in dashboard counts', function (): void {
    $workspace = attentionDashboardWorkspace();
    $other = attentionDashboardWorkspace();

    attentionInvoice($workspace, [
        'automation_level' => 'reading_request',
        'approval_status' => 'pending',
    ]);
    attentionInvoice($other, [
        'automation_level' => 'reading_request',
        'approval_status' => 'pending',
    ]);

    $dashboard = app(BuildAdminAttentionDashboard::class)
        ->handle($workspace['organization']->id, $workspace['admin']->id)
        ->toArray();

    expect($dashboard['counts']['invoices_waiting_for_readings'])->toBe(1);
});

it('shows manager widgets only for permitted resources', function (): void {
    $workspace = attentionDashboardWorkspace();
    $manager = User::factory()->manager()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    ManagerPermission::syncForManager($manager, $workspace['organization'], [
        'billing' => ['can_create' => false, 'can_edit' => true, 'can_delete' => false],
    ]);

    $dashboard = app(BuildAdminAttentionDashboard::class)
        ->handle($workspace['organization']->id, $manager->id)
        ->toArray();

    expect($dashboard['visible_widgets']['billing'])->toBeTrue()
        ->and($dashboard['visible_widgets']['tenant_onboarding'])->toBeFalse()
        ->and($dashboard['visible_widgets']['contracts'])->toBeFalse()
        ->and($dashboard['billing_cards'])->not->toBeEmpty()
        ->and($dashboard['tenant_onboarding_cards'])->toBeEmpty()
        ->and($dashboard['contract_cards'])->toBeEmpty();

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Billing progress')
        ->assertDontSeeText('Tenant onboarding')
        ->assertDontSeeText('Contract attention');
});

it('counts billing, onboarding, contracts, documents, configuration, and integrity attention items', function (): void {
    $workspace = attentionDashboardWorkspace();

    attentionInvoice($workspace, [
        'invoice_number' => 'INV-WAITING',
        'automation_level' => 'reading_request',
        'approval_status' => 'pending',
    ]);
    attentionInvoice($workspace, [
        'invoice_number' => 'INV-SUBMITTED',
        'automation_level' => 'reading_request',
        'approval_status' => 'readings_submitted',
    ]);
    attentionInvoice($workspace, [
        'invoice_number' => 'INV-READY',
        'approval_status' => 'ready_for_review',
    ]);
    $overdue = attentionInvoice($workspace, [
        'invoice_number' => 'INV-OVERDUE',
        'status' => InvoiceStatus::FINALIZED,
        'due_date' => '2026-03-01',
    ]);
    $sent = attentionInvoice($workspace, [
        'invoice_number' => 'INV-SENT',
        'status' => InvoiceStatus::FINALIZED,
        'approval_status' => 'approved',
    ]);
    InvoiceEmailLog::factory()->for($sent)->for($workspace['organization'])->create([
        'sent_by_user_id' => $workspace['admin']->id,
        'recipient_email' => $workspace['tenant']->email,
        'sent_at' => now(),
    ]);
    attentionInvoice($workspace, [
        'invoice_number' => 'INV-PAID',
        'status' => InvoiceStatus::PAID,
        'amount_paid' => 100,
        'paid_at' => now(),
    ]);

    $notInvitedTenant = User::factory()->tenant()->create([
        'organization_id' => $workspace['organization']->id,
        'portal_access_enabled' => false,
        'status' => UserStatus::ACTIVE,
    ]);
    PropertyAssignment::factory()
        ->for($workspace['organization'])
        ->for($workspace['property'])
        ->for($notInvitedTenant, 'tenant')
        ->create(['unassigned_at' => null]);
    OrganizationInvitation::factory()->for($workspace['organization'])->create([
        'tenant_id' => $workspace['tenant']->id,
        'email' => $workspace['tenant']->email,
        'accepted_at' => null,
        'revoked_at' => null,
        'expires_at' => now()->subDay(),
    ]);

    RentalContract::factory()
        ->for($workspace['organization'])
        ->for($workspace['tenant'], 'tenant')
        ->for($workspace['property'])
        ->for($workspace['assignment'], 'propertyAssignment')
        ->create([
            'status' => RentalContractStatus::ACTIVE,
            'end_date' => now()->addDays(10)->toDateString(),
        ]);
    RentalContract::factory()
        ->for($workspace['organization'])
        ->for($notInvitedTenant, 'tenant')
        ->for($workspace['property'])
        ->create([
            'status' => RentalContractStatus::EXPIRED,
            'end_date' => now()->subDay()->toDateString(),
        ]);

    ServiceConfiguration::factory()->for($workspace['organization'])->for($workspace['property'])->create([
        'status' => ServiceConfigurationStatus::CONFIGURATION_ERROR,
        'is_active' => true,
        'tenant_visible' => true,
        'tenant_visible_description' => null,
        'tariff_id' => null,
    ]);
    ServiceConfiguration::factory()->fixedMonthly()->for($workspace['organization'])->for($workspace['property'])->create([
        'billing_method' => BillingMethod::FIXED_MONTHLY,
        'fixed_amount' => null,
    ]);

    MeterReading::factory()->for($workspace['organization'])->for($workspace['property'])->for($workspace['meter'])->create([
        'reading_date' => '2026-03-10',
        'validation_status' => MeterReadingValidationStatus::PENDING,
    ]);
    MeterReading::factory()->for($workspace['organization'])->for($workspace['property'])->for($workspace['meter'])->create([
        'reading_date' => '2026-03-10',
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);
    MeterReading::factory()->for($workspace['organization'])->for($workspace['property'])->for($workspace['meter'])->create([
        'reading_date' => '2026-03-12',
        'validation_status' => MeterReadingValidationStatus::REJECTED,
    ]);

    $duplicateInvoice = attentionInvoice($workspace, [
        'invoice_number' => 'INV-DUPLICATE',
    ]);
    InvoiceItem::factory()->for($duplicateInvoice)->create([
        'source_type' => InvoiceItemSourceType::FIXED_SERVICE,
        'source_id' => null,
        'description' => 'Duplicate fixed service',
        'total' => 10,
    ]);
    InvoiceItem::factory()->for($duplicateInvoice)->create([
        'source_type' => InvoiceItemSourceType::FIXED_SERVICE,
        'source_id' => null,
        'description' => 'Duplicate fixed service',
        'total' => 10,
    ]);
    InvoiceItem::factory()->for($duplicateInvoice)->create([
        'source_type' => InvoiceItemSourceType::EXTRA_CHARGE,
        'source_id' => 99,
        'description' => 'Duplicate charge',
        'total' => 10,
    ]);

    $includedTwiceInvoice = attentionInvoice($workspace, [
        'invoice_number' => 'INV-INCLUDED-TWICE',
    ]);
    InvoiceItem::factory()->for($includedTwiceInvoice)->create([
        'source_type' => InvoiceItemSourceType::EXTRA_CHARGE,
        'source_id' => 99,
        'description' => 'Duplicate charge',
        'total' => 10,
    ]);

    UserKycProfile::factory()->for($workspace['tenant'], 'user')->for($workspace['organization'])->create([
        'verification_status' => KycVerificationStatus::PENDING,
    ]);
    Attachment::factory()->for($workspace['organization'])->create([
        'uploaded_by_user_id' => $workspace['admin']->id,
        'created_at' => now()->subDay(),
    ]);

    $dashboard = app(BuildAdminAttentionDashboard::class)
        ->handle($workspace['organization']->id, $workspace['admin']->id)
        ->toArray();

    expect($dashboard['counts'])->toMatchArray([
        'invoices_waiting_for_readings' => 1,
        'invoices_with_submitted_readings' => 1,
        'invoices_ready_for_review' => 1,
        'invoices_overdue' => 1,
        'invoices_sent' => 1,
        'invoices_paid' => 1,
        'tenants_not_invited' => 1,
        'tenants_invitation_expired' => 1,
        'contracts_expiring_30_days' => 1,
        'contracts_expiring_14_days' => 1,
        'contracts_expired' => 1,
        'configuration_errors_total' => 1,
        'tenant_visible_services_without_description' => 1,
        'duplicate_active_readings' => 1,
        'duplicate_invoice_items' => 1,
        'charges_included_twice' => 1,
        'kyc_pending_review' => 1,
        'documents_uploaded_recently' => 1,
    ]);

    expect($overdue->fresh()->status)->toBe(InvoiceStatus::FINALIZED);
});

it('builds filtered action URLs for every attention surface', function (): void {
    $workspace = attentionDashboardWorkspace();

    $dashboard = app(BuildAdminAttentionDashboard::class)
        ->handle($workspace['organization']->id, $workspace['admin']->id)
        ->toArray();

    $billingCards = collect($dashboard['billing_cards'])->keyBy('key');
    $tenantCards = collect($dashboard['tenant_onboarding_cards'])->keyBy('key');
    $configurationCards = collect($dashboard['configuration_health_cards'])->keyBy('key');
    $contractCards = collect($dashboard['contract_cards'])->keyBy('key');
    $integrityCards = collect($dashboard['data_integrity_cards'])->keyBy('key');

    expect($billingCards['waiting_for_readings']['url'])->toContain(route('filament.admin.pages.billing-review-center', [], false), 'attention=waiting_for_readings')
        ->and($billingCards['submitted_readings']['url'])->toContain('attention=submitted_readings')
        ->and($tenantCards['tenants_not_invited']['url'])->toContain(route('filament.admin.resources.tenants.index', [], false), 'portal_status=not_invited')
        ->and($configurationCards['configuration_errors_total']['url'])->toContain(route('filament.admin.resources.service-configurations.index', [], false), 'attention=configuration_errors')
        ->and($integrityCards['duplicate_active_readings']['url'])->toContain(route('filament.admin.pages.billing-cleanup-center', [], false), 'attention=duplicate_active_readings')
        ->and($integrityCards['duplicate_invoice_items']['url'])->toContain(route('filament.admin.pages.billing-cleanup-center', [], false), 'attention=duplicate_invoice_items')
        ->and($contractCards['contracts_expiring_30_days']['url'])->toContain(route('filament.admin.resources.tenants.index', [], false), 'attention=contracts_expiring_30');
});

it('sorts blocking attention above medium and low priority items', function (): void {
    $workspace = attentionDashboardWorkspace();
    attentionInvoice($workspace, [
        'status' => InvoiceStatus::FINALIZED,
        'due_date' => '2026-03-01',
    ]);
    RentalContract::factory()->for($workspace['organization'])->for($workspace['tenant'], 'tenant')->for($workspace['property'])->create([
        'status' => RentalContractStatus::ACTIVE,
        'end_date' => now()->addDays(25)->toDateString(),
    ]);
    DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => 'dashboard.test',
        'notifiable_type' => User::class,
        'notifiable_id' => $workspace['admin']->id,
        'recipient_user_id' => $workspace['admin']->id,
        'organization_id' => $workspace['organization']->id,
        'data' => ['title' => 'Unread'],
        'read_at' => null,
    ]);

    $dashboard = app(BuildAdminAttentionDashboard::class)
        ->handle($workspace['organization']->id, $workspace['admin']->id)
        ->toArray();

    expect(collect($dashboard['needs_action_items'])->pluck('priority')->first())
        ->toBe('high');

    expect(collect($dashboard['needs_action_items'])->pluck('key')->all())
        ->toContain('overdue_invoices', 'contracts_expiring', 'unread_notifications');
});

it('loads the dashboard within a bounded query count', function (): void {
    $workspace = attentionDashboardWorkspace();

    foreach (range(1, 8) as $index) {
        attentionInvoice($workspace, [
            'invoice_number' => "INV-PERF-{$index}",
            'automation_level' => 'reading_request',
            'approval_status' => $index % 2 === 0 ? 'readings_submitted' : 'pending',
        ]);
        MeterReading::factory()->for($workspace['organization'])->for($workspace['property'])->for($workspace['meter'])->create([
            'reading_date' => "2026-03-0{$index}",
            'validation_status' => MeterReadingValidationStatus::PENDING,
        ]);
    }

    DB::connection()->enableQueryLog();

    app(BuildAdminAttentionDashboard::class)
        ->handle($workspace['organization']->id, $workspace['admin']->id)
        ->toArray();

    $queryCount = count(DB::getQueryLog());
    DB::connection()->disableQueryLog();

    expect($queryCount)->toBeLessThanOrEqual(60);
});

/**
 * @return array{
 *     organization: Organization,
 *     admin: User,
 *     building: Building,
 *     property: Property,
 *     tenant: User,
 *     assignment: PropertyAssignment,
 *     meter: Meter,
 *     period: BillingPeriod
 * }
 */
function attentionDashboardWorkspace(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'portal_access_enabled' => true,
    ]);
    $assignment = PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create(['unassigned_at' => null]);
    $meter = Meter::factory()->for($organization)->for($property)->create();
    $period = BillingPeriod::factory()->for($organization)->create([
        'name' => 'March 2026',
        'starts_at' => '2026-03-01',
        'ends_at' => '2026-03-31',
        'reading_submission_deadline' => '2026-03-20',
        'payment_due_date' => '2026-04-14',
    ]);

    return [
        'organization' => $organization->fresh(),
        'admin' => $admin->fresh(),
        'building' => $building->fresh(),
        'property' => $property->fresh(),
        'tenant' => $tenant->fresh(),
        'assignment' => $assignment->fresh(),
        'meter' => $meter->fresh(),
        'period' => $period->fresh(),
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 */
function attentionInvoice(array $workspace, array $overrides = []): Invoice
{
    return Invoice::factory()
        ->for($workspace['organization'])
        ->for($workspace['property'])
        ->for($workspace['tenant'], 'tenant')
        ->create([
            'billing_period_id' => $workspace['period']->id,
            'billing_period_start' => '2026-03-01',
            'billing_period_end' => '2026-03-31',
            'status' => InvoiceStatus::DRAFT,
            'finalized_at' => null,
            'paid_at' => null,
            'amount_paid' => 0,
            'paid_amount' => 0,
            'approval_status' => 'pending',
            'automation_level' => 'manual',
            ...$overrides,
        ]);
}
