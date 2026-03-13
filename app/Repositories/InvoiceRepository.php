<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\InvoiceRepositoryInterface;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

/**
 * Invoice Repository Implementation
 * 
 * Provides invoice-specific data access operations with tenant awareness,
 * status-based filtering, and invoice management functionality.
 * 
 * @extends BaseRepository<Invoice>
 */
class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    /**
     * Create a new invoice repository instance.
     * 
     * @param Invoice $model
     */
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function findByStatus(InvoiceStatus $status): Collection
    {
        try {
            return $this->query->where('status', $status)->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByStatus', 'status' => $status->value]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection
    {
        try {
            return $this->query
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'findByDateRange',
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d')
            ]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByTenant(int $tenantId): Collection
    {
        try {
            return $this->query->forTenant($tenantId)->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByTenant', 'tenantId' => $tenantId]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findDrafts(): Collection
    {
        try {
            return $this->query->draft()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findDrafts']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findFinalized(): Collection
    {
        try {
            return $this->query->finalized()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findFinalized']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findPaid(): Collection
    {
        try {
            return $this->query->paid()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findPaid']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findOverdue(): Collection
    {
        try {
            return $this->query
                ->where('status', '!=', InvoiceStatus::PAID)
                ->where('due_date', '<', now())
                ->whereNotNull('due_date')
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findOverdue']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findForPeriod(string $startDate, string $endDate): Collection
    {
        try {
            return $this->query->forPeriod($startDate, $endDate)->get();
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'findForPeriod',
                'startDate' => $startDate,
                'endDate' => $endDate
            ]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        try {
            return $this->query->where('invoice_number', $invoiceNumber)->first();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByInvoiceNumber', 'invoiceNumber' => $invoiceNumber]);
            return null;
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findDueWithinDays(int $days): Collection
    {
        try {
            $dueDate = now()->addDays($days);
            return $this->query
                ->where('status', '!=', InvoiceStatus::PAID)
                ->where('due_date', '<=', $dueDate)
                ->where('due_date', '>=', now())
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findDueWithinDays', 'days' => $days]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByAmountRange(float $minAmount, float $maxAmount): Collection
    {
        try {
            return $this->query
                ->whereBetween('total_amount', [$minAmount, $maxAmount])
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'findByAmountRange',
                'minAmount' => $minAmount,
                'maxAmount' => $maxAmount
            ]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function countByStatus(InvoiceStatus $status): int
    {
        try {
            return $this->query->where('status', $status)->count();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'countByStatus', 'status' => $status->value]);
            return 0;
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalAmountByStatus(InvoiceStatus $status): float
    {
        try {
            return (float) $this->query
                ->where('status', $status)
                ->sum('total_amount');
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'getTotalAmountByStatus', 'status' => $status->value]);
            return 0.0;
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getInvoiceStats(): array
    {
        try {
            return [
                'total_invoices' => $this->count(),
                'draft_count' => $this->countByStatus(InvoiceStatus::DRAFT),
                'finalized_count' => $this->countByStatus(InvoiceStatus::FINALIZED),
                'paid_count' => $this->countByStatus(InvoiceStatus::PAID),
                'overdue_count' => $this->query->where('status', '!=', InvoiceStatus::PAID)
                    ->where('due_date', '<', now())->count(),
                'total_draft_amount' => $this->getTotalAmountByStatus(InvoiceStatus::DRAFT),
                'total_finalized_amount' => $this->getTotalAmountByStatus(InvoiceStatus::FINALIZED),
                'total_paid_amount' => $this->getTotalAmountByStatus(InvoiceStatus::PAID),
                'average_invoice_amount' => $this->query->avg('total_amount') ?? 0,
            ];
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'getInvoiceStats']);
            return [];
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findWithItems(): Collection
    {
        try {
            return $this->query
                ->whereHas('items')
                ->with('items')
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findWithItems']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByProperty(int $propertyId): Collection
    {
        try {
            return $this->query
                ->whereHas('property', function ($query) use ($propertyId) {
                    $query->where('id', $propertyId);
                })
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByProperty', 'propertyId' => $propertyId]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function finalizeInvoice(int $invoiceId): Invoice
    {
        try {
            return $this->transaction(function () use ($invoiceId) {
                $invoice = $this->findOrFail($invoiceId);
                
                if ($invoice->isFinalized()) {
                    throw new \App\Exceptions\RepositoryException('Invoice is already finalized');
                }
                
                $invoice->finalize();
                
                $this->logOperation('finalizeInvoice', ['invoiceId' => $invoiceId]);
                return $invoice;
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'finalizeInvoice', 'invoiceId' => $invoiceId]);
            throw new \App\Exceptions\RepositoryException("Failed to finalize invoice with ID: {$invoiceId}", 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function markAsPaid(int $invoiceId, ?float $paidAmount = null, ?string $paymentReference = null): Invoice
    {
        try {
            return $this->transaction(function () use ($invoiceId, $paidAmount, $paymentReference) {
                $invoice = $this->findOrFail($invoiceId);
                
                if ($invoice->isPaid()) {
                    throw new \App\Exceptions\RepositoryException('Invoice is already paid');
                }
                
                $invoice->update([
                    'status' => InvoiceStatus::PAID,
                    'paid_at' => now(),
                    'paid_amount' => $paidAmount ?? $invoice->total_amount,
                    'payment_reference' => $paymentReference,
                ]);
                
                $this->logOperation('markAsPaid', [
                    'invoiceId' => $invoiceId,
                    'paidAmount' => $paidAmount,
                    'paymentReference' => $paymentReference
                ]);
                
                return $invoice;
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'markAsPaid',
                'invoiceId' => $invoiceId,
                'paidAmount' => $paidAmount
            ]);
            throw new \App\Exceptions\RepositoryException("Failed to mark invoice as paid with ID: {$invoiceId}", 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findCreatedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection
    {
        try {
            return $this->query
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'findCreatedBetween',
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d')
            ]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findFinalizedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection
    {
        try {
            return $this->query
                ->whereBetween('finalized_at', [$startDate, $endDate])
                ->whereNotNull('finalized_at')
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'findFinalizedBetween',
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d')
            ]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findPaidBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection
    {
        try {
            return $this->query
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->whereNotNull('paid_at')
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'findPaidBetween',
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d')
            ]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getMonthlyInvoiceSummary(int $year, int $month): array
    {
        try {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            
            $invoices = $this->query
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            return [
                'month' => $month,
                'year' => $year,
                'total_invoices' => $invoices->count(),
                'draft_count' => $invoices->where('status', InvoiceStatus::DRAFT)->count(),
                'finalized_count' => $invoices->where('status', InvoiceStatus::FINALIZED)->count(),
                'paid_count' => $invoices->where('status', InvoiceStatus::PAID)->count(),
                'total_amount' => $invoices->sum('total_amount'),
                'paid_amount' => $invoices->where('status', InvoiceStatus::PAID)->sum('total_amount'),
                'outstanding_amount' => $invoices->whereIn('status', [InvoiceStatus::DRAFT, InvoiceStatus::FINALIZED])->sum('total_amount'),
            ];
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'getMonthlyInvoiceSummary', 'year' => $year, 'month' => $month]);
            return [];
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findRequiringOverdueNotification(): Collection
    {
        try {
            return $this->query
                ->where('status', InvoiceStatus::FINALIZED)
                ->where('due_date', '<', now())
                ->whereNull('overdue_notified_at')
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findRequiringOverdueNotification']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function markOverdueNotificationSent(int $invoiceId): Invoice
    {
        try {
            return $this->transaction(function () use ($invoiceId) {
                $invoice = $this->findOrFail($invoiceId);
                $invoice->update(['overdue_notified_at' => now()]);
                
                $this->logOperation('markOverdueNotificationSent', ['invoiceId' => $invoiceId]);
                return $invoice;
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'markOverdueNotificationSent', 'invoiceId' => $invoiceId]);
            throw new \App\Exceptions\RepositoryException("Failed to mark overdue notification sent for invoice ID: {$invoiceId}", 0, $e);
        }
    }

    /**
     * Get revenue statistics for a date range.
     * 
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return array<string, mixed>
     */
    public function getRevenueStats(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        try {
            $invoices = $this->query
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            return [
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $endDate->format('Y-m-d'),
                'total_invoiced' => $invoices->sum('total_amount'),
                'total_paid' => $invoices->where('status', InvoiceStatus::PAID)->sum('total_amount'),
                'total_outstanding' => $invoices->whereIn('status', [InvoiceStatus::DRAFT, InvoiceStatus::FINALIZED])->sum('total_amount'),
                'payment_rate' => $invoices->count() > 0 ? 
                    round(($invoices->where('status', InvoiceStatus::PAID)->count() / $invoices->count()) * 100, 2) : 0,
            ];
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'getRevenueStats',
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d')
            ]);
            return [];
        } finally {
            $this->resetQuery();
        }
    }
}