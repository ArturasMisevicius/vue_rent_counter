<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\AuditLogAction;
use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\Tenant\SubmitPaymentProofRequest;
use App\Models\Attachment;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use App\Notifications\Billing\TenantPaymentProofSubmittedNotification;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmitTenantPaymentProof
{
    public const DOCUMENT_TYPE = 'invoice_payment_proof';

    private const DISK = 'local';

    private const DIRECTORY = 'invoice-payment-proofs';

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Invoice $invoice, User $tenant, array $attributes): InvoicePayment
    {
        Gate::forUser($tenant)->authorize('view', $invoice);
        $this->ensureTenantOwnsInvoice($invoice, $tenant);
        $this->ensureInvoiceCanReceivePayment($invoice);

        $validated = (new SubmitPaymentProofRequest)->validatePayload($attributes, $tenant);
        $paymentMethod = $validated['payment_method'] instanceof PaymentMethod
            ? $validated['payment_method']
            : PaymentMethod::from((string) $validated['payment_method']);

        $this->ensureProofOrReferenceForBankTransfer($paymentMethod, $validated);

        $payment = DB::transaction(function () use ($invoice, $tenant, $validated, $paymentMethod): InvoicePayment {
            $payment = InvoicePayment::query()->create([
                'invoice_id' => $invoice->id,
                'organization_id' => $invoice->organization_id,
                'tenant_id' => $tenant->id,
                'property_id' => $invoice->property_id,
                'submitted_by_user_id' => $tenant->id,
                'amount' => round((float) $validated['amount'], 2),
                'currency' => (string) $invoice->currency,
                'method' => $paymentMethod,
                'payment_method' => $paymentMethod,
                'status' => PaymentStatus::PENDING,
                'payment_date' => $validated['payment_date'],
                'paid_at' => $validated['payment_date'],
                'reference' => $validated['reference'] ?? null,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'tenant_comment' => $validated['tenant_comment'] ?? null,
            ]);

            $attachment = $this->storeProofAttachment($payment, $tenant, $validated['proof_file'] ?? null);

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $payment,
                [
                    'workspace' => $this->workspaceContext($payment),
                    'context' => [
                        'mutation' => 'payment.proof_submitted',
                    ],
                    'after' => [
                        'invoice_id' => $payment->invoice_id,
                        'amount' => (float) $payment->amount,
                        'currency' => $payment->currency,
                        'payment_method' => $paymentMethod->value,
                        'status' => PaymentStatus::PENDING->value,
                        'reference' => $payment->reference,
                        'attachment_id' => $attachment?->id,
                    ],
                ],
                $tenant->id,
                'Tenant payment proof submitted',
            );

            return $payment->fresh(['attachments']);
        });

        $this->notifyAdmins($payment, $tenant);

        return $payment;
    }

    private function ensureTenantOwnsInvoice(Invoice $invoice, User $tenant): void
    {
        if ($invoice->tenant_user_id !== $tenant->id || $invoice->organization_id !== $tenant->organization_id) {
            throw ValidationException::withMessages([
                'invoice' => __('tenant.pages.invoices.validation.invoice_not_available'),
            ]);
        }
    }

    private function ensureInvoiceCanReceivePayment(Invoice $invoice): void
    {
        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::tryFrom((string) $invoice->status);
        $paymentStatus = $invoice->payment_status instanceof InvoicePaymentStatus
            ? $invoice->payment_status
            : InvoicePaymentStatus::tryFrom((string) $invoice->payment_status);

        if (! in_array($status, [
            InvoiceStatus::FINALIZED,
            InvoiceStatus::PARTIALLY_PAID,
            InvoiceStatus::OVERDUE,
        ], true)) {
            throw ValidationException::withMessages([
                'invoice' => __('tenant.pages.invoices.validation.invoice_not_payable'),
            ]);
        }

        if (in_array($paymentStatus, [
            InvoicePaymentStatus::CANCELLED,
            InvoicePaymentStatus::VOIDED,
            InvoicePaymentStatus::REFUNDED,
        ], true)) {
            throw ValidationException::withMessages([
                'invoice' => __('tenant.pages.invoices.validation.invoice_not_payable'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ensureProofOrReferenceForBankTransfer(PaymentMethod $method, array $validated): void
    {
        if ($method !== PaymentMethod::BANK_TRANSFER) {
            return;
        }

        if (filled($validated['reference'] ?? null) || ($validated['proof_file'] ?? null) instanceof UploadedFile) {
            return;
        }

        throw ValidationException::withMessages([
            'reference' => __('tenant.pages.invoices.validation.bank_transfer_reference_or_proof_required'),
        ]);
    }

    private function storeProofAttachment(InvoicePayment $payment, User $tenant, mixed $proofFile): ?Attachment
    {
        if (! $proofFile instanceof UploadedFile) {
            return null;
        }

        $path = $proofFile->store(self::DIRECTORY.'/'.$payment->organization_id.'/'.$payment->invoice_id, self::DISK);
        $storage = $this->storage();

        $attachment = new Attachment([
            'organization_id' => $payment->organization_id,
            'uploaded_by_user_id' => $tenant->id,
            'filename' => basename((string) $path),
            'original_filename' => $proofFile->getClientOriginalName(),
            'mime_type' => $storage->mimeType((string) $path) ?: $proofFile->getClientMimeType(),
            'size' => $storage->size((string) $path) ?: $proofFile->getSize(),
            'disk' => self::DISK,
            'path' => $path,
            'document_type' => self::DOCUMENT_TYPE,
            'tenant_visible' => true,
            'description' => null,
            'metadata' => [
                'invoice_id' => $payment->invoice_id,
                'payment_id' => $payment->id,
            ],
        ]);

        $payment->attachments()->save($attachment);

        return $attachment->refresh();
    }

    /**
     * @return array{organization_id: int, tenant_id: int|null, invoice_id: int, payment_id: int|null}
     */
    private function workspaceContext(InvoicePayment $payment): array
    {
        return [
            'organization_id' => $payment->organization_id,
            'tenant_id' => $payment->tenant_id,
            'invoice_id' => $payment->invoice_id,
            'payment_id' => $payment->id,
        ];
    }

    private function notifyAdmins(InvoicePayment $payment, User $tenant): void
    {
        $payment = $payment->fresh(['invoice', 'tenant']) ?? $payment;

        User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role'])
            ->where('organization_id', $payment->organization_id)
            ->whereIn('role', [
                UserRole::ADMIN,
                UserRole::MANAGER,
            ])
            ->whereKeyNot($tenant->id)
            ->get()
            ->each(fn (User $admin): mixed => $admin->notify(
                new TenantPaymentProofSubmittedNotification($payment),
            ));
    }

    private function storage(): FilesystemAdapter
    {
        $storage = Storage::disk(self::DISK);

        if (! $storage instanceof FilesystemAdapter) {
            throw new \RuntimeException('Unsupported filesystem adapter.');
        }

        return $storage;
    }
}
