<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SendInvoiceReminderJob;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class SendPaymentReminders
{
    /**
     * @return array{queued: int, skipped_pending_review: int}
     */
    public function handle(User $actor, CarbonInterface|string|null $asOf = null): array
    {
        $today = $asOf instanceof CarbonInterface ? $asOf->copy()->startOfDay() : now()->startOfDay();
        $queued = 0;
        $skippedPendingReview = 0;

        Invoice::query()
            ->select([
                'id',
                'organization_id',
                'tenant_user_id',
                'invoice_number',
                'status',
                'payment_status',
                'currency',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'balance_amount',
                'due_date',
                'billing_period_end',
                'last_reminder_sent_at',
            ])
            ->with(['tenant:id,organization_id,email'])
            ->where('status', InvoiceStatus::OVERDUE)
            ->where('payment_status', InvoicePaymentStatus::OVERDUE)
            ->where(function (Builder $query) use ($today): void {
                $query
                    ->whereNull('last_reminder_sent_at')
                    ->orWhereDate('last_reminder_sent_at', '<=', $today->copy()->subDays(3)->toDateString());
            })
            ->chunkById(100, function ($invoices) use (&$queued, &$skippedPendingReview, $actor): void {
                foreach ($invoices as $invoice) {
                    $hasPendingReview = InvoicePayment::query()
                        ->where('invoice_id', $invoice->id)
                        ->where('status', PaymentStatus::PENDING)
                        ->exists();

                    if ($hasPendingReview) {
                        $skippedPendingReview++;

                        continue;
                    }

                    $recipientEmail = (string) ($invoice->tenant?->email ?? '');

                    if ($recipientEmail === '') {
                        continue;
                    }

                    SendInvoiceReminderJob::dispatch($invoice->id, $actor->id, $recipientEmail);
                    $queued++;
                }
            });

        return [
            'queued' => $queued,
            'skipped_pending_review' => $skippedPendingReview,
        ];
    }
}
