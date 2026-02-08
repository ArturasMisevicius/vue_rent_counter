<?php

declare(strict_types=1);

namespace App\ValueObjects\Audit;

use Carbon\Carbon;

/**
 * Compliance Report Value Object
 * 
 * Represents a comprehensive compliance report for utility services
 * with regulatory assessment, data quality metrics, and recommendations.
 */
final readonly class ComplianceReport
{
    public function __construct(
        public int $tenantId,
        public array $reportPeriod,
        public array $utilityTypes,
        public array $executiveSummary,
        public array $regulatoryCompliance,
        public array $dataRetentionCompliance,
        public array $auditTrailCompleteness,
        public array $securityCompliance,
        public array $dataQualityAssessment,
        public array $complianceGaps,
        public array $recommendations,
        public array $actionPlan,
        public Carbon $generatedAt,
    ) {}

    /**
     * Get overall compliance score from executive summary.
     */
    public function getOverallScore(): float
    {
        return $this->executiveSummary['overall_compliance_score'] ?? 0.0;
    }

    /**
     * Get compliance grade from executive summary.
     */
    public function getComplianceGrade(): string
    {
        return $this->executiveSummary['compliance_grade'] ?? 'F';
    }

    /**
     * Get critical issues count.
     */
    public function getCriticalIssuesCount(): int
    {
        return $this->executiveSummary['critical_issues_found'] ?? 0;
    }

    /**
     * Get high priority compliance gaps.
     */
    public function getHighPriorityGaps(): array
    {
        return array_filter($this->complianceGaps, function ($gap) {
            return ($gap['severity'] ?? '') === 'high';
        });
    }

    /**
     * Get critical priority compliance gaps.
     */
    public function getCriticalGaps(): array
    {
        return array_filter($this->complianceGaps, function ($gap) {
            return ($gap['severity'] ?? '') === 'critical';
        });
    }

    /**
     * Get high priority recommendations.
     */
    public function getHighPriorityRecommendations(): array
    {
        return array_filter($this->recommendations, function ($recommendation) {
            return ($recommendation['priority'] ?? '') === 'high';
        });
    }

    /**
     * Get immediate actions from action plan.
     */
    public function getImmediateActions(): array
    {
        return $this->actionPlan['immediate_actions'] ?? [];
    }

    /**
     * Check if report indicates compliance.
     */
    public function isCompliant(): bool
    {
        return $this->getOverallScore() >= 80.0 && $this->getCriticalIssuesCount() === 0;
    }

    /**
     * Get report period as formatted string.
     */
    public function getReportPeriodString(): string
    {
        [$startDate, $endDate] = $this->reportPeriod;
        return $startDate->format('M j, Y') . ' - ' . $endDate->format('M j, Y');
    }

    /**
     * Get utility types as formatted string.
     */
    public function getUtilityTypesString(): string
    {
        return empty($this->utilityTypes) ? 'All Utilities' : implode(', ', $this->utilityTypes);
    }

    /**
     * Get compliance status summary.
     */
    public function getComplianceStatusSummary(): array
    {
        return [
            'overall_score' => $this->getOverallScore(),
            'grade' => $this->getComplianceGrade(),
            'is_compliant' => $this->isCompliant(),
            'critical_issues' => $this->getCriticalIssuesCount(),
            'high_priority_gaps' => count($this->getHighPriorityGaps()),
            'immediate_actions' => count($this->getImmediateActions()),
        ];
    }

    /**
     * Get regulatory compliance breakdown.
     */
    public function getRegulatoryComplianceBreakdown(): array
    {
        $breakdown = [];
        foreach ($this->regulatoryCompliance as $area => $compliance) {
            if (is_array($compliance) && isset($compliance['score'])) {
                $breakdown[$area] = [
                    'score' => $compliance['score'],
                    'status' => $compliance['score'] >= 80 ? 'compliant' : 'needs_attention',
                ];
            }
        }
        return $breakdown;
    }

    /**
     * Get data quality metrics summary.
     */
    public function getDataQualityMetrics(): array
    {
        return [
            'overall_score' => $this->dataQualityAssessment['overall_score'] ?? 0,
            'completeness' => $this->dataQualityAssessment['completeness_score'] ?? 0,
            'accuracy' => $this->dataQualityAssessment['accuracy_score'] ?? 0,
            'consistency' => $this->dataQualityAssessment['consistency_score'] ?? 0,
            'timeliness' => $this->dataQualityAssessment['timeliness_score'] ?? 0,
        ];
    }

    /**
     * Convert to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'report_period' => [
                'start' => $this->reportPeriod[0]->toISOString(),
                'end' => $this->reportPeriod[1]->toISOString(),
            ],
            'utility_types' => $this->utilityTypes,
            'executive_summary' => $this->executiveSummary,
            'regulatory_compliance' => $this->regulatoryCompliance,
            'data_retention_compliance' => $this->dataRetentionCompliance,
            'audit_trail_completeness' => $this->auditTrailCompleteness,
            'security_compliance' => $this->securityCompliance,
            'data_quality_assessment' => $this->dataQualityAssessment,
            'compliance_gaps' => $this->complianceGaps,
            'recommendations' => $this->recommendations,
            'action_plan' => $this->actionPlan,
            'generated_at' => $this->generatedAt->toISOString(),
            'compliance_status_summary' => $this->getComplianceStatusSummary(),
        ];
    }
}