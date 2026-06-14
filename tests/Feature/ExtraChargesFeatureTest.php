<?php

declare(strict_types=1);

use App\Contracts\BillingServiceInterface;
use App\Enums\ExtraChargeStatus;
use App\Enums\ExtraChargeTypeCode;
use App\Enums\InvoiceItemSourceType;
use App\Filament\Actions\Admin\ExtraCharges\ApproveExtraChargeAction;
use App\Filament\Actions\Admin\ExtraCharges\CreateExtraChargeAction;
use App\Filament\Actions\Admin\ExtraCharges\CreateExtraChargeTypeAction;
use App\Filament\Actions\Admin\ExtraCharges\RejectExtraChargeAction;
use App\Filament\Actions\Admin\ExtraCharges\UpdateExtraChargeAction;
use App\Models\AuditLog;
use App\Models\ExtraCharge;
use App\Models\ExtraChargeType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use App\Notifications\Billing\ExtraChargeRequiresApprovalNotification;
use App\Services\Billing\InvoicePresentationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-01-15 10:00:00');
    Notification::fake();

    config()->set('tenanto.billing.extra_charge_manager_approval_threshold', '100.00');
});

it('admin can create an extra charge type', function (): void {
    $fixture = extraChargeWorkspace();

    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::ONE_TIME_CHARGE, [
        'name' => 'Repair work',
        'default_amount' => '35.50',
        'requires_comment' => true,
    ]);

    expect($chargeType->organization_id)->toBe($fixture['organization']->id)
        ->and($chargeType->name)->toBe('Repair work')
        ->and($chargeType->type)->toBe(ExtraChargeTypeCode::ONE_TIME_CHARGE)
        ->and($chargeType->default_amount)->toBe('35.50')
        ->and($chargeType->requires_comment)->toBeTrue();

    expectAuditMutation($chargeType, 'extra_charge_type.created');
});

it('admin can create a one-time charge', function (): void {
    $fixture = extraChargeWorkspace();
    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::ONE_TIME_CHARGE);

    $charge = extraChargeFor($fixture, $chargeType, $fixture['admin'], [
        'title' => 'Balcony repair',
        'description_for_tenant' => 'Balcony railing repair approved for January.',
        'amount' => '42.25',
        'internal_note' => 'Vendor invoice R-442 should stay internal.',
    ]);

    expect($charge->tenant_id)->toBe($fixture['tenant']->id)
        ->and($charge->property_id)->toBe($fixture['property']->id)
        ->and($charge->status)->toBe(ExtraChargeStatus::APPROVED)
        ->and($charge->is_recurring)->toBeFalse()
        ->and($charge->total_amount)->toBe('42.25')
        ->and($charge->created_by_user_id)->toBe($fixture['admin']->id);

    expectAuditMutation($charge, 'extra_charge.created');
});

it('admin can create a recurring charge', function (): void {
    $fixture = extraChargeWorkspace();
    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::FIXED_SERVICE, [
        'name' => 'Parking subscription',
        'default_amount' => '30.00',
        'is_recurring' => true,
    ]);

    $charge = extraChargeFor($fixture, $chargeType, $fixture['admin'], [
        'title' => 'Parking space',
        'description_for_tenant' => 'Monthly parking space access.',
        'starts_at' => '2026-01-01',
        'ends_at' => null,
    ]);

    expect($charge->is_recurring)->toBeTrue()
        ->and($charge->status)->toBe(ExtraChargeStatus::APPROVED)
        ->and($charge->starts_at?->toDateString())->toBe('2026-01-01')
        ->and($charge->ends_at)->toBeNull();
});

