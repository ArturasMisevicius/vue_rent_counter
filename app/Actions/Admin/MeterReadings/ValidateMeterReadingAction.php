<?php

namespace App\Actions\Admin\MeterReadings;

use App\Models\MeterReading;
use App\Support\Admin\ReadingValidation\ValidateReadingValue;

class ValidateMeterReadingAction
{
    public function __construct(
        private readonly ValidateReadingValue $validateReadingValue,
    ) {}

    public function handle(MeterReading $meterReading): MeterReading
    {
        $validation = $this->validateReadingValue->handle(
            $meterReading->meter,
            $meterReading->reading_value,
            $meterReading->reading_date->toDateString(),
            $meterReading->id,
        );

        $meterReading->update([
            'validation_status' => $validation->status,
            'notes' => $validation->fails()
                ? collect($validation->messages)->flatten()->implode("\n")
                : $validation->notesAsText(),
        ]);

        return $meterReading->fresh();
    }
}
