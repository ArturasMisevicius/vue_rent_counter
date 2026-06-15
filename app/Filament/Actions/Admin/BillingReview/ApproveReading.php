<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Enums\AuditLogAction;
use App\Enums\MeterReadingStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\User;
use App\Notifications\Billing\ReadingApprovedNotification;
use App\Services\Billing\UniversalBillingCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ApproveReading
{
    public function __construct(
        private UniversalBillingCalculator $calculator,
        private AuditLogger $auditLogger,
        private RecalculateInvoice $recalculateInvoice,
    ) {}

    public function handle(MeterReading $reading, ?User $actor = null, bool $confirmNegativeConsumption = false): MeterReading
    {
        $actor ??= auth()->user();
        $this->authorize($reading, $actor);
        $this->guardNegativeConsumption($reading, $confirmNegativeConsumption);

        $approvedReading = DB::transaction(function () use ($reading, $actor): MeterReading {
            $previousReading = $this->previousReading($reading);
            $before = [
                'validation_status' => $reading->validation_status?->value,
                'status' => $reading->status?->value,
            ];

            $reading->update([
                'validation_status' => MeterReadingValidationStatus::VALID,
                'status' => MeterReadingStatus::APPROVED,
                'previous_value' => $previousReading?->current_value ?? $previousReading?->reading_value,
                'current_value' => $reading->reading_value,
                'consumption' => $previousReading instanceof MeterReading
                    ? $this->calculator->quantity($this->calculator->subtract($reading->reading_value, $previousReading->current_value ?? $previousReading->reading_value, 3))
                    : null,
                'approved_by_user_id' => $actor?->id,
                'approved_at' => now(),
                'rejected_by_user_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'voided_at' => null,
            ]);

            $freshReading = $reading->fresh(['invoice', 'submittedBy:id,name,email,role']);
            $freshReading->recordVersion('approved', $actor);

            $this->auditLogger->record(
                AuditLogAction::APPROVED,
                $freshReading,
                [
                    'context' => ['mutation' => 'billing_review.reading.approved'],
                    'before' => $before,
                    'after' => [
                        'validation_status' => MeterReadingValidationStatus::VALID->value,
                        'status' => MeterReadingStatus::APPROVED->value,
                    ],
                ],
                $actor?->id,
                'Meter reading approved from billing review',
            );

            if ($freshReading->submittedBy?->isTenant()) {
                $freshReading->submittedBy->notify(new ReadingApprovedNotification($freshReading));
            }

            return $freshReading;
        });

        if ($approvedReading->invoice instanceof Invoice) {
            $this->recalculateInvoice->handle($approvedReading->invoice, $actor);
        }

        return $approvedReading->fresh(['invoice', 'submittedBy:id,name,email,role']);
    }

    private function guardNegativeConsumption(MeterReading $reading, bool $confirmed): void
    {
        $previous = $this->previousReading($reading);

        if (! $previous instanceof MeterReading) {
            return;
        }

        $consumption = $this->calculator->subtract($reading->reading_value, $previous->reading_value, 3);

        if ($this->calculator->compare($consumption, '0', 3) >= 0 || $confirmed) {
            return;
        }

        throw ValidationException::withMessages([
            'reading_value' => __('admin.billing_review.errors.negative_consumption_confirmation_required'),
        ]);
    }

    private function previousReading(MeterReading $reading): ?MeterReading
    {
        return MeterReading::query()
            ->select(['id', 'organization_id', 'property_id', 'meter_id', 'reading_value', 'reading_date', 'validation_status'])
            ->forOrganization($reading->organization_id)
            ->forMeter($reading->meter_id)
            ->where('id', '!=', $reading->id)
            ->where('validation_status', MeterReadingValidationStatus::VALID)
            ->beforeDate($reading->reading_date)
            ->latestFirst()
            ->first();
    }

    private function authorize(MeterReading $reading, ?User $actor): void
    {
        if ($actor instanceof User && ! $actor->isTenant() && ($actor->isSuperadmin() || $actor->organization_id === $reading->organization_id)) {
            return;
        }

        throw new AuthorizationException;
    }
}
