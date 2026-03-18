<?php

namespace App\Filament\Support\Admin\Dashboard;

use App\Enums\InvoiceStatus;
use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardStats
{
    public function __construct(
        protected DashboardCacheService $dashboardCacheService,
    ) {}

    /**
     * @return array{
     *     metrics: array{
     *         total_properties: int,
     *         active_tenants: int,
     *         pending_invoices: int,
     *         draft_invoices: int,
     *         revenue_this_month: string
     *     },
     *     subscription_usage: array<int, array{label: string, value: string}>,
     *     recent_invoices: array<int, array{
     *         number: string,
     *         tenant: string,
     *         property: string,
     *         amount: string,
     *         status: string
     *     }>,
     *     upcoming_reading_deadlines: array<int, array{
     *         meter_name: string,
     *         property_name: string,
     *         due_label: string
     *     }>
     * }
     */
    public function dashboardFor(User $user, int $invoiceLimit = 10, int $deadlineLimit = 10): array
    {
        $organizationId = $this->resolveOrganizationId($user);

        if ($organizationId === null) {
            return $this->emptyDashboard();
        }

        return $this->dashboardCacheService->remember(
            $user,
            'organization-dashboard',
            fn (): array => $this->buildDashboard($organizationId, $invoiceLimit, $deadlineLimit),
            [
                'invoices-'.$invoiceLimit,
                'deadlines-'.$deadlineLimit,
            ],
        );
    }

    /**
     * @return array{
     *     total_properties: int,
     *     active_tenants: int,
     *     pending_invoices: int,
     *     draft_invoices: int,
     *     revenue_this_month: string
     * }
     */
    public function metricsFor(User $user): array
    {
        return $this->dashboardFor($user)['metrics'];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function subscriptionUsageFor(User $user): array
    {
        return $this->dashboardFor($user)['subscription_usage'];
    }

    /**
     * @return array<int, array{
     *     number: string,
     *     tenant: string,
     *     property: string,
     *     amount: string,
     *     status: string
     * }>
     */
    public function recentInvoicesFor(User $user, int $limit = 10): array
    {
        return $this->dashboardFor($user, $limit)['recent_invoices'];
    }

    /**
     * @return array<int, array{
     *     meter_name: string,
     *     property_name: string,
     *     due_label: string
     * }>
     */
    public function upcomingReadingDeadlinesFor(User $user, int $limit = 10): array
    {
        return $this->dashboardFor($user, deadlineLimit: $limit)['upcoming_reading_deadlines'];
    }

    private function resolveOrganizationId(User $user): ?int
    {
        return $user->organization_id;
    }

    /**
     * @return array{
     *     metrics: array{
     *         total_properties: int,
     *         active_tenants: int,
     *         pending_invoices: int,
     *         draft_invoices: int,
     *         revenue_this_month: string
     *     },
     *     subscription_usage: array<int, array{label: string, value: string}>,
     *     recent_invoices: array<int, array{
     *         number: string,
     *         tenant: string,
     *         property: string,
     *         amount: string,
     *         status: string
     *     }>,
     *     upcoming_reading_deadlines: array<int, array{
     *         meter_name: string,
     *         property_name: string,
     *         due_label: string
     *     }>
     * }
     */
    private function buildDashboard(int $organizationId, int $invoiceLimit, int $deadlineLimit): array
    {
        $organization = Organization::query()
            ->select(['id'])
            ->whereKey($organizationId)
            ->withCount([
                'properties',
                'meters',
                'invoices',
                'propertyAssignments as active_tenants_count' => fn (Builder $query): Builder => $query->current(),
                'invoices as pending_invoices_count' => fn (Builder $query): Builder => $query->pendingAttention(),
                'invoices as draft_invoices_count' => fn (Builder $query): Builder => $query->where('status', InvoiceStatus::DRAFT),
            ])
            ->withSum([
                'invoices as revenue_this_month' => fn (Builder $query): Builder => $query->paidBetween(
                    now()->startOfMonth(),
                    now()->endOfMonth(),
                ),
            ], 'amount_paid')
            ->with([
                'currentSubscription:id,organization_id,property_limit_snapshot,tenant_limit_snapshot,meter_limit_snapshot,invoice_limit_snapshot',
            ])
            ->first();

        if ($organization === null) {
            return $this->emptyDashboard();
        }

        return [
            'metrics' => [
                'total_properties' => (int) $organization->properties_count,
                'active_tenants' => (int) $organization->active_tenants_count,
                'pending_invoices' => (int) $organization->pending_invoices_count,
                'draft_invoices' => (int) $organization->draft_invoices_count,
                'revenue_this_month' => $this->formatCurrency((float) ($organization->revenue_this_month ?? 0)),
            ],
            'subscription_usage' => [
                [
                    'label' => __('dashboard.organization_usage.properties'),
                    'value' => $this->formatUsage(
                        (int) $organization->properties_count,
                        $organization->currentSubscription?->property_limit_snapshot,
                    ),
                ],
                [
                    'label' => __('dashboard.organization_usage.tenants'),
                    'value' => $this->formatUsage(
                        (int) $organization->active_tenants_count,
                        $organization->currentSubscription?->tenant_limit_snapshot,
                    ),
                ],
                [
                    'label' => __('dashboard.organization_usage.meters'),
                    'value' => $this->formatUsage(
                        (int) $organization->meters_count,
                        $organization->currentSubscription?->meter_limit_snapshot,
                    ),
                ],
                [
                    'label' => __('dashboard.organization_usage.invoices'),
                    'value' => $this->formatUsage(
                        (int) $organization->invoices_count,
                        $organization->currentSubscription?->invoice_limit_snapshot,
                    ),
                ],
            ],
            'recent_invoices' => $this->buildRecentInvoices($organizationId, $invoiceLimit),
            'upcoming_reading_deadlines' => $this->buildUpcomingReadingDeadlines($organizationId, $deadlineLimit),
        ];
    }

    /**
     * @return array<int, array{
     *     number: string,
     *     tenant: string,
     *     property: string,
     *     amount: string,
     *     status: string
     * }>
     */
    private function buildRecentInvoices(int $organizationId, int $limit): array
    {
        return Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'status',
                'currency',
                'total_amount',
                'billing_period_start',
            ])
            ->forOrganization($organizationId)
            ->with([
                'property:id,name,unit_number',
                'tenant:id,name',
            ])
            ->latestBillingFirst()
            ->limit($limit)
            ->get()
            ->map(function (Invoice $invoice): array {
                $propertyName = (string) ($invoice->property?->name ?? __('dashboard.not_available'));
                $unitNumber = $invoice->property?->unit_number;

                return [
                    'number' => (string) $invoice->invoice_number,
                    'tenant' => (string) ($invoice->tenant?->name ?? __('dashboard.not_available')),
                    'property' => filled($unitNumber)
                        ? $propertyName.' · '.$unitNumber
                        : $propertyName,
                    'amount' => $this->formatCurrency((float) $invoice->total_amount),
                    'status' => $invoice->status->label(),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{
     *     meter_name: string,
     *     property_name: string,
     *     due_label: string
     * }>
     */
    private function buildUpcomingReadingDeadlines(int $organizationId, int $limit): array
    {
        return Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name', 'installed_at', 'created_at'])
            ->forOrganization($organizationId)
            ->active()
            ->with([
                'property:id,name,unit_number',
                'latestReading:id,meter_id,reading_date',
            ])
            ->ordered()
            ->get()
            ->map(function (Meter $meter): array {
                $baseDate = $meter->latestReading?->reading_date
                    ?? $meter->installed_at
                    ?? $meter->created_at;

                $dueDate = $baseDate->copy()->addDays(30);
                $unitNumber = $meter->property?->unit_number;
                $propertyName = (string) ($meter->property?->name ?? __('dashboard.not_available'));

                return [
                    'meter_name' => (string) $meter->name,
                    'property_name' => filled($unitNumber)
                        ? $propertyName.' · '.$unitNumber
                        : $propertyName,
                    'due_label' => $this->formatDueLabel($dueDate),
                    'due_sort' => $dueDate->timestamp,
                ];
            })
            ->filter(fn (array $deadline): bool => $deadline['due_sort'] <= now()->addDays(14)->timestamp)
            ->sortBy('due_sort')
            ->take($limit)
            ->map(fn (array $deadline): array => [
                'meter_name' => $deadline['meter_name'],
                'property_name' => $deadline['property_name'],
                'due_label' => $deadline['due_label'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     metrics: array{
     *         total_properties: int,
     *         active_tenants: int,
     *         pending_invoices: int,
     *         draft_invoices: int,
     *         revenue_this_month: string
     *     },
     *     subscription_usage: array<int, array{label: string, value: string}>,
     *     recent_invoices: array<int, array{
     *         number: string,
     *         tenant: string,
     *         property: string,
     *         amount: string,
     *         status: string
     *     }>,
     *     upcoming_reading_deadlines: array<int, array{
     *         meter_name: string,
     *         property_name: string,
     *         due_label: string
     *     }>
     * }
     */
    private function emptyDashboard(): array
    {
        return [
            'metrics' => [
                'total_properties' => 0,
                'active_tenants' => 0,
                'pending_invoices' => 0,
                'draft_invoices' => 0,
                'revenue_this_month' => $this->formatCurrency(0),
            ],
            'subscription_usage' => [],
            'recent_invoices' => [],
            'upcoming_reading_deadlines' => [],
        ];
    }

    private function formatCurrency(float $amount): string
    {
        return 'EUR '.number_format($amount, 2, '.', '');
    }

    private function formatUsage(int $current, ?int $limit): string
    {
        return $current.' / '.($limit ?? '—');
    }

    private function formatDueLabel(CarbonInterface $dueDate): string
    {
        $days = (int) now()->startOfDay()->diffInDays($dueDate->startOfDay(), false);

        if ($days < 0) {
            return __('dashboard.organization_deadlines.overdue_by_days', [
                'days' => abs($days),
            ]);
        }

        if ($days === 0) {
            return __('dashboard.organization_deadlines.due_today');
        }

        return __('dashboard.organization_deadlines.due_in_days', [
            'days' => $days,
        ]);
    }
}
