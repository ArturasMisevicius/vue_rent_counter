<?php

namespace App\Actions\Admin\MeterReadings;

use App\Enums\MeterReadingSubmissionMethod;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use App\Support\Admin\ReadingValidation\ReadingValidationResult;
use App\Support\Admin\ReadingValidation\ValidateReadingValue;
use Illuminate\Validation\ValidationException;

class CreateMeterReadingAction
{
    public function __construct(
        protected ValidateReadingValue $validateReadingValue,
    ) {}

    public function handle(
        Meter $meter,
        string|int|float $readingValue,
        string $readingDate,
        ?User $submittedBy,
        MeterReadingSubmissionMethod $submissionMethod,
        ?string $notes = null,
    ): MeterReading {
        $validation = $this->validateReadingValue->handle($meter, $readingValue, $readingDate);

        if ($validation->fails()) {
            throw ValidationException::withMessages($validation->messages);
        }

        return MeterReading::query()->create([
            'organization_id' => $meter->organization_id,
            'property_id' => $meter->property_id,
            'meter_id' => $meter->id,
            'submitted_by_user_id' => $submittedBy?->id,
            'reading_value' => $readingValue,
            'reading_date' => $readingDate,
            'validation_status' => $validation->status,
            'submission_method' => $submissionMethod,
            'notes' => $this->mergeNotes($notes, $validation),
        ]);
    }

    private function mergeNotes(?string $notes, ReadingValidationResult $validation): ?string
    {
        $segments = array_values(array_filter([
            $notes,
            ...$validation->notes,
        ]));

        return $segments !== [] ? implode("\n", $segments) : null;
    }
}