it('shows tenant-visible descriptions and hides internal notes from tenant invoice presentation', function (): void {
    $fixture = extraChargeWorkspace();
    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::ONE_TIME_CHARGE);
    $charge = extraChargeFor($fixture, $chargeType, $fixture['admin'], [
        'title' => 'Kitchen sink repair',
        'description_for_tenant' => 'Emergency kitchen sink repair completed on January 9.',
        'internal_note' => 'Technician reported suspected misuse. Do not show tenant.',
        'amount' => '64.00',
    ]);

    $invoice = generateExtraChargeInvoices($fixture)['created']->sole();
    $item = $invoice->invoiceItems()->firstOrFail();
    $presentation = app(InvoicePresentationService::class)->present($invoice);
    $tenantPayload = json_encode($presentation, JSON_THROW_ON_ERROR);

    expect($item->source_type)->toBe(InvoiceItemSourceType::EXTRA_CHARGE)
        ->and($item->source_id)->toBe($charge->id)
        ->and($item->description_for_tenant)->toBe('Emergency kitchen sink repair completed on January 9.')
        ->and($item->internal_note)->toBe('Technician reported suspected misuse. Do not show tenant.')
        ->and($tenantPayload)->toContain('Emergency kitchen sink repair completed on January 9.')
        ->and($tenantPayload)->not->toContain('Technician reported suspected misuse. Do not show tenant.');
});

it('includes recurring charges monthly without duplicating repeated invoice generation for the same period', function (): void {
    $fixture = extraChargeWorkspace();
    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::FIXED_SERVICE, [
        'default_amount' => '18.00',
        'is_recurring' => true,
    ]);
    $charge = extraChargeFor($fixture, $chargeType, $fixture['admin'], [
        'title' => 'Sauna access',
        'description_for_tenant' => 'Monthly sauna access.',
        'starts_at' => '2026-01-01',
        'ends_at' => null,
    ]);

    $january = generateExtraChargeInvoices($fixture, '2026-01-01', '2026-01-31');
    $januaryInvoice = $january['created']->sole();
    $repeatJanuary = generateExtraChargeInvoices($fixture, '2026-01-01', '2026-01-31');

    expect(extraChargeInvoiceItemCount($charge))->toBe(1)
        ->and($repeatJanuary['created'])->toHaveCount(0)
        ->and($repeatJanuary['skipped'][0]['reason'] ?? null)->toBe('already_billed');

    $february = generateExtraChargeInvoices($fixture, '2026-02-01', '2026-02-28');
    $februaryInvoice = $february['created']->sole();

    expect($januaryInvoice->total_amount)->toBe('18.00')
        ->and($februaryInvoice->total_amount)->toBe('18.00')
        ->and(extraChargeInvoiceItemCount($charge))->toBe(2)
        ->and($charge->fresh()->status)->toBe(ExtraChargeStatus::INCLUDED_IN_INVOICE);
});

it('includes one-time charges only once', function (): void {
    $fixture = extraChargeWorkspace();
    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::ONE_TIME_CHARGE, [
        'default_amount' => '22.00',
    ]);
    $charge = extraChargeFor($fixture, $chargeType, $fixture['admin'], [
        'title' => 'Door key replacement',
        'description_for_tenant' => 'Replacement key issued in January.',
    ]);

    generateExtraChargeInvoices($fixture, '2026-01-01', '2026-01-31');
    generateExtraChargeInvoices($fixture, '2026-02-01', '2026-02-28');

    expect(extraChargeInvoiceItemCount($charge))->toBe(1)
        ->and($charge->fresh()->status)->toBe(ExtraChargeStatus::INCLUDED_IN_INVOICE)
        ->and($charge->fresh()->invoice_id)->not->toBeNull();
});

