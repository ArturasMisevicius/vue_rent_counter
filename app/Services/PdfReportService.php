<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\OrganizationActivityLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * PdfReportService handles PDF report generation for superadmin dashboard
 * 
 * Generates comprehensive PDF reports with:
 * - Executive summaries with key metrics
 * - Charts and visualizations
 * - Formatted tables with data
 * - Professional styling and branding
 */
class PdfReportService
{
    /**
     * Generate executive summary PDF report
     */
    public function generateExecutiveSummary(): string
    {
        $data = $this->getExecutiveSummaryData();
        
        $pdf = Pdf::loadView('reports.executive-summary', $data);
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'executive_summary_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = storage_path('app/' . $filename);
        
        $pdf->save($filepath);
        
        return $filepath;
    }

    /**
     * Generate organizations report PDF
     */
    public function generateOrganizationsReport(Builder $query = null): string
    {
        $organizations = $query ? $query->get() : Organization::all();
        $data = $this->getOrganizationsReportData($organizations);
        
        $pdf = Pdf::loadView('reports.organizations', $data);
        $pdf->setPaper('A4', 'landscape');
        
        $filename = 'organizations_report_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = storage_path('app/' . $filename);
        
        $pdf->save($filepath);
        
        return $filepath;
    }

    /**
     * Generate subscriptions report PDF
     */
    public function generateSubscriptionsReport(Builder $query = null): string
    {
        $subscriptions = $query ? $query->with(['user.organization'])->get() : 
                        Subscription::with(['user.organization'])->get();
        $data = $this->getSubscriptionsReportData($subscriptions);
        
        $pdf = Pdf::loadView('reports.subscriptions', $data);
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'subscriptions_report_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = storage_path('app/' . $filename);
        
        $pdf->save($filepath);
        
        return $filepath;
    }

    /**
     * Generate activity logs report PDF
     */
    public function generateActivityLogsReport(Builder $query = null, ?Carbon $startDate = null, ?Carbon $endDate = null): string
    {
        $logs = $this->buildActivityLogsQuery($query, $startDate, $endDate)->limit(1000)->get();
        $data = $this->getActivityLogsReportData($logs, $startDate, $endDate);
        
        $pdf = Pdf::loadView('reports.activity-logs', $data);
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'activity_logs_report_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = storage_path('app/' . $filename);
        
        $pdf->save($filepath);
        
        return $filepath;
    }

