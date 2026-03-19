<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;

/**
 * Invoice Repository Interface
 *
 * Defines invoice-specific repository operations for managing
 * invoices, billing periods, and invoice-related queries.
 *
 * @extends RepositoryInterface<Invoice>
 */
interface InvoiceRepositoryInterface extends RepositoryInterface
{
    /**
     * Find invoices by status.
     *
     * @return Collection<int, Invoice>
     */
    public function findByStatus(InvoiceStatus $status): Collection;

    /**
     * Find invoices by date range.
     *
     * @return Collection<int, Invoice>
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Find invoices by tenant (renter).
     *
     * @return Collection<int, Invoice>
     */
    public function findByTenant(int $tenantId): Collection;

    /**
     * Find draft invoices.
     *
     * @return Collection<int, Invoice>
     */
    public function findDrafts(): Collection;

    /**
     * Find finalized invoices.
     *
     * @return Collection<int, Invoice>
     */
    public function findFinalized(): Collection;

    /**
     * Find paid invoices.
     *
     * @return Collection<int, Invoice>
     */
    public function findPaid(): Collection;

    /**
     * Find overdue invoices.
     *
     * @return Collection<int, Invoice>
     */
    public function findOverdue(): Collection;

    /**
     * Find invoices for billing period.
     *
     * @return Collection<int, Invoice>
     */
    public function findForPeriod(string $startDate, string $endDate): Collection;

    /**
     * Find invoices by invoice number.
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice;

    /**
     * Find invoices due within days.
     *
     * @return Collection<int, Invoice>
     */
    public function findDueWithinDays(int $days): Collection;

    /**
     * Find invoices by amount range.
     *
     * @return Collection<int, Invoice>
     */
    public function findByAmountRange(float $minAmount, float $maxAmount): Collection;

    /**
     * Count invoices by status.
     */
    public function countByStatus(InvoiceStatus $status): int;

    /**
     * Get total amount by status.
     */
    public function getTotalAmountByStatus(InvoiceStatus $status): float;

    /**
     * Get invoice statistics.
     *
     * @return array<string, mixed>
     */
    public function getInvoiceStats(): array;

    /**
     * Find invoices with items.
     *
     * @return Collection<int, Invoice>
     */
    public function findWithItems(): Collection;

    /**
     * Find invoices by property.
     *
     * @return Collection<int, Invoice>
     */
    public function findByProperty(int $propertyId): Collection;

    /**
     * Finalize invoice.
     */
    public function finalizeInvoice(int $invoiceId): Invoice;

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(int $invoiceId, ?float $paidAmount = null, ?string $paymentReference = null): Invoice;

    /**
     * Find invoices created in date range.
     *
     * @return Collection<int, Invoice>
     */
    public function findCreatedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Find invoices finalized in date range.
     *
     * @return Collection<int, Invoice>
     */
    public function findFinalizedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Find invoices paid in date range.
     *
     * @return Collection<int, Invoice>
     */
    public function findPaidBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Get monthly invoice summary.
     *
     * @return array<string, mixed>
     */
    public function getMonthlyInvoiceSummary(int $year, int $month): array;

    /**
     * Find invoices requiring overdue notification.
     *
     * @return Collection<int, Invoice>
     */
    public function findRequiringOverdueNotification(): Collection;

    /**
     * Mark overdue notification sent.
     */
    public function markOverdueNotificationSent(int $invoiceId): Invoice;
}
