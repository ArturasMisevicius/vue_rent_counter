<?php

declare(strict_types=1);

namespace App\Repositories\Criteria;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Invoices By Status Criteria
 * 
 * Filters invoices by their status (draft, finalized, paid).
 * Supports single status or multiple statuses.
 */
class InvoicesByStatus implements CriteriaInterface
{
    /**
     * Create a new invoices by status criteria.
     * 
     * @param InvoiceStatus|array<InvoiceStatus> $statuses Status or array of statuses
     */
    public function __construct(
        private readonly InvoiceStatus|array $statuses
    ) {}

    /**
     * {@inheritDoc}
     */
    public function apply(Builder $query): Builder
    {
        if (is_array($this->statuses)) {
            return $query->whereIn('status', $this->statuses);
        }

        return $query->where('status', $this->statuses);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        if (is_array($this->statuses)) {
            $statusNames = array_map(fn($status) => $status->value, $this->statuses);
            return 'Filter invoices by statuses: ' . implode(', ', $statusNames);
        }

        return "Filter invoices by status: {$this->statuses->value}";
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        return [
            'statuses' => is_array($this->statuses) 
                ? array_map(fn($status) => $status->value, $this->statuses)
                : $this->statuses->value,
        ];
    }

    /**
     * Create criteria for draft invoices.
     * 
     * @return static
     */
    public static function drafts(): static
    {
        return new static(InvoiceStatus::DRAFT);
    }

    /**
     * Create criteria for finalized invoices.
     * 
     * @return static
     */
    public static function finalized(): static
    {
        return new static(InvoiceStatus::FINALIZED);
    }

    /**
     * Create criteria for paid invoices.
     * 
     * @return static
     */
    public static function paid(): static
    {
        return new static(InvoiceStatus::PAID);
    }

    /**
     * Create criteria for unpaid invoices (draft or finalized).
     * 
     * @return static
     */
    public static function unpaid(): static
    {
        return new static([InvoiceStatus::DRAFT, InvoiceStatus::FINALIZED]);
    }

    /**
     * Create criteria for processable invoices (draft or finalized).
     * 
     * @return static
     */
    public static function processable(): static
    {
        return new static([InvoiceStatus::DRAFT, InvoiceStatus::FINALIZED]);
    }
}