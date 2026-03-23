<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Reports;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Query\Expression;

final class RevenueMonthlyTotalsChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    public ?int $organizationId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function getHeading(): ?string
    {
        return __('admin.reports.tabs.revenue');
    }

    protected function getData(): array
    {
        if ($this->organizationId === null) {
            return [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => __('admin.reports.columns.total_invoiced'),
                        'data' => [],
                    ],
                ],
            ];
        }

        $fromDate = filled($this->dateFrom)
            ? (string) $this->dateFrom
            : now()->startOfMonth()->toDateString();
        $toDate = filled($this->dateTo)
            ? (string) $this->dateTo
            : now()->endOfMonth()->toDateString();

        $query = Invoice::query()
            ->from('invoices')
            ->where('organization_id', $this->organizationId)
            ->whereBetween('billing_period_end', [$fromDate, $toDate]);

        $driver = $query->getConnection()->getDriverName();
        $monthExpression = $this->monthExpression($driver);
        $normalizedPaidExpression = 'CASE WHEN COALESCE(invoices.amount_paid, 0) >= COALESCE(invoices.paid_amount, 0) THEN COALESCE(invoices.amount_paid, 0) ELSE COALESCE(invoices.paid_amount, 0) END';

        $rows = $query
            ->select(new Expression($monthExpression.' AS report_month'))
            ->addSelect(new Expression('SUM(invoices.total_amount) AS total_invoiced_amount'))
            ->addSelect(new Expression('SUM('.$normalizedPaidExpression.') AS total_paid_amount'))
            ->groupBy(new Expression($monthExpression))
            ->orderBy('report_month')
            ->get();

        return [
            'labels' => $rows->pluck('report_month')->map(fn (?string $month): string => (string) $month)->all(),
            'datasets' => [
                [
                    'label' => __('admin.reports.columns.total_invoiced'),
                    'data' => $rows->pluck('total_invoiced_amount')->map(fn ($value): float => (float) $value)->all(),
                    'borderColor' => '#0f172a',
                    'backgroundColor' => 'rgba(15, 23, 42, 0.15)',
                    'fill' => true,
                    'tension' => 0.25,
                ],
                [
                    'label' => __('admin.reports.columns.total_paid'),
                    'data' => $rows->pluck('total_paid_amount')->map(fn ($value): float => (float) $value)->all(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                    'tension' => 0.25,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function monthExpression(string $driver): string
    {
        return match ($driver) {
            'pgsql' => "to_char(date_trunc('month', invoices.billing_period_end), 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', invoices.billing_period_end)",
            default => "DATE_FORMAT(invoices.billing_period_end, '%Y-%m')",
        };
    }
}
