<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingIntegrity;

use App\Enums\MeterReadingValidationStatus;
use App\Filament\Actions\Admin\BillingReview\VoidReading;
use App\Filament\Support\Admin\BillingIntegrity\BillingIntegrityActionGuard;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final readonly class VoidMeterReadingDuplicate
{
    public function __construct(
        private BillingIntegrityActionGuard $guard,
        private VoidReading $voidReading,
    ) {}

    /**
     * @param  array<int, int>  $readingIds
     */
    public function handle(MeterReading $keepReading, array $readingIds, string $reason, ?User $actor = null): MeterReading
    {
        $actor = $this->guard->ensureCanManage($actor ?? auth()->user(), (int) $keepReading->organization_id);
        $reason = $this->guard->ensureReason($reason);
        $ids = collect($readingIds)
            ->push($keepReading->id)
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $readings = MeterReading::query()
            ->select(['id', 'organization_id', 'property_id', 'meter_id', 'submitted_by_user_id', 'reading_value', 'reading_date', 'validation_status', 'submission_method', 'notes', 'created_at', 'updated_at'])
            ->forOrganization((int) $keepReading->organization_id)
            ->whereKey($ids)
            ->whereIn('validation_status', $this->activeStatuses())
            ->get();

        if ($readings->count() < 2 || ! $readings->contains('id', $keepReading->id)) {
            throw ValidationException::withMessages([
                'meter_reading' => __('admin.billing_cleanup.errors.duplicate_group_missing'),
            ]);
        }

        $this->ensureSameDuplicateGroup($keepReading, $readings);

        $readings
            ->reject(fn (MeterReading $reading): bool => (int) $reading->id === (int) $keepReading->id)
            ->each(fn (MeterReading $reading): mixed => $this->voidReading->handle($reading, $reason, $actor));

        return $keepReading->fresh();
    }

    /**
     * @return array<int, MeterReadingValidationStatus>
     */
    private function activeStatuses(): array
    {
        return [
            MeterReadingValidationStatus::PENDING,
            MeterReadingValidationStatus::VALID,
            MeterReadingValidationStatus::FLAGGED,
        ];
    }

    /**
     * @param  Collection<int, MeterReading>  $readings
     */
    private function ensureSameDuplicateGroup(MeterReading $keepReading, Collection $readings): void
    {
        $groups = $readings
            ->groupBy(fn (MeterReading $reading): string => implode(':', [
                $reading->organization_id,
                $reading->property_id,
                $reading->meter_id,
                $reading->reading_date?->toDateString(),
            ]));

        if ($groups->count() === 1) {
            return;
        }

        throw ValidationException::withMessages([
            'meter_reading' => __('admin.billing_cleanup.errors.duplicate_group_mismatch'),
        ]);
    }
}
