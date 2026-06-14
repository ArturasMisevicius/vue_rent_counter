<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingReview;

final readonly class BillingInvoiceReviewData
{
    /**
     * @param  array<int, string>  $blockingErrors
     * @param  array<int, string>  $warnings
     * @param  array<int, array<string, mixed>>  $submittedReadings
     * @param  array<int, array<string, mixed>>  $missingReadings
     * @param  array<int, array<string, mixed>>  $services
     * @param  array<int, array<string, mixed>>  $extraCharges
     * @param  array<int, array<string, mixed>>  $calculationPreview
     * @param  array<int, array<string, mixed>>  $history
     * @param  array<int, array<string, mixed>>  $invoiceItemPayload
     */
    public function __construct(
        public int $invoiceId,
        public string $invoiceNumber,
        public string $tenantName,
        public ?string $tenantEmail,
        public string $propertyName,
        public ?string $buildingName,
        public string $billingPeriod,
        public string $invoiceStatus,
        public string $invoiceStatusLabel,
        public ?string $approvalStatus,
        public int $requiredReadingsCount,
        public int $approvedReadingsCount,
        public int $submittedReadingsCount,
        public string $readingsProgress,
        public array $blockingErrors,
        public array $warnings,
        public string $previewTotal,
        public string $currency,
        public ?string $lastActivityAt,
        public string $lastActivityLabel,
        public string $reviewUrl,
        public bool $canApprove,
        public bool $canSend,
        public bool $wasSent,
        public bool $isOverdue,
        public array $submittedReadings = [],
        public array $missingReadings = [],
        public array $services = [],
        public array $extraCharges = [],
        public array $calculationPreview = [],
        public array $history = [],
        public array $invoiceItemPayload = [],
    ) {}

    public function hasConfigurationErrors(): bool
    {
        return collect($this->blockingErrors)
            ->contains(fn (string $error): bool => str_contains($error, 'tariff') || str_contains($error, 'previous'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'invoice_id' => $this->invoiceId,
            'invoice_number' => $this->invoiceNumber,
            'tenant_name' => $this->tenantName,
            'tenant_email' => $this->tenantEmail,
            'property_name' => $this->propertyName,
            'building_name' => $this->buildingName,
            'billing_period' => $this->billingPeriod,
            'invoice_status' => $this->invoiceStatus,
            'invoice_status_label' => $this->invoiceStatusLabel,
            'approval_status' => $this->approvalStatus,
            'required_readings_count' => $this->requiredReadingsCount,
            'approved_readings_count' => $this->approvedReadingsCount,
            'submitted_readings_count' => $this->submittedReadingsCount,
            'readings_progress' => $this->readingsProgress,
            'blocking_errors' => $this->blockingErrors,
            'warnings' => $this->warnings,
            'preview_total' => $this->previewTotal,
            'currency' => $this->currency,
            'last_activity_at' => $this->lastActivityAt,
            'last_activity_label' => $this->lastActivityLabel,
            'review_url' => $this->reviewUrl,
            'can_approve' => $this->canApprove,
            'can_send' => $this->canSend,
            'was_sent' => $this->wasSent,
            'is_overdue' => $this->isOverdue,
            'submitted_readings' => $this->submittedReadings,
            'missing_readings' => $this->missingReadings,
            'services' => $this->services,
            'extra_charges' => $this->extraCharges,
            'calculation_preview' => $this->calculationPreview,
            'history' => $this->history,
            'invoice_item_payload' => $this->invoiceItemPayload,
        ];
    }
}
