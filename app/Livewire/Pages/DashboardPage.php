<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\SystemHealthMetric;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

final class DashboardPage extends Component
{
    /**
     * @throws AuthorizationException
     */
    public function render(): View
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $data = match ($user->role) {
            UserRole::SUPERADMIN => $this->buildSuperadminData(),
            UserRole::ADMIN => $this->buildAdminData($user),
            UserRole::MANAGER => $this->buildManagerData($user),
            UserRole::TENANT => $this->buildTenantData($user),
        };

        return view('pages.dashboard.index', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSuperadminData(): array
    {
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE->value)->count();
        $expiredSubscriptions = Subscription::where('status', SubscriptionStatus::EXPIRED->value)->count();
        $suspendedSubscriptions = Subscription::where('status', SubscriptionStatus::SUSPENDED->value)->count();

        $subscriptionsByPlan = Subscription::select('plan_type', DB::raw('count(*) as count'))
            ->groupBy('plan_type')
            ->get()
            ->pluck('count', 'plan_type');

        $expiringSubscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE->value)
            ->where('expires_at', '<=', now()->addDays(14))
            ->where('expires_at', '>=', now())
            ->with('user')
            ->get();

        $organizationStats = Cache::remember(
            'superadmin_dashboard_organizations_stats',
            now()->addMinutes(10),
            fn (): array => [
                'total' => Organization::count(),
                'active' => Organization::where('is_active', true)->count(),
            ],
        );

        $totalOrganizations = (int) ($organizationStats['total'] ?? 0);
        $activeOrganizations = (int) ($organizationStats['active'] ?? 0);

        $totalProperties = Property::withoutGlobalScopes()->count();
        $totalBuildings = Building::withoutGlobalScopes()->count();
        $totalTenants = User::withoutGlobalScopes()
            ->where('role', UserRole::TENANT)
            ->count();
        $totalInvoices = Invoice::withoutGlobalScopes()->count();

