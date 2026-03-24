<?php

namespace App\Filament\Support\Admin\ReadingValidation;

use App\Enums\MeterReadingValidationStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ValidateReadingValue
{
    public function validate(
        Meter $meter,
        string|int|float $readingValue,
        string $readingDate,
        ?int $ignoreReadingId = null,
    ): ReadingValidationResult {
        $messages = [];
        $notes = [];
        $normalizedDate = Carbon::parse($readingDate)->toDateString();
        $normalizedValue = (float) $readingValue;

        if ($normalizedValue <= 0) {
            $messages['reading_value'][] = __('validation.gt.numeric', [
                'attribute' => __('requests.attributes.reading_value'),
                'value' => 0,
            ]);
        }

        if ($normalizedDate > now()->toDateString()) {
            $messages['reading_date'][] = __('validation.before_or_equal', [
                'attribute' => 'reading date',
                'date' => now()->toDateString(),
            ]);
        }

        $previousReading = MeterReading::query()
            ->where('meter_id', $meter->id)
            ->whereDate('reading_date', '<=', $normalizedDate)
            ->whereIn('validation_status', MeterReadingValidationStatus::comparableValues())
            ->when(
                $ignoreReadingId !== null,
                fn ($query) => $query->whereKeyNot($ignoreReadingId),
            )
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->first();

        if ($previousReading && $normalizedValue < (float) $previousReading->reading_value) {
            $messages['reading_value'][] = 'The reading value must be higher than the previous reading.';
        }

        $consumptionDelta = null;
        $averageMonthlyUsage = null;
        $anomalous = false;
        $gapDetected = false;

        if ($messages === [] && $previousReading !== null) {
            $previousDate = Carbon::parse($previousReading->reading_date);
            $consumptionDelta = round($normalizedValue - (float) $previousReading->reading_value, 3);

            if ($previousDate->diffInDays($normalizedDate) >= 60) {
                $notes[] = 'Potential 60-day gap detected since the previous reading.';
                $gapDetected = true;
            }

            $averageMonthlyUsage = $this->averageMonthlyUsage($meter, $normalizedDate, $ignoreReadingId);

            if (
                $averageMonthlyUsage !== null
                && $consumptionDelta !== null
                && $consumptionDelta > round($averageMonthlyUsage * 3, 3)
            ) {
                $notes[] = 'Potential anomalous spike detected compared with the previous reading.';
                $anomalous = true;
            }
        }

        return ReadingValidationResult::fromValidation(
            status: match (true) {
                $messages !== [] => MeterReadingValidationStatus::REJECTED,
                $notes !== [] => MeterReadingValidationStatus::FLAGGED,
                default => MeterReadingValidationStatus::VALID,
            },
            messages: $messages,
            previousReading: $previousReading,
            notes: $notes,
            consumptionDelta: $consumptionDelta,
            averageMonthlyUsage: $averageMonthlyUsage,
            anomalous: $anomalous,
            gapDetected: $gapDetected,
        );
    }

    public function handle(
        Meter $meter,
        string|int|float $readingValue,
        string $readingDate,
        ?int $ignoreReadingId = null,
    ): ReadingValidationResult {
        return $this->validate($meter, $readingValue, $readingDate, $ignoreReadingId);
    }

    private function averageMonthlyUsage(Meter $meter, string $normalizedDate, ?int $ignoreReadingId = null): ?float
    {
        /** @var Collection<int, MeterReading> $historicalReadings */
        $historicalReadings = MeterReading::query()
            ->select(['id', 'meter_id', 'reading_value', 'reading_date', 'validation_status'])
            ->where('meter_id', $meter->id)
            ->whereDate('reading_date', '<', $normalizedDate)
            ->whereIn('validation_status', MeterReadingValidationStatus::comparableValues())
            ->when(
                $ignoreReadingId !== null,
                fn ($query) => $query->whereKeyNot($ignoreReadingId),
            )
            ->orderBy('reading_date')
            ->orderBy('id')
            ->get();

        if ($historicalReadings->count() < 2) {
            return null;
        }

        $monthlyUsageSamples = [];
        $previousReading = null;

        foreach ($historicalReadings as $historicalReading) {
            if ($previousReading === null) {
                $previousReading = $historicalReading;

                continue;
            }

            $daysBetween = Carbon::parse($previousReading->reading_date)
                ->diffInDays(Carbon::parse($historicalReading->reading_date));
            $usageDelta = (float) $historicalReading->reading_value - (float) $previousReading->reading_value;

            if ($daysBetween > 0 && $usageDelta >= 0) {
                $monthlyUsageSamples[] = $usageDelta / ($daysBetween / 30);
            }

            $previousReading = $historicalReading;
        }

        if ($monthlyUsageSamples === []) {
            return null;
        }

        return round(array_sum($monthlyUsageSamples) / count($monthlyUsageSamples), 3);
    }
}