it('discount charges reduce invoice totals', function (): void {
    $fixture = extraChargeWorkspace();
    $penaltyType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::PENALTY, [
        'name' => 'Penalty',
        'default_amount' => '100.00',
    ]);
    $discountType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::DISCOUNT, [
        'name' => 'Goodwill discount',
        'default_amount' => '-15.00',
    ]);

    extraChargeFor($fixture, $penaltyType, $fixture['admin'], [
        'title' => 'Late checkout penalty',
        'description_for_tenant' => 'Late checkout penalty.',
    ]);
    extraChargeFor($fixture, $discountType, $fixture['admin'], [
        'title' => 'Goodwill credit',
        'description_for_tenant' => 'Goodwill credit for service interruption.',
        'amount' => '-15.00',
    ]);

    $invoice = generateExtraChargeInvoices($fixture)['created']->sole();

    expect($invoice->total_amount)->toBe('85.00')
        ->and($invoice->invoiceItems()->where('total', '-15.00')->exists())->toBeTrue();
});

it('penalty charges increase invoice totals', function (): void {
    $fixture = extraChargeWorkspace();
    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::PENALTY, [
        'default_amount' => '25.00',
    ]);

    extraChargeFor($fixture, $chargeType, $fixture['admin'], [
        'title' => 'Late payment penalty',
        'description_for_tenant' => 'Late payment penalty.',
    ]);

    $invoice = generateExtraChargeInvoices($fixture)['created']->sole();

    expect($invoice->total_amount)->toBe('25.00')
        ->and($invoice->invoiceItems()->where('source_type', InvoiceItemSourceType::EXTRA_CHARGE->value)->count())->toBe(1);
});

it('does not include rejected or voided charges in invoices', function (): void {
    $fixture = extraChargeWorkspace();
    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::MANUAL_EXPENSE);
    $rejectedCharge = extraChargeFor($fixture, $chargeType, $fixture['admin'], [
        'title' => 'Rejected receipt',
        'description_for_tenant' => 'Receipt charge pending correction.',
        'status' => ExtraChargeStatus::DRAFT->value,
    ]);
    $rejectedCharge = app(RejectExtraChargeAction::class)->handle($fixture['admin'], $rejectedCharge, 'Receipt duplicated.');
    $voidedCharge = ExtraCharge::factory()
        ->for($fixture['organization'])
        ->for($fixture['tenant'], 'tenant')
        ->for($fixture['property'])
        ->for($chargeType, 'type')
        ->voided()
        ->create([
            'organization_id' => $fixture['organization']->id,
            'tenant_id' => $fixture['tenant']->id,
            'property_id' => $fixture['property']->id,
            'extra_charge_type_id' => $chargeType->id,
            'title' => 'Voided provider invoice',
            'description_for_tenant' => 'Voided provider invoice.',
            'amount' => '44.00',
            'unit_price' => '44.0000',
            'total_amount' => '44.00',
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-01-31',
        ]);

    $invoice = generateExtraChargeInvoices($fixture)['created']->sole();

    expect($invoice->total_amount)->toBe('0.00')
        ->and(extraChargeInvoiceItemCount($rejectedCharge))->toBe(0)
        ->and(extraChargeInvoiceItemCount($voidedCharge))->toBe(0);

    expectAuditMutation($rejectedCharge, 'extra_charge.rejected');
});

it('does not silently edit charges included in finalized invoices', function (): void {
    $fixture = extraChargeWorkspace();
    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::ONE_TIME_CHARGE);
    $charge = extraChargeFor($fixture, $chargeType, $fixture['admin']);

    generateExtraChargeInvoices($fixture);

    expect(fn (): ExtraCharge => app(UpdateExtraChargeAction::class)->handle($fixture['admin'], $charge->fresh(['invoice']), [
        'title' => 'Changed after invoice finalization',
    ]))->toThrow(ValidationException::class);
});

