<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\User;
use App\Notifications\AuditAnomalyDetectedNotification;
use App\Notifications\ComplianceIssueNotification;
use App\ValueObjects\Audit\AuditReportData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Audit Alert System
 * 
 * Monitors audit data for anomalies and compliance issues,
 * sending alerts to appropriate users when thresholds are exceeded.
 */
final readonly class AuditAlertSystem
{
    public function __construct(
        private UniversalServiceAuditReporter $auditReporter,
    ) {}

    /**
     * Process audit alerts for a tenant.
     */
    public function processAlerts(int $tenantId): void
    {
        try {
            $report = $this->auditReporter->generateReport(
                tenantId: $tenantId,
                startDate: now()->subHours(24), // Last 24 hours
                endDate: now(),
            );

            $this->checkCriticalAnomalies($tenantId, $report);
            $this->checkComplianceIssues($tenantId, $report);
            $this->checkPerformanceThresholds($tenantId, $report);
            $this->checkChangeFrequency($tenantId, $report);

        } catch (\Exception $e) {
            Log::error('Audit alert processing failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Check for critical anomalies that require immediate attention.
     */
    private function checkCriticalAnomalies(int $tenantId, AuditReportData $report): void
    {
        $criticalAnomalies = $report->getCriticalAnomalies();
        
        if (empty($criticalAnomalies)) {
            return;
        }

        $alertKey = "critical_anomaly_alert_{$tenantId}_" . md5(serialize($criticalAnomalies));
        
        // Prevent duplicate alerts within 1 hour
        if (Cache::has($alertKey)) {
            return;
        }

        Cache::put($alertKey, true, 3600); // 1 hour

        $adminUsers = $this->getAdminUsers($tenantId);
        
        foreach ($criticalAnomalies as $anomaly) {
            Notification::send($adminUsers, new AuditAnomalyDetectedNotification(
                tenantId: $tenantId,
                anomalyType: $anomaly['type'],
                severity: $anomaly['severity'],
                description: $anomaly['description'],
                details: $anomaly['details'] ?? [],
                detectedAt: $anomaly['detected_at'],
            ));

            Log::warning('Critical audit anomaly detected', [
                'tenant_id' => $tenantId,
                'anomaly_type' => $anomaly['type'],
                'severity' => $anomaly['severity'],
                'description' => $anomaly['description'],
            ]);
        }
    }

    /**
     * Check for compliance issues.
     */
    private function checkComplianceIssues(int $tenantId, AuditReportData $report): void
    {
        $compliance = $report->complianceStatus;
        
        // Alert if overall compliance score drops below 80%
        if ($compliance->overallScore < 80) {
            $alertKey = "compliance_alert_{$tenantId}_" . date('Y-m-d-H');
            
            if (Cache::has($alertKey)) {
                return;
            }

            Cache::put($alertKey, true, 3600); // 1 hour

            $adminUsers = $this->getAdminUsers($tenantId);
            
            Notification::send($adminUsers, new ComplianceIssueNotification(
                tenantId: $tenantId,
                overallScore: $compliance->overallScore,
                failingCategories: $this->getFailingComplianceCategories($compliance),
                recommendations: $compliance->recommendations,
            ));

            Log::warning('Compliance score below threshold', [
                'tenant_id' => $tenantId,
                'compliance_score' => $compliance->overallScore,
                'threshold' => 80,
            ]);
        }
    }

    /**
     * Check performance thresholds.
     */
    private function checkPerformanceThresholds(int $tenantId, AuditReportData $report): void
    {
        $performance = $report->performanceMetrics;
        $overallScore = $performance->getOverallScore();
        
        // Alert if performance score drops below 70%
        if ($overallScore < 70) {
            $alertKey = "performance_alert_{$tenantId}_" . date('Y-m-d-H');
            
            if (Cache::has($alertKey)) {
                return;
            }

            Cache::put($alertKey, true, 3600); // 1 hour

            Log::warning('System performance below threshold', [
                'tenant_id' => $tenantId,
                'performance_score' => $overallScore,
                'threshold' => 70,
                'billing_performance' => $performance->getBillingPerformanceScore(),
                'system_response' => $performance->getSystemResponseScore(),
                'error_rate' => $performance->getErrorRateScore(),
            ]);
        }
    }

    /**
     * Check change frequency for unusual patterns.
     */
    private function checkChangeFrequency(int $tenantId, AuditReportData $report): void
    {
        $summary = $report->summary;
        $changesPerDay = $summary->getChangesPerDay();
        
        // Alert if changes per day exceed 100 (configurable threshold)
        if ($changesPerDay > 100) {
            $alertKey = "high_frequency_alert_{$tenantId}_" . date('Y-m-d');
            
            if (Cache::has($alertKey)) {
                return;
            }

            Cache::put($alertKey, true, 86400); // 24 hours

            Log::info('High change frequency detected', [
                'tenant_id' => $tenantId,
                'changes_per_day' => $changesPerDay,
                'threshold' => 100,
                'total_changes' => $summary->totalChanges,
                'user_changes' => $summary->userChanges,
                'system_changes' => $summary->systemChanges,
            ]);
        }
    }

    /**
     * Get admin users for a tenant.
     */
    private function getAdminUsers(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereHas('teams', function ($query) use ($tenantId) {
            $query->where('teams.id', $tenantId);
        })
        ->whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'superadmin']);
        })
        ->get();
    }

    /**
     * Get failing compliance categories.
     */
    private function getFailingComplianceCategories($compliance): array
    {
        $failing = [];
        
        $categories = [
            'audit_trail' => $compliance->auditTrailCompleteness,
            'data_retention' => $compliance->dataRetentionCompliance,
            'regulatory' => $compliance->regulatoryCompliance,
            'security' => $compliance->securityCompliance,
            'data_quality' => $compliance->dataQualityCompliance,
        ];
        
        foreach ($categories as $name => $category) {
            if (($category['score'] ?? 0) < 80) {
                $failing[] = [
                    'category' => $name,
                    'score' => $category['score'] ?? 0,
                    'issues' => $category['issues'] ?? [],
                ];
            }
        }
        
        return $failing;
    }

    /**
     * Process alerts for all tenants (for scheduled execution).
     */
    public function processAllTenantAlerts(): void
    {
        // Get all active tenant IDs
        $tenantIds = \App\Models\Team::pluck('id');
        
        foreach ($tenantIds as $tenantId) {
            $this->processAlerts($tenantId);
        }
    }

    /**
     * Get alert configuration for a tenant.
     */
    public function getAlertConfiguration(int $tenantId): array
    {
        return Cache::remember("alert_config_{$tenantId}", 3600, function () {
            return [
                'critical_anomaly_threshold' => 1, // Alert on any critical anomaly
                'compliance_threshold' => 80, // Alert if compliance drops below 80%
                'performance_threshold' => 70, // Alert if performance drops below 70%
                'change_frequency_threshold' => 100, // Alert if changes per day exceed 100
                'alert_cooldown_hours' => 1, // Minimum hours between similar alerts
                'notification_channels' => ['mail', 'database'], // Available: mail, database, slack
            ];
        });
    }

    /**
     * Update alert configuration for a tenant.
     */
    public function updateAlertConfiguration(int $tenantId, array $config): void
    {
        Cache::put("alert_config_{$tenantId}", $config, 86400); // 24 hours
        
        Log::info('Alert configuration updated', [
            'tenant_id' => $tenantId,
            'config' => $config,
        ]);
    }
}