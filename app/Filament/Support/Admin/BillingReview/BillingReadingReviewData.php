<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingReview;

final readonly class BillingReadingReviewData
{
    /**
     * @param  array<int, string>  $blockingErrors
     * @param  array<int, string>  $warnings
     * @param  array<int, string>  $issueLabels
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
        public bool $pendingReview,
        public bool $negativeConsumption,
        public bool $highConsumption,
        public bool $strangeReading,
        public array $blockingErrors = [],
        public array $warnings = [],
        public array $issueLabels = [],
    ) {}

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'corrected'], true);
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
            'pending_review' => $this->pendingReview,
            'negative_consumption' => $this->negativeConsumption,
            'high_consumption' => $this->highConsumption,
            'strange_reading' => $this->strangeReading,
            'blocking_errors' => $this->blockingErrors,
            'warnings' => $this->warnings,
            'issue_labels' => $this->issueLabels,
        ];
    }
}
