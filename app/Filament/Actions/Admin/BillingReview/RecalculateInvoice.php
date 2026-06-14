<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Contracts\BillingServiceInterface;
use App\Enums\AuditLogAction;
use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Filament\Support\Admin\BillingReview\BuildBillingReviewForPeriod;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\Billing\InvoiceConfigurationErrorsNotification;
use App\Notifications\Billing\InvoiceReadyForReviewNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecalculateInvoice
{
    public function __construct(
        private BuildBillingReviewForPeriod $buildBillingReviewForPeriod,
        private BillingServiceInterface $billingService,
        private SubscriptionLimitGuard $subscriptionLimitGuard,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(Invoice $invoice, ?User $actor = null): Invoice
    {
        $actor ??= auth()->user();
        $this->subscriptionLimitGuard->ensureCanWrite($invoice->organization_id);

        if ($invoice->status !== InvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.billing_review.errors.recalculate_draft_only'),
            ]);
        }

        $review = $this->buildBillingReviewForPeriod->forInvoice($invoice);
        $before = [
            'total_amount' => $invoice->total_amount,
            'items' => $invoice->items,
            'approval_status' => $invoice->approval_status,
        ];

        $freshInvoice = DB::transaction(function () use ($invoice, $review, $actor, $before): Invoice {
            $updatedInvoice = $this->billingService->saveDraft($invoice, [
                'items' => $review->invoiceItemPayload,
            ]);
            $metadata = is_array($updatedInvoice->approval_metadata) ? $updatedInvoice->approval_metadata : [];

            $updatedInvoice->forceFill([
                'approval_status' => $review->blockingErrors === [] ? 'ready_for_review' : 'needs_attention',
                'approval_metadata' => [
                    ...$metadata,
                    'billing_review_recalculated_at' => now()->toISOString(),
                    'billing_review_recalculated_by_user_id' => $actor?->id,
                    'billing_review_blocking_errors' => $review->blockingErrors,
                    'billing_review_warnings' => $review->warnings,
                ],
            ])->save();

            $fresh = $updatedInvoice->fresh(['invoiceItems', 'payments', 'emailLogs']);

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $fresh,
                [
                    'workspace' => [
                        'organization_id' => $fresh->organization_id,
                        'property_id' => $fresh->property_id,
                        'tenant_user_id' => $fresh->tenant_user_id,
                    ],
                    'context' => ['mutation' => 'billing_review.invoice.recalculated'],
                    'before' => $before,
                    'after' => [
                        'total_amount' => $fresh->total_amount,
                        'items' => $fresh->items,
                        'approval_status' => $fresh->approval_status,
                    ],
                ],
                $actor?->id,
                'Invoice recalculated from billing review',
            );

            return $fresh;
        });

        $this->notifyReviewWatchers($freshInvoice, $review->blockingErrors === []);

        return $freshInvoice;
    }

    private function notifyReviewWatchers(Invoice $invoice, bool $ready): void
    {
        User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role'])
            ->forOrganization($invoice->organization_id)
            ->whereIn('role', [UserRole::ADMIN, UserRole::MANAGER])
            ->get()
            ->each(fn (User $user) => $user->notify(
                $ready
                    ? new InvoiceReadyForReviewNotification($invoice)
                    : new InvoiceConfigurationErrorsNotification($invoice),
            ));
    }
}
