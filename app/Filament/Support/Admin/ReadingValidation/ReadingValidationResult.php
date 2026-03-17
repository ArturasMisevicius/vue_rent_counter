<?php

namespace App\Filament\Support\Admin\ReadingValidation;

use App\Enums\MeterReadingValidationStatus;
use App\Models\MeterReading;
use Illuminate\Support\Arr;

class ReadingValidationResult
{
    /**
     * @param  array<string, list<string>>  $messages
     * @param  list<string>  $notes
     */
    public function __construct(
        public MeterReadingValidationStatus $status,
        public array $messages = [],
        public ?MeterReading $previousReading = null,
        public array $notes = [],
        public ?float $consumptionDelta = null,
        public ?float $averageMonthlyUsage = null,
        public bool $anomalous = false,
        public bool $gapDetected = false,
        public ?string $message = null,
    ) {}

    public function fails(): bool
    {
        return $this->isBlocking();
    }

    public function isBlocking(): bool
    {
        return $this->messages !== [];
    }

    public function isAnomalous(): bool
    {
        return $this->anomalous;
    }

    public function hasGapNote(): bool
    {
        return $this->gapDetected;
    }

    public function notesAsText(): ?string
    {
        if ($this->notes === []) {
            return null;
        }

        return implode("\n", $this->notes);
    }

    public static function fromValidation(
        MeterReadingValidationStatus $status,
        array $messages = [],
        ?MeterReading $previousReading = null,
        array $notes = [],
        ?float $consumptionDelta = null,
        ?float $averageMonthlyUsage = null,
        bool $anomalous = false,
        bool $gapDetected = false,
    ): self {
        return new self(
            status: $status,
            messages: $messages,
            previousReading: $previousReading,
            notes: $notes,
            consumptionDelta: $consumptionDelta,
            averageMonthlyUsage: $averageMonthlyUsage,
            anomalous: $anomalous,
            gapDetected: $gapDetected,
            message: Arr::first(Arr::flatten($messages)) ?? Arr::first($notes),
        );
    }
}
