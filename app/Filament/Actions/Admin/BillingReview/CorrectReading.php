<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Enums\AuditLogAction;
use App\Enums\MeterReadingStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
use App\Models\User;
use App\Notifications\Billing\ReadingApprovedNotification;
use App\Services\Billing\UniversalBillingCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CorrectReading
{
    public function __construct(
        private UniversalBillingCalculator $calculator,
        private AuditLogger $auditLogger,
        private RecalculateInvoice $recalculateInvoice,
    ) {}

    /**
     * @param  array{reading_value?: string|int|float, reading_date?: string|null, reason?: string|null, confirm_negative_consumption?: bool}  $data
     */
    public function handle(MeterReading $reading, array $data, ?User $actor = null): MeterReading
    {
        $actor ??= auth()->user();
        $this->authorize($reading, $actor);
        $reason = trim((string) ($data['reason'] ?? ''));

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('admin.billing_review.errors.correction_reason_required'),
            ]);
        }

        $value = $data['reading_value'] ?? $reading->reading_value;
        $date = filled($data['reading_date'] ?? null)
            ? (string) $data['reading_date']
            : $reading->reading_date?->toDateString();

        if ($date === null) {
            throw ValidationException::withMessages([
                'reading_date' => __('validation.required', ['attribute' => __('admin.meter_readings.columns.reading_date')]),
            ]);
        }

        $this->guardNegativeConsumption($reading, $value, $date, (bool) ($data['confirm_negative_consumption'] ?? false));

        $correctedReading = DB::transaction(function () use ($reading, $actor, $reason, $value, $date): MeterReading {
            $previousReading = $this->previousReading($reading, $date);
            $before = [
                'reading_value' => $reading->reading_value,
                'reading_date' => $reading->reading_date?->toDateString(),
                'validation_status' => $reading->validation_status?->value,
                'status' => $reading->status?->value,
            ];

            $reading->update([
                'reading_value' => $value,
                'reading_date' => $date,
                'previous_value' => $previousReading?->current_value ?? $previousReading?->reading_value,
                'current_value' => $value,
                'consumption' => $previousReading instanceof MeterReading
                    ? $this->calculator->quantity($this->calculator->subtract($value, $previousReading->current_value ?? $previousReading->reading_value, 3))
                    : null,
                'validation_status' => MeterReadingValidationStatus::VALID,
                'status' => MeterReadingStatus::CORRECTED,
                'corrected_by_user_id' => $actor?->id,
                'correction_reason' => $reason,
                'approved_by_user_id' => $actor?->id,
                'approved_at' => now(),
                'rejected_by_user_id' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'voided_at' => null,
                'notes' => $this->mergeNotes($reading->notes, $reason),
            ]);

            MeterReadingAudit::query()->create([
                'meter_reading_id' => $reading->id,
                'changed_by_user_id' => $actor?->id,
                'old_value' => $before['reading_value'],
                'new_value' => $value,
                'change_reason' => $reason,
            ]);

            $freshReading = $reading->fresh(['invoice', 'submittedBy:id,name,email,role']);
            $freshReading->recordVersion('corrected', $actor, $reason);

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $freshReading,
                [
                    'context' => ['mutation' => 'billing_review.reading.corrected'],
                    'reason' => $reason,
                    'before' => $before,
                    'after' => [
                        'reading_value' => $freshReading->reading_value,
                        'reading_date' => $freshReading->reading_date?->toDateString(),
                        'validation_status' => $freshReading->validation_status?->value,
                        'status' => $freshReading->status?->value,
                    ],
                ],
                $actor?->id,
                'Meter reading corrected from billing review',
            );

            if ($freshReading->submittedBy?->isTenant()) {
                $freshReading->submittedBy->notify(new ReadingApprovedNotification($freshReading));
            }

            return $freshReading;
        });

        if ($correctedReading->invoice instanceof Invoice) {
            $this->recalculateInvoice->handle($correctedReading->invoice, $actor);
        }

        return $correctedReading->fresh(['invoice', 'submittedBy:id,name,email,role']);
    }

    private function guardNegativeConsumption(MeterReading $reading, string|int|float $value, string $date, bool $confirmed): void
    {
        $previous = $this->previousReading($reading, $date);

        if (! $previous instanceof MeterReading) {
            return;
        }

        $consumption = $this->calculator->subtract($value, $previous->reading_value, 3);

        if ($this->calculator->compare($consumption, '0', 3) >= 0 || $confirmed) {
            return;
        }

        throw ValidationException::withMessages([
            'reading_value' => __('admin.billing_review.errors.negative_consumption_confirmation_required'),
        ]);
    }

    private function previousReading(MeterReading $reading, string $date): ?MeterReading
    {
        return MeterReading::query()
            ->select(['id', 'organization_id', 'property_id', 'meter_id', 'reading_value', 'current_value', 'reading_date', 'validation_status', 'status'])
            ->forOrganization($reading->organization_id)
            ->forMeter($reading->meter_id)
            ->where('id', '!=', $reading->id)
            ->where('validation_status', MeterReadingValidationStatus::VALID)
            ->beforeDate($date)
            ->latestFirst()
            ->first();
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
