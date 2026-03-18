<?php

namespace App\Filament\Actions\Admin\MeterReadings;

use App\Enums\MeterReadingSubmissionMethod;
use App\Filament\Support\Admin\ReadingValidation\ValidateReadingValue;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\MeterReadingService;
use Illuminate\Validation\ValidationException;

class CreateMeterReadingAction
{
    public function __construct(
        protected ValidateReadingValue $validateReadingValue,
        protected MeterReadingService $meterReadingService,
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

        return $this->meterReadingService->create(
            meter: $meter,
            readingValue: $readingValue,
            readingDate: $readingDate,
            submittedBy: $submittedBy,
            validationStatus: $validation->status,
            submissionMethod: $submissionMethod,
            notes: $this->mergeNotes($notes, $validation->notesAsText()),
        );
    }

    private function mergeNotes(?string ...$notes): ?string
    {
        $compiledNotes = array_values(array_filter($notes, fn (?string $note): bool => filled($note)));

        if ($compiledNotes === []) {
            return null;
        }

        return implode("\n", $compiledNotes);
    }
}
