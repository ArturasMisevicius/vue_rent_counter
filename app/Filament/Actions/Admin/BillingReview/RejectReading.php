<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Enums\AuditLogAction;
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
            $before = $reading->validation_status?->value;

            $reading->update([
                'validation_status' => MeterReadingValidationStatus::REJECTED,
                'notes' => $this->mergeNotes($reading->notes, $tenantVisibleComment),
            ]);

            $freshReading = $reading->fresh(['submittedBy:id,name,email,role']);

            $this->auditLogger->record(
                AuditLogAction::REJECTED,
                $freshReading,
                [
                    'context' => ['mutation' => 'billing_review.reading.rejected'],
                    'tenant_visible_comment' => $tenantVisibleComment,
                    'before' => ['validation_status' => $before],
                    'after' => ['validation_status' => MeterReadingValidationStatus::REJECTED->value],
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