it('requires approval for manager charges above the configured threshold', function (): void {
    $fixture = extraChargeWorkspace();
    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::MANUAL_EXPENSE, [
        'default_amount' => '150.00',
    ]);

    $charge = extraChargeFor($fixture, $chargeType, $fixture['manager'], [
        'title' => 'Large repair receipt',
        'description_for_tenant' => 'Large repair receipt awaiting admin approval.',
        'amount' => '150.00',
    ]);

    expect($charge->status)->toBe(ExtraChargeStatus::PENDING_REVIEW)
        ->and($charge->approved_by_user_id)->toBeNull()
        ->and($charge->approved_at)->toBeNull();

    Notification::assertSentTo($fixture['admin'], ExtraChargeRequiresApprovalNotification::class);

    $approvedCharge = app(ApproveExtraChargeAction::class)->handle($fixture['admin'], $charge);

    expect($approvedCharge->status)->toBe(ExtraChargeStatus::APPROVED)
        ->and($approvedCharge->approved_by_user_id)->toBe($fixture['admin']->id);
});

it('enforces organization isolation and tenant charge access', function (): void {
    $fixture = extraChargeWorkspace();
    $otherFixture = extraChargeWorkspace();
    $foreignType = extraChargeTypeFor($otherFixture, ExtraChargeTypeCode::ONE_TIME_CHARGE);

    expect(fn (): ExtraCharge => extraChargeFor($fixture, $foreignType, $fixture['admin']))
        ->toThrow(ValidationException::class);

    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::ONE_TIME_CHARGE);
    $charge = extraChargeFor($fixture, $chargeType, $fixture['admin']);

    expect($fixture['tenant']->can('view', $charge))->toBeTrue()
        ->and($otherFixture['tenant']->can('view', $charge))->toBeFalse()
        ->and($otherFixture['admin']->can('view', $charge))->toBeFalse();
});

it('writes audit logs for charge actions', function (): void {
    $fixture = extraChargeWorkspace();
    $chargeType = extraChargeTypeFor($fixture, ExtraChargeTypeCode::ONE_TIME_CHARGE);
    $draftCharge = extraChargeFor($fixture, $chargeType, $fixture['admin'], [
        'title' => 'Draft charge',
        'description_for_tenant' => 'Draft charge description.',
        'status' => ExtraChargeStatus::DRAFT->value,
    ]);

    $updatedCharge = app(UpdateExtraChargeAction::class)->handle($fixture['admin'], $draftCharge, [
        'title' => 'Updated draft charge',
    ]);
    $approvedCharge = app(ApproveExtraChargeAction::class)->handle($fixture['admin'], $updatedCharge);
    $rejectedCharge = extraChargeFor($fixture, $chargeType, $fixture['admin'], [
        'title' => 'Rejected draft charge',
        'description_for_tenant' => 'Rejected draft charge description.',
        'status' => ExtraChargeStatus::DRAFT->value,
    ]);
    $rejectedCharge = app(RejectExtraChargeAction::class)->handle($fixture['admin'], $rejectedCharge, 'No longer valid.');

    expectAuditMutation($chargeType, 'extra_charge_type.created');
    expectAuditMutation($draftCharge, 'extra_charge.created');
    expectAuditMutation($updatedCharge, 'extra_charge.updated');
    expectAuditMutation($approvedCharge, 'extra_charge.approved');
    expectAuditMutation($rejectedCharge, 'extra_charge.rejected');
});

/**
 * @return array{
 *     organization: Organization,
 *     admin: User,
 *     manager: User,
 *     tenant: User,
 *     property: Property,
 *     assignment: PropertyAssignment
 * }
 */
function extraChargeWorkspace(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $property = Property::factory()->for($organization)->create();
    $assignment = PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => '2025-12-01 00:00:00',
            'unassigned_at' => null,
        ]);

    return [
        'organization' => $organization->fresh(),
        'admin' => $admin->fresh(),
        'manager' => $manager->fresh(),
        'tenant' => $tenant->fresh(),
        'property' => $property->fresh(),
        'assignment' => $assignment->fresh(),
    ];
}

/**
 * @param  array{organization: Organization, admin: User}  $fixture
 * @param  array<string, mixed>  $overrides
 */
