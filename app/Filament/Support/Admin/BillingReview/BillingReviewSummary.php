<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingReview;

final readonly class BillingReviewSummary
{
    public function __construct(
        public int $totalInvoices,
        public int $waitingForReadings,
        public int $submittedReadings,
        public int $waitingConfirmation,
        public int $readyForReview,
        public int $configurationErrors,
        public int $approved,
        public int $sent,
        public int $overdue,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'total_invoices' => $this->totalInvoices,
            'waiting_for_readings' => $this->waitingForReadings,
            'submitted_readings' => $this->submittedReadings,
            'waiting_confirmation' => $this->waitingConfirmation,
            'ready_for_review' => $this->readyForReview,
            'configuration_errors' => $this->configurationErrors,
            'approved' => $this->approved,
            'sent' => $this->sent,
            'overdue' => $this->overdue,
        ];
    }
}
