<?php

namespace App\Filament\Support\Admin\Dashboard;

use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;

class AdminDashboardStats
{
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
        $organizationId = $this->resolveOrganizationId($user);

        if ($organizationId === null) {
            return [
                'total_properties' => 0,
                'active_tenants' => 0,
                'pending_invoices' => 0,
                'revenue_this_month' => $this->formatCurrency(0),
            ];
        }

        $revenue = (float) Invoice::query()
            ->forOrganization($organizationId)
            ->paidBetween(now()->startOfMonth(), now()->endOfMonth())
            ->sum('amount_paid');

        return [
            'total_properties' => Property::query()
                ->forOrganization($organizationId)
                ->count(),
            'active_tenants' => PropertyAssignment::query()
                ->forOrganization($organizationId)
                ->current()
                ->count(),
            'pending_invoices' => Invoice::query()
                ->forOrganization($organizationId)
                ->pendingAttention()
                ->count(),
            'revenue_this_month' => $this->formatCurrency($revenue),
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function subscriptionUsageFor(User $user): array
    {
        $organizationId = $this->resolveOrganizationId($user);

        if ($organizationId === null) {
            return [];
        }

        $subscription = Subscription::query()
            ->select([
                'id',
                'organization_id',
                'property_limit_snapshot',
                'tenant_limit_snapshot',
                'meter_limit_snapshot',
                'invoice_limit_snapshot',
                'starts_at',
            ])
            ->forOrganization($organizationId)
            ->latestFirst()
            ->first();

        $propertyCount = Property::query()
            ->forOrganization($organizationId)
            ->count();

        $activeTenantCount = PropertyAssignment::query()
            ->forOrganization($organizationId)
            ->current()
            ->count();

        $meterCount = Meter::query()
            ->forOrganization($organizationId)
            ->count();

        $invoiceCount = Invoice::query()
            ->forOrganization($organizationId)
            ->count();

        return [
            [
                'label' => __('dashboard.organization_usage.properties'),
                'value' => $this->formatUsage($propertyCount, $subscription?->property_limit_snapshot),
            ],
            [
                'label' => __('dashboard.organization_usage.tenants'),
                'value' => $this->formatUsage($activeTenantCount, $subscription?->tenant_limit_snapshot),
            ],
            [
                'label' => __('dashboard.organization_usage.meters'),
                'value' => $this->formatUsage($meterCount, $subscription?->meter_limit_snapshot),
            ],
            [
                'label' => __('dashboard.organization_usage.invoices'),
                'value' => $this->formatUsage($invoiceCount, $subscription?->invoice_limit_snapshot),
            ],
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
    public function recentInvoicesFor(User $user, int $limit = 5): array
    {
        $organizationId = $this->resolveOrganizationId($user);

        if ($organizationId === null) {
            return [];
        }

        return Invoice::query()
            ->forAdminWorkspace($organizationId)
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
                'billing_period_end',
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
                    'status' => Str::of($invoice->status->value)
                        ->replace('_', ' ')
                        ->title()
                        ->value(),
                ];
            })
            ->all();
    }

    private function resolveOrganizationId(User $user): ?int
    {
        return $user->organization_id;
    }

    private function formatCurrency(float $amount): string
    {
        return 'EUR '.number_format($amount, 2, '.', '');
    }

    private function formatUsage(int $current, ?int $limit): string
    {
        return $current.' / '.($limit ?? '—');
    }
}
