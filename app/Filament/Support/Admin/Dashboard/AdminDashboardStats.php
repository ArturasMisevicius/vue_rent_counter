<?php

namespace App\Filament\Support\Admin\Dashboard;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SystemConfiguration;
use App\Models\SystemSetting;
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
     *         revenue_this_month: string
     *     },
     *     subscription_usage: array<int, array{
     *         key: string,
     *         label: string,
     *         used: int,
     *         limit: int,
     *         summary: string,
     *         percent: int,
     *         tone: string,
     *         limit_reached: bool,
     *         message: string
     *     }>,
     *     recent_invoices: array<int, array{
     *         id: int,
     *         tenant: string,
     *         property: string,
     *         billing_period: string,
     *         amount: string,
     *         status: string,
     *         can_process_payment: bool
     *     }>,
     *     upcoming_reading_deadlines: array<int, array{
     *         meter_id: int,
     *         meter_identifier: string,
     *         property_name: string,
     *         due_label: string,
     *         tone: string
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
     *     id: int,
     *     tenant: string,
     *     property: string,
     *     billing_period: string,
     *     amount: string,
     *     status: string,
     *     can_process_payment: bool
     * }>
     */
    public function recentInvoicesFor(User $user, int $limit = 10): array
    {
        return $this->dashboardFor($user, $limit)['recent_invoices'];
    }

    /**
     * @return array<int, array{
     *     meter_id: int,
     *     meter_identifier: string,
     *     property_name: string,
     *     due_label: string,
     *     tone: string
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
     *         revenue_this_month: string
     *     },
     *     subscription_usage: array<int, array{
     *         key: string,
     *         label: string,
     *         used: int,
     *         limit: int,
     *         summary: string,
     *         percent: int,
     *         tone: string,
     *         limit_reached: bool,
     *         message: string
     *     }>,
     *     recent_invoices: array<int, array{
     *         id: int,
     *         tenant: string,
     *         property: string,
     *         billing_period: string,
     *         amount: string,
     *         status: string,
     *         can_process_payment: bool
     *     }>,
     *     upcoming_reading_deadlines: array<int, array{
     *         meter_id: int,
     *         meter_identifier: string,
     *         property_name: string,
     *         due_label: string,
     *         tone: string
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
                'users as tenants_count' => fn (Builder $query): Builder => $query->where('role', UserRole::TENANT),
                'propertyAssignments as active_tenants_count' => fn (Builder $query): Builder => $query->current(),
                'invoices as pending_invoices_count' => fn (Builder $query): Builder => $query->where('status', InvoiceStatus::DRAFT),
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

        $subscription = $organization->currentSubscription;

        if ($subscription !== null) {
            $subscription->setRelation('organization', $organization);
        }

        return [
            'metrics' => [
                'total_properties' => (int) $organization->properties_count,
                'active_tenants' => (int) $organization->active_tenants_count,
                'pending_invoices' => (int) $organization->pending_invoices_count,
                'revenue_this_month' => $this->formatCurrency((float) ($organization->revenue_this_month ?? 0)),
            ],
            'subscription_usage' => $this->buildSubscriptionUsage($subscription),
            'recent_invoices' => $this->buildRecentInvoices($organizationId, $invoiceLimit),
            'upcoming_reading_deadlines' => $this->buildUpcomingReadingDeadlines($organizationId, $deadlineLimit),
        ];
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     tenant: string,
     *     property: string,
     *     billing_period: string,
     *     amount: string,
     *     status: string,
     *     can_process_payment: bool
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
                'amount_paid',
                'paid_amount',
                'billing_period_start',
                'billing_period_end',
                'due_date',
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
                    'id' => (int) $invoice->getKey(),
                    'number' => (string) $invoice->invoice_number,
                    'tenant' => (string) ($invoice->tenant?->name ?? __('dashboard.not_available')),
                    'property' => filled($unitNumber)
                        ? $propertyName.' · '.$unitNumber
                        : $propertyName,
                    'billing_period' => $this->formatBillingPeriod(
                        $invoice->billing_period_start,
                        $invoice->billing_period_end,
                    ),
                    'amount' => $this->formatCurrency((float) $invoice->total_amount),
                    'status' => $invoice->effectiveStatus()->label(),
                    'can_process_payment' => $invoice->status === InvoiceStatus::FINALIZED,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{
     *     meter_id: int,
     *     meter_identifier: string,
     *     property_name: string,
     *     due_label: string,
     *     tone: string
     * }>
     */
    private function buildUpcomingReadingDeadlines(int $organizationId, int $limit): array
    {
        $thresholdDays = $this->readingThresholdDays();

        return Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'installed_at', 'created_at'])
            ->forOrganization($organizationId)
            ->active()
            ->with([
                'property:id,name,unit_number',
                'latestReading:id,meter_id,reading_date',
            ])
            ->ordered()
            ->get()
            ->map(function (Meter $meter) use ($thresholdDays): array {
                $baseDate = $meter->latestReading?->reading_date
                    ?? $meter->installed_at
                    ?? $meter->created_at;

                $dueDate = $baseDate->copy()->addDays($thresholdDays);
                $daysUntilDue = (int) now()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false);
                $unitNumber = $meter->property?->unit_number;
                $propertyName = (string) ($meter->property?->name ?? __('dashboard.not_available'));

                return [
                    'meter_id' => (int) $meter->getKey(),
                    'meter_identifier' => (string) ($meter->identifier ?: $meter->name),
                    'property_name' => filled($unitNumber)
                        ? $propertyName.' · '.$unitNumber
                        : $propertyName,
                    'due_label' => $this->formatDueLabel($dueDate),
                    'days_until_due' => $daysUntilDue,
                    'tone' => $this->deadlineTone($daysUntilDue),
                    'due_sort' => $dueDate->timestamp,
                ];
            })
            ->filter(fn (array $deadline): bool => $deadline['days_until_due'] <= 14)
            ->sortBy('due_sort')
            ->take($limit)
            ->map(fn (array $deadline): array => [
                'meter_id' => $deadline['meter_id'],
                'meter_identifier' => $deadline['meter_identifier'],
                'property_name' => $deadline['property_name'],
                'due_label' => $deadline['due_label'],
                'tone' => $deadline['tone'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     used: int,
     *     limit: int,
     *     summary: string,
     *     percent: int,
     *     tone: string,
     *     limit_reached: bool,
     *     message: string
     * }>
     */
    private function buildSubscriptionUsage(?Subscription $subscription): array
    {
        if ($subscription === null) {
            return [];
        }

        return [
            [
                'key' => 'properties',
                'label' => __('dashboard.organization_usage.properties'),
                'used' => $subscription->propertiesUsedCount(),
                'limit' => $subscription->propertyLimit(),
                'summary' => __('dashboard.organization_usage.usage_summary', [
                    'used' => $subscription->propertiesUsedCount(),
                    'limit' => $subscription->propertyLimit(),
                    'label' => strtolower(__('dashboard.organization_usage.properties')),
                ]),
                'percent' => $subscription->propertyUsagePercent(),
                'tone' => $subscription->propertyUsageTone(),
                'limit_reached' => $subscription->hasReachedPropertyLimit(),
                'message' => __('dashboard.organization_usage.limit_reached', [
                    'label' => strtolower(__('dashboard.organization_usage.properties')),
                ]),
            ],
            [
                'key' => 'tenants',
                'label' => __('dashboard.organization_usage.tenants'),
                'used' => $subscription->tenantsUsedCount(),
                'limit' => $subscription->tenantLimit(),
                'summary' => __('dashboard.organization_usage.usage_summary', [
                    'used' => $subscription->tenantsUsedCount(),
                    'limit' => $subscription->tenantLimit(),
                    'label' => strtolower(__('dashboard.organization_usage.tenants')),
                ]),
                'percent' => $subscription->tenantUsagePercent(),
                'tone' => $subscription->tenantUsageTone(),
                'limit_reached' => $subscription->hasReachedTenantLimit(),
                'message' => __('dashboard.organization_usage.limit_reached', [
                    'label' => strtolower(__('dashboard.organization_usage.tenants')),
                ]),
            ],
        ];
    }

    /**
     * @return array{
     *     metrics: array{
     *         total_properties: int,
     *         active_tenants: int,
     *         pending_invoices: int,
     *         revenue_this_month: string
     *     },
     *     subscription_usage: array<int, array{
     *         key: string,
     *         label: string,
     *         used: int,
     *         limit: int,
     *         summary: string,
     *         percent: int,
     *         tone: string,
     *         limit_reached: bool,
     *         message: string
     *     }>,
     *     recent_invoices: array<int, array{
     *         id: int,
     *         tenant: string,
     *         property: string,
     *         billing_period: string,
     *         amount: string,
     *         status: string,
     *         can_process_payment: bool
     *     }>,
     *     upcoming_reading_deadlines: array<int, array{
     *         meter_id: int,
     *         meter_identifier: string,
     *         property_name: string,
     *         due_label: string,
     *         tone: string
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

    private function formatBillingPeriod(?CarbonInterface $periodStart, ?CarbonInterface $periodEnd): string
    {
        $from = $periodStart?->translatedFormat('F Y') ?? __('dashboard.not_available');
        $to = $periodEnd?->translatedFormat('F Y') ?? __('dashboard.not_available');

        return __('dashboard.organization_invoice_period', [
            'from' => $from,
            'to' => $to,
        ]);
    }

    private function deadlineTone(int $daysUntilDue): string
    {
        return match (true) {
            $daysUntilDue < 0 => 'danger',
            $daysUntilDue <= 3 => 'warning',
            default => 'neutral',
        };
    }

    private function readingThresholdDays(): int
    {
        $systemSetting = SystemSetting::query()
            ->select(['id', 'key', 'value'])
            ->where('key', 'reports.meter_compliance.threshold_days')
            ->first();

        if ($systemSetting !== null) {
            $value = is_array($systemSetting->value) ? ($systemSetting->value['value'] ?? null) : null;

            if (is_numeric($value)) {
                return max((int) $value, 1);
            }
        }

        $systemConfiguration = SystemConfiguration::query()
            ->select(['id', 'key', 'value'])
            ->where('key', 'reports.meter_compliance.threshold_days')
            ->first();

        if ($systemConfiguration !== null) {
            $value = is_array($systemConfiguration->value) ? ($systemConfiguration->value['value'] ?? null) : null;

            if (is_numeric($value)) {
                return max((int) $value, 1);
            }
        }

        return 30;
    }
}