    /**
     * Get executive summary data
     */
    private function getExecutiveSummaryData(): array
    {
        $totalOrganizations = Organization::count();
        $activeOrganizations = Organization::active()->count();
        $suspendedOrganizations = Organization::whereNotNull('suspended_at')->count();
        
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::whereHas('user', function ($query) {
            $query->whereHas('organization', function ($q) {
                $q->where('is_active', true);
            });
        })->count();
        
        $expiringSubscriptions = Subscription::where('expires_at', '<=', now()->addDays(14))
            ->where('expires_at', '>', now())
            ->count();
        
        $totalUsers = \App\Models\User::count();
        $totalProperties = \App\Models\Property::count();
        $totalInvoices = \App\Models\Invoice::count();
        
        // Plan distribution
        $planDistribution = Organization::selectRaw('plan, COUNT(*) as count')
            ->groupBy('plan')
            ->pluck('count', 'plan')
            ->toArray();
        
        // Recent activity
        $recentActivity = OrganizationActivityLog::with(['organization', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Growth metrics (last 30 days)
        $organizationGrowth = Organization::where('created_at', '>=', now()->subDays(30))->count();
        $subscriptionGrowth = Subscription::where('created_at', '>=', now()->subDays(30))->count();
        
        return [
            'generated_at' => now(),
            'period' => now()->format('F Y'),
            'metrics' => [
                'organizations' => [
                    'total' => $totalOrganizations,
                    'active' => $activeOrganizations,
                    'suspended' => $suspendedOrganizations,
                    'growth' => $organizationGrowth,
                ],
                'subscriptions' => [
                    'total' => $totalSubscriptions,
                    'active' => $activeSubscriptions,
                    'expiring' => $expiringSubscriptions,
                    'growth' => $subscriptionGrowth,
                ],
                'platform' => [
                    'users' => $totalUsers,
                    'properties' => $totalProperties,
                    'invoices' => $totalInvoices,
                ],
            ],
            'plan_distribution' => $planDistribution,
            'recent_activity' => $recentActivity,
        ];
    }

    /**
     * Get organizations report data
     */
    private function getOrganizationsReportData(Collection $organizations): array
    {
        $statusCounts = $organizations->groupBy(function ($org) {
            return $org->getTenantStatus()->value;
        })->map->count();
        
        $planCounts = $organizations->groupBy(function ($org) {
            return $org->plan?->value ?? 'unknown';
        })->map->count();
        
        return [
            'generated_at' => now(),
            'organizations' => $organizations,
            'total_count' => $organizations->count(),
            'status_distribution' => $statusCounts,
            'plan_distribution' => $planCounts,
            'summary_stats' => [
                'total_properties' => $organizations->sum(fn($org) => $org->properties()->count()),
                'total_users' => $organizations->sum(fn($org) => $org->users()->count()),
                'avg_properties_per_org' => $organizations->avg(fn($org) => $org->properties()->count()),
                'avg_users_per_org' => $organizations->avg(fn($org) => $org->users()->count()),
            ],
        ];
    }

    /**
     * Get subscriptions report data
     */
    private function getSubscriptionsReportData(Collection $subscriptions): array
    {
        $statusCounts = $subscriptions->groupBy(function ($sub) {
            return $sub->status?->value ?? 'unknown';
        })->map->count();
        
        $planCounts = $subscriptions->groupBy('plan_type')->map->count();
        
        $expiringCount = $subscriptions->filter(function ($sub) {
            return $sub->expires_at && $sub->expires_at->between(now(), now()->addDays(14));
        })->count();
        
        return [
            'generated_at' => now(),
            'subscriptions' => $subscriptions,
            'total_count' => $subscriptions->count(),
            'status_distribution' => $statusCounts,
            'plan_distribution' => $planCounts,
            'expiring_count' => $expiringCount,
            'summary_stats' => [
                'avg_days_until_expiry' => $subscriptions->avg(fn($sub) => $sub->daysUntilExpiry()),
                'total_max_properties' => $subscriptions->sum('max_properties'),
                'total_max_tenants' => $subscriptions->sum('max_tenants'),
            ],
        ];
    }

    /**
     * Get activity logs report data
     */
    private function getActivityLogsReportData(Collection $logs, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $actionCounts = $logs->groupBy('action')->map->count()->sortDesc();
        $userCounts = $logs->groupBy('user.name')->map->count()->sortDesc()->take(10);
        $organizationCounts = $logs->groupBy('organization.name')->map->count()->sortDesc()->take(10);
        
        return [
            'generated_at' => now(),
            'period' => [
                'start' => $startDate?->format('Y-m-d') ?? 'All time',
                'end' => $endDate?->format('Y-m-d') ?? 'Present',
            ],
            'logs' => $logs,
            'total_count' => $logs->count(),
            'action_distribution' => $actionCounts,
            'top_users' => $userCounts,
            'top_organizations' => $organizationCounts,
            'daily_activity' => $this->getDailyActivityChart($logs),
        ];
    }

    /**
     * Get daily activity chart data
     */
    private function getDailyActivityChart(Collection $logs): array
    {
        return $logs->groupBy(function ($log) {
            return $log->created_at->format('Y-m-d');
        })->map->count()->sortKeys()->toArray();
    }

    /**
     * Build activity logs query with date filtering
     */
    private function buildActivityLogsQuery(Builder $query = null, ?Carbon $startDate = null, ?Carbon $endDate = null): Builder
    {
        $logsQuery = $query ?: OrganizationActivityLog::with(['organization', 'user']);
        
        if ($startDate) {
            $logsQuery->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $logsQuery->where('created_at', '<=', $endDate);
        }
        
        return $logsQuery->orderBy('created_at', 'desc');
    }
}