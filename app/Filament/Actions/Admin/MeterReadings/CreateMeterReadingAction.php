<?php

namespace App\Filament\Actions\Admin\MeterReadings;

use App\Enums\MeterReadingSubmissionMethod;
use App\Filament\Support\Admin\ReadingValidation\ValidateReadingValue;
use App\Http\Requests\Admin\MeterReadings\StoreMeterReadingRequest;
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
        $validated = $this->validatePayload(
            readingValue: $readingValue,
            readingDate: $readingDate,
            submissionMethod: $submissionMethod,
            notes: $notes,
            submittedBy: $submittedBy,
        );
        $resolvedSubmissionMethod = $validated['submission_method'] instanceof MeterReadingSubmissionMethod
            ? $validated['submission_method']
            : MeterReadingSubmissionMethod::from((string) $validated['submission_method']);
        $validation = $this->validateReadingValue->handle(
            $meter,
            $validated['reading_value'],
            $validated['reading_date'],
        );

        if ($validation->fails()) {
            throw ValidationException::withMessages($validation->messages);
        }

        return $this->meterReadingService->create(
            meter: $meter,
            readingValue: $validated['reading_value'],
            readingDate: $validated['reading_date'],
            submittedBy: $submittedBy,
            validationStatus: $validation->status,
            submissionMethod: $resolvedSubmissionMethod,
            notes: $this->mergeNotes($validated['notes'], $validation->notesAsText()),
        );
    }

    /**
     * @return array{
     *     reading_value: string|int|float,
     *     reading_date: string,
     *     submission_method: MeterReadingSubmissionMethod|string,
     *     notes: string|null
     * }
     */
    private function validatePayload(
        string|int|float $readingValue,
        string $readingDate,
        MeterReadingSubmissionMethod $submissionMethod,
        ?string $notes,
        ?User $submittedBy,
    ): array {
        /** @var StoreMeterReadingRequest $request */
        $request = new StoreMeterReadingRequest;

        return $request->validatePayload([
            'reading_value' => $readingValue,
            'reading_date' => $readingDate,
            'submission_method' => $submissionMethod,
            'notes' => $notes,
        ], $submittedBy ?? auth()->user());
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