        $recentActivity = OrganizationActivityLog::query()
            ->with(['organization', 'user'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $topOrganizations = Organization::query()
            ->withCount('properties')
            ->orderByDesc('properties_count')
            ->take(5)
            ->get();

        $subscriptionList = Subscription::with('user')
            ->orderByDesc('created_at')
            ->take(12)
            ->get();

        $organizationList = Organization::query()
            ->orderByDesc('created_at')
            ->take(12)
            ->get();

        $organizationLookup = Organization::query()
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $latestProperties = Property::withoutGlobalScopes()
            ->with(['building'])
            ->orderByDesc('updated_at')
            ->take(10)
            ->get();

        $latestBuildings = Building::withoutGlobalScopes()
            ->orderByDesc('updated_at')
            ->take(10)
            ->get();

        $latestTenants = User::withoutGlobalScopes()
            ->where('role', UserRole::TENANT)
            ->with(['property'])
            ->orderByDesc('updated_at')
            ->take(10)
            ->get();

        $latestInvoices = Invoice::withoutGlobalScopes()
            ->with(['tenant', 'property'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $systemHealthMetrics = SystemHealthMetric::query()
            ->orderByDesc('checked_at')
            ->get()
            ->groupBy('metric_type')
            ->map(fn ($metrics) => $metrics->first())
            ->values();

        return compact(
            'totalSubscriptions',
            'activeSubscriptions',
            'expiredSubscriptions',
            'suspendedSubscriptions',
            'subscriptionsByPlan',
            'expiringSubscriptions',
            'totalOrganizations',
            'activeOrganizations',
            'totalProperties',
            'totalBuildings',
            'totalTenants',
            'totalInvoices',
            'recentActivity',
            'topOrganizations',
            'subscriptionList',
            'organizationList',
            'organizationLookup',
            'latestProperties',
            'latestBuildings',
            'latestTenants',
            'latestInvoices',
            'systemHealthMetrics',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAdminData(User $admin): array
    {
        $this->authorize('viewAny', User::class);

        $subscription = $admin->subscription;
        $subscriptionStatus = null;
        $daysUntilExpiry = null;
        $showExpiryWarning = false;

        if ($subscription !== null) {
            $daysUntilExpiry = $subscription->daysUntilExpiry();
            $showExpiryWarning = $daysUntilExpiry !== null && $daysUntilExpiry <= 14 && $daysUntilExpiry > 0;

            if ($subscription->isExpired()) {
                $subscriptionStatus = 'expired';
            } elseif ($showExpiryWarning) {
                $subscriptionStatus = 'expiring_soon';
            } else {
                $subscriptionStatus = 'active';
            }
        } else {
            $subscriptionStatus = 'no_subscription';
        }

        $stats = [
            'total_properties' => Property::where('tenant_id', $admin->tenant_id)->count(),
            'total_buildings' => Building::where('tenant_id', $admin->tenant_id)->count(),
            'total_tenants' => User::where('tenant_id', $admin->tenant_id)
                ->where('role', UserRole::TENANT->value)
                ->count(),
            'active_tenants' => User::where('tenant_id', $admin->tenant_id)
                ->where('role', UserRole::TENANT->value)
                ->where('is_active', true)
                ->count(),
            'active_meters' => Meter::whereHas('property', function ($query) use ($admin): void {
                $query->where('tenant_id', $admin->tenant_id);
            })->count(),
            'draft_invoices' => Invoice::where('tenant_id', $admin->tenant_id)
                ->where('status', 'draft')
                ->count(),
            'unpaid_invoices' => Invoice::where('tenant_id', $admin->tenant_id)
                ->whereIn('status', ['draft', 'finalized'])
                ->count(),
            'recent_readings_count' => MeterReading::whereHas('meter.property', function ($query) use ($admin): void {
                $query->where('tenant_id', $admin->tenant_id);
            })->where('reading_date', '>=', now()->subDays(7))->count(),
        ];

        $usageStats = null;
        if ($subscription !== null) {
            $usageStats = [
                'properties_used' => $stats['total_properties'],
                'properties_max' => $subscription->max_properties,
                'properties_percentage' => $subscription->max_properties > 0
                    ? round(($stats['total_properties'] / $subscription->max_properties) * 100)
                    : 0,
                'tenants_used' => $stats['total_tenants'],
                'tenants_max' => $subscription->max_tenants,
                'tenants_percentage' => $subscription->max_tenants > 0
                    ? round(($stats['total_tenants'] / $subscription->max_tenants) * 100)
                    : 0,
            ];
        }

        $pendingTasks = [
            'pending_meter_readings' => $this->getPendingMeterReadingsCount($admin),
            'draft_invoices' => $stats['draft_invoices'],
            'inactive_tenants' => $stats['total_tenants'] - $stats['active_tenants'],
        ];

        $recentActivity = [
            'recent_tenants' => User::where('tenant_id', $admin->tenant_id)
                ->where('role', UserRole::TENANT->value)
                ->with('property')
                ->latest()
                ->take(5)
                ->get(),
            'recent_invoices' => Invoice::where('tenant_id', $admin->tenant_id)
                ->with(['property'])
                ->latest()
                ->take(5)
                ->get(),
            'recent_readings' => MeterReading::whereHas('meter.property', function ($query) use ($admin): void {
                $query->where('tenant_id', $admin->tenant_id);
            })
                ->with(['meter.property', 'enteredBy'])
                ->latest('reading_date')
                ->take(5)
                ->get(),
        ];

        return compact(
            'stats',
            'subscription',
            'subscriptionStatus',
            'daysUntilExpiry',
            'showExpiryWarning',
            'usageStats',
            'pendingTasks',
            'recentActivity',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildManagerData(User $user): array
    {
        $tenantId = $user->tenant_id;
        $cacheKey = "manager_dashboard_stats_{$tenantId}";
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();

        $stats = Cache::remember($cacheKey, 300, function () use ($currentMonthStart, $now): array {
            return [
                'total_properties' => Property::count(),
                'active_meters' => Meter::count(),
                'active_tenants' => Tenant::count(),
                'meters_pending_reading' => Meter::whereDoesntHave('readings', function ($query) use ($currentMonthStart): void {
                    $query->where('reading_date', '>=', $currentMonthStart);
                })->count(),
                'draft_invoices' => Invoice::draft()->count(),
                'overdue_invoices' => Invoice::where('status', InvoiceStatus::FINALIZED)
                    ->whereDate('due_date', '<', $now)
                    ->count(),
                'recent_invoices' => Invoice::with(['tenant.property'])->latest()->take(5)->get(),
            ];
        });

        $propertiesNeedingReadings = Cache::remember("{$cacheKey}_pending_readings", 300, function () use ($currentMonthStart) {
            return Property::whereHas('meters', function ($query) use ($currentMonthStart): void {
                $query->whereDoesntHave('readings', function ($q) use ($currentMonthStart): void {
                    $q->where('reading_date', '>=', $currentMonthStart);
                });
            })
                ->with(['building', 'meters' => function ($query) use ($currentMonthStart): void {
                    $query->whereDoesntHave('readings', function ($q) use ($currentMonthStart): void {
                        $q->where('reading_date', '>=', $currentMonthStart);
                    });
                }])
                ->limit(10)
                ->get();
        });

        $draftInvoices = Cache::remember("{$cacheKey}_draft_invoices", 300, function () {
            return Invoice::draft()
                ->with(['tenant.property', 'items'])
                ->latest()
                ->limit(5)
                ->get();
        });

        return compact('stats', 'propertiesNeedingReadings', 'draftInvoices');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTenantData(User $user): array
    {
        $property = $user->property;

        if ($property === null) {
            return [
                'stats' => [
                    'property' => null,
                    'latest_readings' => collect(),
                    'unpaid_balance' => 0,
                    'total_invoices' => 0,
                    'unpaid_invoices' => 0,
                ],
            ];
        }

        $cacheKey = "tenant_dashboard_{$user->id}";

        $property->load([
            'meters.readings' => function ($query): void {
                $query->latest('reading_date')->limit(2);
            },
            'meters.serviceConfiguration.utilityService',
            'building',
        ]);

        $stats = Cache::remember($cacheKey, 300, function () use ($user, $property): array {
            $tenant = $user->tenant;

            $latestReadings = MeterReading::whereHas('meter', function ($query) use ($property): void {
                $query->where('property_id', $property->id);
            })
                ->with(['meter.serviceConfiguration.utilityService'])
                ->latest('reading_date')
                ->limit(5)
                ->get();

            $unpaidBalance = 0;
            $totalInvoices = 0;
            $unpaidInvoices = 0;

            if ($tenant !== null) {
                $unpaidBalance = Invoice::where('tenant_renter_id', $tenant->id)
                    ->where('status', 'finalized')
                    ->sum('total_amount');

                $totalInvoices = Invoice::where('tenant_renter_id', $tenant->id)->count();
                $unpaidInvoices = Invoice::where('tenant_renter_id', $tenant->id)
                    ->where('status', 'finalized')
                    ->count();
            }

            $consumptionTrends = $property->meters->map(function ($meter): array {
                $readings = $meter->readings->sortByDesc('reading_date')->values();
                $latest = $readings->get(0);
                $previous = $readings->get(1);

                $delta = null;
                $percent = null;

                if ($latest !== null && $previous !== null) {
                    $delta = $latest->value - $previous->value;
                    $percent = $previous->value != 0 ? ($delta / $previous->value) * 100 : null;
                }

                return [
                    'meter' => $meter,
                    'latest' => $latest,
                    'previous' => $previous,
                    'delta' => $delta,
                    'percent' => $percent,
                ];
            });

            return [
                'property' => $property,
                'latest_readings' => $latestReadings,
                'unpaid_balance' => $unpaidBalance,
                'total_invoices' => $totalInvoices,
                'unpaid_invoices' => $unpaidInvoices,
                'consumption_trends' => $consumptionTrends,
            ];
        });

        return compact('stats');
    }

    private function getPendingMeterReadingsCount(User $admin): int
    {
        $startOfMonth = now()->startOfMonth();

        return Property::where('tenant_id', $admin->tenant_id)
            ->whereHas('meters', function ($query) use ($startOfMonth): void {
                $query->whereDoesntHave('readings', function ($q) use ($startOfMonth): void {
                    $q->where('reading_date', '>=', $startOfMonth);
                });
            })
            ->count();
    }
}
