<?php

namespace App\Actions\Admin\MeterReadings;

use App\Enums\MeterReadingSubmissionMethod;
use App\Models\MeterReading;
use App\Support\Admin\ReadingValidation\ReadingValidationResult;
use App\Support\Admin\ReadingValidation\ValidateReadingValue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateMeterReadingAction
{
    public function __construct(
        private readonly ValidateReadingValue $validateReadingValue,
    ) {}

    public function handle(MeterReading $reading, array $data): MeterReading
    {
        $validated = Validator::make($data, [
            'reading_value' => ['required', 'numeric', 'min:0'],
            'reading_date' => ['required', 'date'],
            'submission_method' => ['required', Rule::enum(MeterReadingSubmissionMethod::class)],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $validation = $this->validateReadingValue->handle(
            $reading->meter,
            $validated['reading_value'],
            $validated['reading_date'],
            $reading,
        );

        if ($validation->fails()) {
            throw ValidationException::withMessages($validation->messages);
        }

        $reading->update([
            'reading_value' => $validated['reading_value'],
            'reading_date' => $validated['reading_date'],
            'validation_status' => $validation->status,
            'submission_method' => $validated['submission_method'],
            'notes' => $this->mergeNotes($validated['notes'] ?? null, $validation),
        ]);

        return $reading->fresh();
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
