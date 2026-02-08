<?php

declare(strict_types=1);

namespace App\ValueObjects\Audit;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Audit Report Data Value Object
 * 
 * Comprehensive audit report containing all audit information
 * for universal services including summary, changes, metrics, and compliance.
 */
final readonly class AuditReportData
{
    public function __construct(
        public AuditSummary $summary,
        public Collection $configurationChanges,
        public PerformanceMetrics $performanceMetrics,
        public ComplianceStatus $complianceStatus,
        public array $anomalies,
        public Carbon $generatedAt,
    ) {}

    /**
     * Get report as array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary->toArray(),
            'configuration_changes' => $this->configurationChanges->toArray(),
            'performance_metrics' => $this->performanceMetrics->toArray(),
            'compliance_status' => $this->complianceStatus->toArray(),
            'anomalies' => $this->anomalies,
            'generated_at' => $this->generatedAt->toISOString(),
        ];
    }

    /**
     * Get overall health score based on all metrics.
     */
    public function getOverallHealthScore(): float
    {
        $scores = [
            $this->complianceStatus->overallScore,
            $this->performanceMetrics->getOverallScore(),
            $this->getAnomalyScore(),
        ];
        
        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Get anomaly score (lower anomalies = higher score).
     */
    private function getAnomalyScore(): float
    {
        $anomalyCount = count($this->anomalies);
        $criticalAnomalies = count(array_filter($this->anomalies, fn($a) => $a['severity'] === 'critical'));
        $highAnomalies = count(array_filter($this->anomalies, fn($a) => $a['severity'] === 'high'));
        
        // Start with 100 and deduct points for anomalies
        $score = 100;
        $score -= $criticalAnomalies * 20; // Critical anomalies cost 20 points each
        $score -= $highAnomalies * 10; // High anomalies cost 10 points each
        $score -= ($anomalyCount - $criticalAnomalies - $highAnomalies) * 5; // Other anomalies cost 5 points each
        
        return max(0, $score);
    }

    /**
     * Check if report indicates system health issues.
     */
    public function hasHealthIssues(): bool
    {
        return $this->getOverallHealthScore() < 80 || 
               $this->complianceStatus->overallScore < 85 ||
               count($this->getCriticalAnomalies()) > 0;
    }

    /**
     * Get critical anomalies that require immediate attention.
     */
    public function getCriticalAnomalies(): array
    {
        return array_filter($this->anomalies, fn($a) => $a['severity'] === 'critical');
    }

    /**
     * Get recommendations based on report findings.
     */
    public function getRecommendations(): array
    {
        $recommendations = [];
        
        // Add compliance recommendations
        $recommendations = array_merge($recommendations, $this->complianceStatus->recommendations);
        
        // Add performance recommendations
        if ($this->performanceMetrics->getOverallScore() < 80) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'performance',
                'title' => 'Improve System Performance',
                'description' => 'System performance metrics indicate optimization opportunities',
                'action_items' => [
                    'Review and optimize slow queries',
                    'Implement additional caching strategies',
                    'Monitor resource utilization patterns',
                ],
            ];
        }
        
        // Add anomaly-based recommendations
        if (count($this->anomalies) > 5) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'monitoring',
                'title' => 'Enhance Monitoring and Alerting',
                'description' => 'Multiple anomalies detected, consider improving monitoring',
                'action_items' => [
                    'Implement proactive anomaly detection',
                    'Set up automated alerting for critical issues',
                    'Review and tune anomaly detection thresholds',
                ],
            ];
        }
        
        return $recommendations;
    }
}