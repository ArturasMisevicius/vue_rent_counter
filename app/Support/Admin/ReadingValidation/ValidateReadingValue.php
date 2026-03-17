<?php

namespace App\Support\Admin\ReadingValidation;

use App\Enums\MeterReadingValidationStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use Carbon\Carbon;

class ValidateReadingValue
{
    public function handle(
        Meter $meter,
        string|int|float $readingValue,
        string $readingDate,
        ?MeterReading $except = null,
    ): ReadingValidationResult {
        $messages = [];
        $notes = [];
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
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except->id))
            ->whereDate('reading_date', '<=', $normalizedDate)
            ->whereIn('validation_status', [
                MeterReadingValidationStatus::PENDING->value,
                MeterReadingValidationStatus::VALID->value,
                MeterReadingValidationStatus::FLAGGED->value,
            ])
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->first();

        if ($previousReading && $normalizedValue < (float) $previousReading->reading_value) {
            $messages['reading_value'][] = 'The reading value must not be lower than the previous reading.';
        }

        if ($previousReading !== null) {
            $daysSincePrevious = Carbon::parse($previousReading->reading_date)->diffInDays($normalizedDate);
            $previousValue = (float) $previousReading->reading_value;

            if ($daysSincePrevious > 60) {
                $notes[] = 'Detected a 60-day gap since the previous reading.';
            }

            if ($previousValue > 0 && $normalizedValue > ($previousValue * 1.5)) {
                $notes[] = 'Potential anomalous spike detected compared with the previous reading.';
            }
        }

        return new ReadingValidationResult(
            messages: $messages,
            status: $messages !== []
                ? MeterReadingValidationStatus::REJECTED
                : ($notes !== [] ? MeterReadingValidationStatus::FLAGGED : MeterReadingValidationStatus::VALID),
            previousReading: $previousReading,
            notes: $notes,
        );
    }
}
