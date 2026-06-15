<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Enums\AuditLogAction;
use App\Enums\MeterReadingStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\MeterReading;
use App\Models\User;
use App\Notifications\Billing\ReadingRejectedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RejectReading
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    public function handle(MeterReading $reading, string $tenantVisibleComment, ?User $actor = null): MeterReading
    {
        $actor ??= auth()->user();
        $this->authorize($reading, $actor);

        if (blank($tenantVisibleComment)) {
            throw ValidationException::withMessages([
                'tenant_visible_comment' => __('admin.billing_review.errors.rejection_comment_required'),
            ]);
        }

        return DB::transaction(function () use ($reading, $tenantVisibleComment, $actor): MeterReading {
            $before = [
                'validation_status' => $reading->validation_status?->value,
                'status' => $reading->status?->value,
            ];

            $reading->update([
                'validation_status' => MeterReadingValidationStatus::REJECTED,
                'status' => MeterReadingStatus::REJECTED,
                'rejected_by_user_id' => $actor?->id,
                'rejected_at' => now(),
                'rejection_reason' => $tenantVisibleComment,
                'approved_by_user_id' => null,
                'approved_at' => null,
                'notes' => $this->mergeNotes($reading->notes, $tenantVisibleComment),
            ]);

            $freshReading = $reading->fresh(['invoice', 'submittedBy:id,name,email,role']);
            $freshReading->recordVersion('rejected', $actor, $tenantVisibleComment);
            $this->markInvoiceRejected($freshReading, $tenantVisibleComment, $actor);

            $this->auditLogger->record(
                AuditLogAction::REJECTED,
                $freshReading,
                [
                    'context' => ['mutation' => 'billing_review.reading.rejected'],
                    'tenant_visible_comment' => $tenantVisibleComment,
                    'before' => $before,
                    'after' => [
                        'validation_status' => MeterReadingValidationStatus::REJECTED->value,
                        'status' => MeterReadingStatus::REJECTED->value,
                    ],
                ],
                $actor?->id,
                'Meter reading rejected from billing review',
            );

            if ($freshReading->submittedBy?->isTenant()) {
                $freshReading->submittedBy->notify(new ReadingRejectedNotification($freshReading, $tenantVisibleComment));
            }

            return $freshReading;
        });
    }

    private function markInvoiceRejected(MeterReading $reading, string $comment, ?User $actor): void
    {
        $invoice = $reading->invoice;

        if ($invoice === null || $invoice->automation_level !== 'reading_request') {
            return;
        }

        $metadata = is_array($invoice->approval_metadata) ? $invoice->approval_metadata : [];

        $invoice->forceFill([
            'approval_status' => 'readings_rejected',
            'approval_metadata' => [
                ...$metadata,
                'workflow' => $metadata['workflow'] ?? 'meter_reading_request',
                'request_status' => 'readings_rejected',
                'last_rejected_meter_reading_id' => (int) $reading->id,
                'last_rejection_comment' => $comment,
                'last_rejected_by_user_id' => $actor?->id,
                'last_rejected_at' => now()->toISOString(),
            ],
        ])->save();
    }

    private function mergeNotes(?string ...$notes): string
    {
        return collect($notes)
            ->filter(fn (?string $note): bool => filled($note))
            ->implode("\n");
    }

    private function authorize(MeterReading $reading, ?User $actor): void
    {
        if ($actor instanceof User && ! $actor->isTenant() && ($actor->isSuperadmin() || $actor->organization_id === $reading->organization_id)) {
            return;
        }

        throw new AuthorizationException;
    }
}
