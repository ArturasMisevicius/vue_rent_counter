<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Support\Collection;

/**
 * Result of automated billing cycle execution.
 * 
 * Immutable value object containing billing cycle results
 * with analysis capabilities and metadata tracking.
 * 
 * @package App\ValueObjects
 */
final readonly class AutomatedBillingResult
{
    public function __construct(
        private int $processedTenants,
        private int $generatedInvoices,
        private float $totalAmount,
        private array $errors,
        private array $warnings,
        private array $readingCollectionResults,
        private array $sharedServiceResults = [],
        private array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            processedTenants: $data['processed_tenants'] ?? 0,
            generatedInvoices: $data['generated_invoices'] ?? 0,
            totalAmount: $data['total_amount'] ?? 0.0,
            errors: $data['errors'] ?? [],
            warnings: $data['warnings'] ?? [],
            readingCollectionResults: $data['reading_collection_results'] ?? [],
            sharedServiceResults: $data['shared_service_results'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    public function getProcessedTenants(): int
    {
        return $this->processedTenants;
    }

    public function getGeneratedInvoices(): int
    {
        return $this->generatedInvoices;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getReadingCollectionResults(): array
    {
        return $this->readingCollectionResults;
    }

    public function getSharedServiceResults(): array
    {
        return $this->sharedServiceResults;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function isSuccessful(): bool
    {
        return !$this->hasErrors();
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    public function getWarningCount(): int
    {
        return count($this->warnings);
    }

    public function getSuccessRate(): float
    {
        if ($this->processedTenants === 0) {
            return 0.0;
        }
        
        $successfulTenants = $this->processedTenants - count($this->errors);
        return ($successfulTenants / $this->processedTenants) * 100.0;
    }

    public function getSummary(): array
    {
        return [
            'processed_tenants' => $this->processedTenants,
            'generated_invoices' => $this->generatedInvoices,
            'total_amount' => $this->totalAmount,
            'success_rate' => $this->getSuccessRate(),
            'error_count' => $this->getErrorCount(),
            'warning_count' => $this->getWarningCount(),
            'has_errors' => $this->hasErrors(),
            'has_warnings' => $this->hasWarnings(),
            'is_successful' => $this->isSuccessful(),
        ];
    }

    public function toArray(): array
    {
        return [
            'processed_tenants' => $this->processedTenants,
            'generated_invoices' => $this->generatedInvoices,
            'total_amount' => $this->totalAmount,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'reading_collection_results' => $this->readingCollectionResults,
            'shared_service_results' => $this->sharedServiceResults,
            'metadata' => $this->metadata,
            'has_errors' => $this->hasErrors(),
            'has_warnings' => $this->hasWarnings(),
            'is_successful' => $this->isSuccessful(),
        ];
    }
}