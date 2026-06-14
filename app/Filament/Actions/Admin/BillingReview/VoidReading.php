<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Enums\AuditLogAction;
use App\Enums\MeterReadingValidationStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class VoidReading
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    public function handle(MeterReading $reading, string $reason, ?User $actor = null): MeterReading
    {
        $actor ??= auth()->user();
        $this->authorize($reading, $actor);

        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => __('admin.billing_review.errors.void_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($reading, $reason, $actor): MeterReading {
            $before = $reading->validation_status?->value;

            $reading->update([
                'validation_status' => MeterReadingValidationStatus::VOID,
                'notes' => collect([$reading->notes, $reason])->filter()->implode("\n"),
            ]);

            $freshReading = $reading->fresh();

            $this->auditLogger->record(
                AuditLogAction::DELETED,
                $freshReading,
                [
                    'context' => ['mutation' => 'billing_review.reading.voided'],
                    'reason' => $reason,
                    'before' => ['validation_status' => $before],
                    'after' => ['validation_status' => MeterReadingValidationStatus::VOID->value],
                ],
                $actor?->id,
                'Meter reading voided from billing review',
            );

            return $freshReading;
        });
    }

    private function authorize(MeterReading $reading, ?User $actor): void
    {
        if ($actor instanceof User && ! $actor->isTenant() && ($actor->isSuperadmin() || $actor->organization_id === $reading->organization_id)) {
            return;
        }

        throw new AuthorizationException;
    }
}
