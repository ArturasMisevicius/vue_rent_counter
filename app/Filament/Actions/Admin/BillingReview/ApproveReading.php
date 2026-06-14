<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Enums\AuditLogAction;
use App\Enums\MeterReadingValidationStatus;
use App\Filament\Support\Audit\AuditLogger;
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
    ) {}

    public function handle(MeterReading $reading, ?User $actor = null, bool $confirmNegativeConsumption = false): MeterReading
    {
        $actor ??= auth()->user();
        $this->authorize($reading, $actor);
        $this->guardNegativeConsumption($reading, $confirmNegativeConsumption);

        return DB::transaction(function () use ($reading, $actor): MeterReading {
            $before = $reading->validation_status?->value;

            $reading->update([
                'validation_status' => MeterReadingValidationStatus::VALID,
            ]);

            $freshReading = $reading->fresh(['submittedBy:id,name,email,role']);

            $this->auditLogger->record(
                AuditLogAction::APPROVED,
                $freshReading,
                [
                    'context' => ['mutation' => 'billing_review.reading.approved'],
                    'before' => ['validation_status' => $before],
                    'after' => ['validation_status' => MeterReadingValidationStatus::VALID->value],
                ],
                $actor?->id,
                'Meter reading approved from billing review',
            );

            if ($freshReading->submittedBy?->isTenant()) {
                $freshReading->submittedBy->notify(new ReadingApprovedNotification($freshReading));
            }

            return $freshReading;
        });
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
