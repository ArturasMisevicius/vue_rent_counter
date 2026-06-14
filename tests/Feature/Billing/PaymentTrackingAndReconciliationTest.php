<?php

use App\Actions\Billing\ConfirmInvoicePayment;
use App\Actions\Billing\CreateManualPayment;
use App\Actions\Billing\MarkOverdueInvoices;
use App\Actions\Billing\RejectInvoicePayment;
use App\Actions\Billing\SendPaymentReminders;
use App\Actions\Billing\SubmitTenantPaymentProof;
use App\Actions\Billing\VoidInvoicePayment;
use App\Enums\AuditLogAction;
use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Jobs\SendInvoiceReminderJob;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\InvoicePresentationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('lets a tenant submit payment proof for their own invoice without closing the invoice', function (): void {
    Storage::fake('local');
    $workspace = paymentTrackingWorkspace([
        'total_amount' => 161.50,
        'balance_amount' => 161.50,
        'payment_reference' => 'INV-2026-00015',
    ]);

    $payment = app(SubmitTenantPaymentProof::class)->handle($workspace['invoice'], $workspace['tenant'], [
        'amount' => 161.50,
        'payment_date' => now()->toDateString(),
        'payment_method' => PaymentMethod::BANK_TRANSFER->value,
        'reference' => 'INV-2026-00015',
        'transaction_id' => 'TRX-123',
        'tenant_comment' => 'Paid from my bank.',
        'proof_file' => UploadedFile::fake()->create('receipt.pdf', 12, 'application/pdf'),
    ]);

    $workspace['invoice']->refresh();

    expect($payment->status)->toBe(PaymentStatus::PENDING)
        ->and($payment->attachments)->toHaveCount(1)
        ->and((float) $workspace['invoice']->paid_amount)->toBe(0.0)
        ->and($workspace['invoice']->payment_status)->toBe(InvoicePaymentStatus::UNPAID)
        ->and(AuditLog::query()
            ->forSubject($payment)
            ->where('action', AuditLogAction::CREATED)
            ->where('metadata->context->mutation', 'payment.proof_submitted')
            ->exists())->toBeTrue();

    $attachment = $payment->attachments->firstOrFail();

    $this->actingAs($workspace['tenant'])
        ->get(route('tenant.attachments.show', $attachment))
        ->assertOk();

    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    $this->actingAs($otherTenant)
        ->get(route('tenant.attachments.show', $attachment))
        ->assertForbidden();
});

it('blocks tenant payment proof for another tenant invoice', function (): void {
    $workspace = paymentTrackingWorkspace();
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    expect(fn () => app(SubmitTenantPaymentProof::class)->handle($workspace['invoice'], $otherTenant, [
        'amount' => 25,
        'payment_date' => now()->toDateString(),
        'payment_method' => PaymentMethod::CASH->value,
    ]))->toThrow(AuthorizationException::class);
});

it('reconciles partial, full, and overpaid manual payments from confirmed payments only', function (): void {
    $workspace = paymentTrackingWorkspace([
        'total_amount' => 300,
        'balance_amount' => 300,
    ]);

    app(CreateManualPayment::class)->handle($workspace['invoice'], $workspace['admin'], [
        'amount' => 100,
        'payment_method' => PaymentMethod::CASH->value,
        'payment_date' => now()->toDateString(),
        'reference' => 'CASH-1',
        'confirm_immediately' => true,
    ]);

    $invoice = $workspace['invoice']->fresh();

    expect((float) $invoice->paid_amount)->toBe(100.0)
        ->and((float) $invoice->balance_amount)->toBe(200.0)
        ->and($invoice->status)->toBe(InvoiceStatus::PARTIALLY_PAID)
        ->and($invoice->payment_status)->toBe(InvoicePaymentStatus::PARTIALLY_PAID);

    app(CreateManualPayment::class)->handle($invoice, $workspace['admin'], [
        'amount' => 200,
        'payment_method' => PaymentMethod::BANK_TRANSFER->value,
        'payment_date' => now()->toDateString(),
        'reference' => 'WIRE-2',
        'confirm_immediately' => true,
    ]);

    $invoice = $invoice->fresh();

    expect((float) $invoice->paid_amount)->toBe(300.0)
        ->and((float) $invoice->balance_amount)->toBe(0.0)
        ->and($invoice->status)->toBe(InvoiceStatus::PAID)
        ->and($invoice->payment_status)->toBe(InvoicePaymentStatus::PAID)
        ->and($invoice->paid_at)->not->toBeNull();

    app(CreateManualPayment::class)->handle($invoice, $workspace['admin'], [
        'amount' => 25,
        'payment_method' => PaymentMethod::BANK_TRANSFER->value,
        'payment_date' => now()->toDateString(),
        'reference' => 'WIRE-3',
        'confirm_immediately' => true,
    ]);

    $invoice = $invoice->fresh();

    expect((float) $invoice->paid_amount)->toBe(325.0)
        ->and((float) $invoice->balance_amount)->toBe(0.0)
        ->and($invoice->status)->toBe(InvoiceStatus::PAID)
        ->and($invoice->payment_status)->toBe(InvoicePaymentStatus::OVERPAID);
});

