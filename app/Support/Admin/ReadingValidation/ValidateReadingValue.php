<?php

namespace App\Support\Admin\ReadingValidation;

use App\Enums\MeterReadingValidationStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use Carbon\Carbon;

class ValidateReadingValue
{
    public function handle(Meter $meter, string|int|float $readingValue, string $readingDate): ReadingValidationResult
    {
        $messages = [];
        $normalizedDate = Carbon::parse($readingDate)->toDateString();
        $normalizedValue = (float) $readingValue;

        if ($normalizedDate > now()->toDateString()) {
            $messages['reading_date'][] = __('validation.before_or_equal', [
                'attribute' => 'reading date',
                'date' => now()->toDateString(),
            ]);
        }

        $previousReading = MeterReading::query()
            ->where('meter_id', $meter->id)
            ->whereDate('reading_date', '<=', $normalizedDate)
            ->whereIn('validation_status', [
                MeterReadingValidationStatus::VALID->value,
                MeterReadingValidationStatus::FLAGGED->value,
            ])
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->first();

        if ($previousReading && $normalizedValue < (float) $previousReading->reading_value) {
            $messages['reading_value'][] = 'The reading value must not be lower than the previous reading.';
        }

        return new ReadingValidationResult(
            messages: $messages,
            status: empty($messages) ? MeterReadingValidationStatus::VALID : MeterReadingValidationStatus::REJECTED,
            previousReading: $previousReading,
        );
    }
}
