<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Events\MeterReadingSubmitted;
use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class MeterReadingService
{
    public function __construct(
        private readonly DashboardCacheService $dashboardCacheService,
    ) {}

    public function create(
        Meter $meter,
        string|int|float $readingValue,
        string $readingDate,
        ?User $submittedBy,
        MeterReadingValidationStatus $validationStatus,
        MeterReadingSubmissionMethod $submissionMethod,
        ?string $notes = null,
    ): MeterReading {
        return DB::transaction(function () use (
            $meter,
            $readingValue,
            $readingDate,
            $submittedBy,
            $validationStatus,
            $submissionMethod,
            $notes,
        ): MeterReading {
            $reading = MeterReading::query()->create([
                'organization_id' => $meter->organization_id,
                'property_id' => $meter->property_id,
                'meter_id' => $meter->id,
                'submitted_by_user_id' => $submittedBy?->id,
                'reading_value' => $readingValue,
                'reading_date' => $readingDate,
                'validation_status' => $validationStatus,
                'submission_method' => $submissionMethod,
                'notes' => $notes,
            ]);

            DB::afterCommit(function () use ($reading, $submittedBy): void {
                $this->dashboardCacheService->touchOrganization($reading->organization_id);

                event(new MeterReadingSubmitted(
                    organizationId: $reading->organization_id,
                    meterReadingId: $reading->id,
                    meterId: $reading->meter_id,
                    propertyId: $reading->property_id,
                    tenantUserId: $submittedBy?->id,
                ));
            });

            return $reading;
        });
    }
}