it('requires a rejection reason and leaves invoice totals unchanged', function (): void {
    $workspace = paymentTrackingWorkspace([
        'total_amount' => 200,
        'balance_amount' => 200,
    ]);
    $payment = app(CreateManualPayment::class)->handle($workspace['invoice'], $workspace['admin'], [
        'amount' => 75,
        'payment_method' => PaymentMethod::BANK_TRANSFER->value,
        'payment_date' => now()->toDateString(),
        'reference' => 'BAD-PROOF',
        'confirm_immediately' => false,
    ]);

    expect(fn () => app(RejectInvoicePayment::class)->handle($payment, $workspace['admin'], ''))
        ->toThrow(ValidationException::class);

    $rejected = app(RejectInvoicePayment::class)->handle($payment, $workspace['admin'], 'Receipt is unreadable.');
    $invoice = $workspace['invoice']->fresh();

    expect($rejected->status)->toBe(PaymentStatus::FAILED)
        ->and($rejected->rejection_reason)->toBe('Receipt is unreadable.')
        ->and((float) $invoice->paid_amount)->toBe(0.0)
        ->and((float) $invoice->balance_amount)->toBe(200.0)
        ->and(AuditLog::query()
            ->forSubject($rejected)
            ->where('action', AuditLogAction::REJECTED)
            ->where('metadata->context->mutation', 'payment.rejected')
            ->exists())->toBeTrue();
});

it('requires a void reason and recalculates invoice balance after voiding a confirmed payment', function (): void {
    $workspace = paymentTrackingWorkspace([
        'total_amount' => 200,
        'balance_amount' => 200,
    ]);
    $payment = app(CreateManualPayment::class)->handle($workspace['invoice'], $workspace['admin'], [
        'amount' => 200,
        'payment_method' => PaymentMethod::CASH->value,
        'payment_date' => now()->toDateString(),
        'reference' => 'CASH-FULL',
        'confirm_immediately' => true,
    ]);

    expect(fn () => app(VoidInvoicePayment::class)->handle($payment, $workspace['admin'], ''))
        ->toThrow(ValidationException::class);

    $voided = app(VoidInvoicePayment::class)->handle($payment->fresh(), $workspace['admin'], 'Duplicate receipt.');
    $invoice = $workspace['invoice']->fresh();

    expect($voided->status)->toBe(PaymentStatus::VOIDED)
        ->and($voided->void_reason)->toBe('Duplicate receipt.')
        ->and((float) $invoice->paid_amount)->toBe(0.0)
        ->and((float) $invoice->balance_amount)->toBe(200.0)
        ->and($invoice->payment_status)->toBe(InvoicePaymentStatus::UNPAID)
        ->and(AuditLog::query()
            ->forSubject($voided)
            ->where('action', AuditLogAction::UPDATED)
            ->where('metadata->context->mutation', 'payment.voided')
            ->exists())->toBeTrue();
});

it('marks overdue invoices automatically but skips paid invoices', function (): void {
    $workspace = paymentTrackingWorkspace([
        'due_date' => now()->subDay()->toDateString(),
        'total_amount' => 100,
        'balance_amount' => 100,
    ]);
    $paidWorkspace = paymentTrackingWorkspace([
        'due_date' => now()->subDay()->toDateString(),
        'status' => InvoiceStatus::PAID,
        'payment_status' => InvoicePaymentStatus::PAID,
        'total_amount' => 100,
        'amount_paid' => 100,
        'paid_amount' => 100,
        'balance_amount' => 0,
        'paid_at' => now()->subDay(),
    ]);

    $marked = app(MarkOverdueInvoices::class)->handle(now(), $workspace['admin']);

    expect($marked)->toBe(1)
        ->and($workspace['invoice']->fresh()->status)->toBe(InvoiceStatus::OVERDUE)
        ->and($workspace['invoice']->fresh()->payment_status)->toBe(InvoicePaymentStatus::OVERDUE)
        ->and($paidWorkspace['invoice']->fresh()->status)->toBe(InvoiceStatus::PAID)
        ->and($paidWorkspace['invoice']->fresh()->payment_status)->toBe(InvoicePaymentStatus::PAID);
});

