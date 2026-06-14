<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingReview;

final readonly class BillingReadingReviewData
{
    /**
     * @param  array<int, string>  $blockingErrors
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public int $meterId,
        public string $meterName,
        public ?string $meterIdentifier,
        public ?string $meterUnit,
        public ?int $readingId,
        public ?string $readingValue,
        public ?string $readingDate,
        public ?string $previousReadingValue,
        public ?string $previousReadingDate,
        public ?string $consumption,
        public string $status,
        public string $statusLabel,
        public ?string $submittedBy,
        public ?string $submittedAt,
        public bool $missing,
        public array $blockingErrors = [],
        public array $warnings = [],
    ) {}

    public function isApproved(): bool
    {
        return $this->status === 'valid';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'meter_id' => $this->meterId,
            'meter_name' => $this->meterName,
            'meter_identifier' => $this->meterIdentifier,
            'meter_unit' => $this->meterUnit,
            'reading_id' => $this->readingId,
            'reading_value' => $this->readingValue,
            'reading_date' => $this->readingDate,
            'previous_reading_value' => $this->previousReadingValue,
            'previous_reading_date' => $this->previousReadingDate,
            'consumption' => $this->consumption,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'submitted_by' => $this->submittedBy,
            'submitted_at' => $this->submittedAt,
            'missing' => $this->missing,
            'blocking_errors' => $this->blockingErrors,
            'warnings' => $this->warnings,
        ];
    }
}
