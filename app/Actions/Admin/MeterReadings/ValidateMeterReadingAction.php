<?php

namespace App\Actions\Admin\MeterReadings;

use App\Models\MeterReading;
use App\Support\Admin\ReadingValidation\ReadingValidationResult;
use App\Support\Admin\ReadingValidation\ValidateReadingValue;
use Illuminate\Validation\ValidationException;

class ValidateMeterReadingAction
{
    public function __construct(
        private readonly ValidateReadingValue $validateReadingValue,
    ) {}

    public function handle(MeterReading $reading): MeterReading
    {
        $validation = $this->validateReadingValue->handle(
            $reading->meter,
            $reading->reading_value,
            $reading->reading_date->toDateString(),
            $reading,
        );

        if ($validation->fails()) {
            throw ValidationException::withMessages($validation->messages);
        }

        $reading->update([
            'validation_status' => $validation->status,
            'notes' => $this->mergeNotes($reading->notes, $validation),
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