it('queues overdue reminders only when the invoice still has open balance and no pending payment review', function (): void {
    Queue::fake();
    $workspace = paymentTrackingWorkspace([
        'status' => InvoiceStatus::OVERDUE,
        'payment_status' => InvoicePaymentStatus::OVERDUE,
        'due_date' => now()->subDays(4)->toDateString(),
        'total_amount' => 100,
        'balance_amount' => 100,
    ]);
    $pendingWorkspace = paymentTrackingWorkspace([
        'status' => InvoiceStatus::OVERDUE,
        'payment_status' => InvoicePaymentStatus::OVERDUE,
        'due_date' => now()->subDays(4)->toDateString(),
        'total_amount' => 100,
        'balance_amount' => 100,
    ]);

    app(CreateManualPayment::class)->handle($pendingWorkspace['invoice'], $pendingWorkspace['admin'], [
        'amount' => 100,
        'payment_method' => PaymentMethod::BANK_TRANSFER->value,
        'payment_date' => now()->toDateString(),
        'confirm_immediately' => false,
    ]);

    $result = app(SendPaymentReminders::class)->handle($workspace['admin']);

    expect($result)->toBe([
        'queued' => 1,
        'skipped_pending_review' => 1,
    ]);

    Queue::assertPushed(SendInvoiceReminderJob::class, 1);

    app(ConfirmInvoicePayment::class)->handle($pendingWorkspace['invoice']->payments()->firstOrFail(), $pendingWorkspace['admin']);

    Queue::fake();
    $result = app(SendPaymentReminders::class)->handle($workspace['admin']);

    expect($result['queued'])->toBe(1);
    Queue::assertPushed(SendInvoiceReminderJob::class, 1);
});

it('does not expose payment internal notes to the tenant invoice presentation', function (): void {
    $workspace = paymentTrackingWorkspace();

    app(CreateManualPayment::class)->handle($workspace['invoice'], $workspace['admin'], [
        'amount' => 25,
        'payment_method' => PaymentMethod::CASH->value,
        'payment_date' => now()->toDateString(),
        'internal_note' => 'Do not show this note to tenant.',
        'tenant_comment' => 'Tenant-safe comment.',
        'confirm_immediately' => true,
    ]);

    $invoice = $workspace['invoice']->fresh([
        'payments.attachments',
        'tenant:id,organization_id,name,email',
        'property:id,organization_id,name,unit_number',
    ]);
    $presentation = app(InvoicePresentationService::class)->present($invoice);

    expect($presentation['payments'])->toHaveCount(1)
        ->and($presentation['payments'][0])->not->toHaveKey('internal_note')
        ->and($presentation['payments'][0])->toHaveKey('status_label');
});

it('blocks admins from recording payments for another organization invoice', function (): void {
    $workspace = paymentTrackingWorkspace();
    $otherWorkspace = paymentTrackingWorkspace();

    expect(fn () => app(CreateManualPayment::class)->handle($otherWorkspace['invoice'], $workspace['admin'], [
        'amount' => 25,
        'payment_method' => PaymentMethod::CASH->value,
        'payment_date' => now()->toDateString(),
        'confirm_immediately' => true,
    ]))->toThrow(AuthorizationException::class);
});

/**
 * @param  array<string, mixed>  $invoiceOverrides
 * @return array{organization: Organization, admin: User, tenant: User, invoice: Invoice}
 */
function paymentTrackingWorkspace(array $invoiceOverrides = []): array
{
    $workspace = createOrgWithAdmin();
    $tenantContext = createTenantInOrg($workspace['admin']);

    $invoice = Invoice::factory()
        ->for($workspace['organization'])
        ->for($tenantContext['property'])
        ->for($tenantContext['tenant'], 'tenant')
        ->create([
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'status' => InvoiceStatus::FINALIZED,
            'payment_status' => InvoicePaymentStatus::UNPAID,
            'currency' => 'EUR',
            'total_amount' => 100,
            'amount_paid' => 0,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'due_date' => now()->addDays(7)->toDateString(),
            'payment_reference' => null,
            ...$invoiceOverrides,
        ]);

    return [
        'organization' => $workspace['organization'],
        'admin' => $workspace['admin'],
        'tenant' => $tenantContext['tenant'],
        'invoice' => $invoice,
    ];
}