function extraChargeTypeFor(array $fixture, ExtraChargeTypeCode $typeCode, array $overrides = []): ExtraChargeType
{
    return app(CreateExtraChargeTypeAction::class)->handle(
        $fixture['admin'],
        $fixture['organization'],
        [
            'name' => $overrides['name'] ?? str($typeCode->value)->replace('_', ' ')->title()->toString(),
            'type' => $typeCode->value,
            'default_amount' => $overrides['default_amount'] ?? ($typeCode->allowsNegativeAmount() ? '-10.00' : '25.00'),
            'currency' => $overrides['currency'] ?? 'EUR',
            'is_recurring' => $overrides['is_recurring'] ?? ($typeCode === ExtraChargeTypeCode::FIXED_SERVICE),
            'is_taxable' => $overrides['is_taxable'] ?? false,
            'tenant_visible_by_default' => $overrides['tenant_visible_by_default'] ?? true,
            'requires_comment' => $overrides['requires_comment'] ?? false,
            'requires_attachment' => $overrides['requires_attachment'] ?? false,
            'is_active' => $overrides['is_active'] ?? true,
        ],
    );
}

/**
 * @param  array{
 *     organization: Organization,
 *     admin: User,
 *     tenant: User,
 *     property: Property
 * }  $fixture
 * @param  array<string, mixed>  $overrides
 */
function extraChargeFor(array $fixture, ExtraChargeType $chargeType, ?User $actor = null, array $overrides = []): ExtraCharge
{
    $amount = (string) ($overrides['amount'] ?? $chargeType->default_amount);

    return app(CreateExtraChargeAction::class)->handle(
        $actor ?? $fixture['admin'],
        $fixture['organization'],
        [
            'tenant_id' => $fixture['tenant']->id,
            'property_id' => $fixture['property']->id,
            'extra_charge_type_id' => $chargeType->id,
            'title' => $overrides['title'] ?? $chargeType->name,
            'description_for_tenant' => $overrides['description_for_tenant'] ?? $chargeType->name.' for January billing.',
            'internal_note' => $overrides['internal_note'] ?? 'Internal admin note.',
            'amount' => $amount,
            'currency' => $overrides['currency'] ?? $chargeType->currency,
            'quantity' => $overrides['quantity'] ?? '1',
            'unit_price' => $overrides['unit_price'] ?? $amount,
            'tax_amount' => $overrides['tax_amount'] ?? '0',
            'status' => $overrides['status'] ?? null,
            'is_recurring' => $overrides['is_recurring'] ?? $chargeType->is_recurring,
            'starts_at' => $overrides['starts_at'] ?? '2026-01-01',
            'ends_at' => array_key_exists('ends_at', $overrides) ? $overrides['ends_at'] : '2026-01-31',
        ],
    );
}

/**
 * @param  array{organization: Organization, admin: User}  $fixture
 * @return array{created: Collection<int, Invoice>, skipped: array<int, array<string, mixed>>}
 */
function generateExtraChargeInvoices(array $fixture, string $periodStart = '2026-01-01', string $periodEnd = '2026-01-31'): array
{
    return app(BillingServiceInterface::class)->generateBulkInvoices($fixture['organization'], [
        'billing_period_start' => $periodStart,
        'billing_period_end' => $periodEnd,
        'due_date' => Carbon::parse($periodEnd)->addDays(14)->toDateString(),
    ], $fixture['admin']);
}

function extraChargeInvoiceItemCount(ExtraCharge $charge): int
{
    return InvoiceItem::query()
        ->where('source_type', InvoiceItemSourceType::EXTRA_CHARGE->value)
        ->where('source_id', $charge->id)
        ->count();
}

function expectAuditMutation(Model $subject, string $mutation): void
{
    $hasAuditLog = AuditLog::query()
        ->forSubject($subject)
        ->get()
        ->contains(fn (AuditLog $auditLog): bool => data_get($auditLog->metadata, 'context.mutation') === $mutation);

    expect($hasAuditLog)->toBeTrue();
}
