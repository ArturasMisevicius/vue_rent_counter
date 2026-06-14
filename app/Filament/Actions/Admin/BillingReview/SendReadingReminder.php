<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Enums\AuditLogAction;
use App\Enums\UserRole;
use App\Filament\Support\Admin\BillingReview\BuildBillingReviewForPeriod;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\Billing\MissingReadingReminderNotification;
use App\Notifications\Billing\ReadingDeadlineMissingReadingsNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class SendReadingReminder
{
    public function __construct(
        private BuildBillingReviewForPeriod $buildBillingReviewForPeriod,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(Invoice $invoice, ?User $actor = null): bool
    {
        $actor ??= auth()->user();
        $invoice->loadMissing('tenant:id,organization_id,name,email');

        if (! $invoice->tenant instanceof User) {
            return false;
        }

        $review = $this->buildBillingReviewForPeriod->forInvoice($invoice);

        if ($review->missingReadings === []) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.billing_review.errors.no_missing_readings'),
            ]);
        }

        DB::transaction(function () use ($invoice, $actor, $review): void {
            $invoice->forceFill([
                'last_reminder_sent_at' => now(),
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::SENT,
                $invoice,
                [
                    'context' => ['mutation' => 'billing_review.reading_reminder.sent'],
                    'missing_readings' => $review->missingReadings,
                ],
                $actor?->id,
                'Reading reminder sent from billing review',
            );
        });

        $invoice->tenant->notify(new MissingReadingReminderNotification($invoice, $review->missingReadings));

        User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role'])
            ->forOrganization($invoice->organization_id)
            ->whereIn('role', [UserRole::ADMIN, UserRole::MANAGER])
            ->get()
            ->each(fn (User $user) => $user->notify(new ReadingDeadlineMissingReadingsNotification($invoice, $review->missingReadings)));

        return true;
    }
}
