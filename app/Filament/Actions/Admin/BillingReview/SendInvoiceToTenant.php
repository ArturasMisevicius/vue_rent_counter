<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Enums\AuditLogAction;
use App\Enums\InvoiceStatus;
use App\Filament\Actions\Admin\Invoices\SendInvoiceEmailAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class SendInvoiceToTenant
{
    public function __construct(
        private SendInvoiceEmailAction $sendInvoiceEmailAction,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(Invoice $invoice, User $actor, ?string $recipientEmail = null, ?string $personalMessage = null): bool
    {
        if ($invoice->status === InvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.billing_review.errors.send_finalized_only'),
            ]);
        }

        $sent = $this->sendInvoiceEmailAction->handle($invoice, $actor, $recipientEmail, $personalMessage);

        if (! $sent) {
            return false;
        }

        $this->auditLogger->record(
            AuditLogAction::SENT,
            $invoice,
            [
                'context' => ['mutation' => 'billing_review.invoice.sent_to_tenant'],
                'recipient_email' => $recipientEmail ?: $invoice->tenant?->email,
            ],
            $actor->id,
            'Invoice sent to tenant from billing review',
        );

        return true;
    }
}
