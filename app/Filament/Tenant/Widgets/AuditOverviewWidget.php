<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\Audit\UniversalServiceAuditReporter;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/**
 * Audit Overview Widget
 * 
 * Displays key audit metrics and compliance status for universal services.
 */
final class AuditOverviewWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected static ?int $sort = 1;

    public function __construct(
        private readonly UniversalServiceAuditReporter $auditReporter,
    ) {
        parent::__construct();
    }

    protected function getStats(): array
    {
        $cacheKey = 'audit_overview_' . auth()->user()->currentTeam->id;
        
        return Cache::remember($cacheKey, 300, function () {
            $report = $this->auditReporter->generateReport(
                tenantId: auth()->user()->currentTeam->id,
                startDate: now()->subDays(30),
                endDate: now(),
            );
            
            $summary = $report->summary;
            $compliance = $report->complianceStatus;
            $performance = $report->performanceMetrics;
            
            return [
                Stat::make(__('dashboard.audit.total_changes'), $summary->totalChanges)
                    ->description(__('dashboard.audit.last_30_days'))
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color($this->getChangesTrendColor($summary->getChangesPerDay()))
                    ->chart($this->getChangesChart()),
                
                Stat::make(__('dashboard.audit.compliance_score'), number_format($compliance->overallScore, 1) . '%')
                    ->description($this->getComplianceDescription($compliance->getOverallStatus()))
                    ->descriptionIcon($this->getComplianceIcon($compliance->getOverallStatus()))
                    ->color($this->getComplianceColor($compliance->overallScore))
                    ->chart($this->getComplianceChart($compliance)),
                
                Stat::make(__('dashboard.audit.performance_grade'), $performance->getPerformanceGrade())
                    ->description(__('dashboard.audit.system_performance'))
                    ->descriptionIcon('heroicon-m-cpu-chip')
                    ->color($this->getPerformanceColor($performance->getOverallScore()))
                    ->chart($this->getPerformanceChart($performance)),
                
                Stat::make(__('dashboard.audit.critical_issues'), count($report->getCriticalAnomalies()))
                    ->description(__('dashboard.audit.requires_attention'))
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color($this->getCriticalIssuesColor(count($report->getCriticalAnomalies())))
                    ->chart($this->getIssuesChart($report)),
            ];
        });
    }

    /**
     * Get color for changes trend.
     */
    private function getChangesTrendColor(float $changesPerDay): string
    {
        return match (true) {
            $changesPerDay > 50 => 'warning',
            $changesPerDay > 20 => 'success',
            default => 'gray',
        };
    }

    /**
     * Get compliance description.
     */
    private function getComplianceDescription(string $status): string
    {
        return match ($status) {
            'compliant' => __('dashboard.audit.fully_compliant'),
            'warning' => __('dashboard.audit.needs_attention'),
            'non_compliant' => __('dashboard.audit.non_compliant'),
            default => __('dashboard.audit.unknown_status'),
        };
    }

    /**
     * Get compliance icon.
     */
    private function getComplianceIcon(string $status): string
    {
        return match ($status) {
            'compliant' => 'heroicon-m-check-circle',
            'warning' => 'heroicon-m-exclamation-triangle',
            'non_compliant' => 'heroicon-m-x-circle',
            default => 'heroicon-m-question-mark-circle',
        };
    }

    /**
     * Get compliance color.
     */
    private function getComplianceColor(float $score): string
    {
        return match (true) {
            $score >= 95 => 'success',
            $score >= 80 => 'warning',
            default => 'danger',
        };
    }

    /**
     * Get performance color.
     */
    private function getPerformanceColor(float $score): string
    {
        return match (true) {
            $score >= 90 => 'success',
            $score >= 70 => 'warning',
            default => 'danger',
        };
    }

    /**
     * Get critical issues color.
     */
    private function getCriticalIssuesColor(int $count): string
    {
        return match (true) {
            $count === 0 => 'success',
            $count <= 2 => 'warning',
            default => 'danger',
        };
    }

    /**
     * Get changes chart data.
     */
    private function getChangesChart(): array
    {
        // Generate sample chart data for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = rand(5, 25);
        }
        return $data;
    }

    /**
     * Get compliance chart data.
     */
    private function getComplianceChart($compliance): array
    {
        return [
            $compliance->auditTrailCompleteness['score'] ?? 0,
            $compliance->dataRetentionCompliance['score'] ?? 0,
            $compliance->regulatoryCompliance['score'] ?? 0,
            $compliance->securityCompliance['score'] ?? 0,
            $compliance->dataQualityCompliance['score'] ?? 0,
        ];
    }

    /**
     * Get performance chart data.
     */
    private function getPerformanceChart($performance): array
    {
        return [
            $performance->getBillingPerformanceScore(),
            $performance->getSystemResponseScore(),
            $performance->getDataQualityScore(),
            $performance->getOperationalEfficiencyScore(),
            $performance->getErrorRateScore(),
        ];
    }

    /**
     * Get issues chart data.
     */
    private function getIssuesChart($report): array
    {
        $anomalies = $report->anomalies;
        $criticalCount = count(array_filter($anomalies, fn($a) => $a['severity'] === 'critical'));
        $highCount = count(array_filter($anomalies, fn($a) => $a['severity'] === 'high'));
        $mediumCount = count(array_filter($anomalies, fn($a) => $a['severity'] === 'medium'));
        $lowCount = count($anomalies) - $criticalCount - $highCount - $mediumCount;
        
        return [$criticalCount, $highCount, $mediumCount, $lowCount];
    }
}