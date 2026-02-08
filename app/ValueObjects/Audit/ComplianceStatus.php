<?php

declare(strict_types=1);

namespace App\ValueObjects\Audit;

use Carbon\Carbon;

/**
 * Compliance Status Value Object
 * 
 * Contains comprehensive compliance status for regulatory requirements
 * including audit trail completeness, data retention, and security compliance.
 */
final readonly class ComplianceStatus
{
    public function __construct(
        public float $overallScore,
        public array $auditTrailCompleteness,
        public array $dataRetentionCompliance,
        public array $regulatoryCompliance,
        public array $securityCompliance,
        public array $dataQualityCompliance,
        public array $violations,
        public array $recommendations,
        public Carbon $assessedAt,
    ) {}

    /**
     * Get compliance status as array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'overall_score' => $this->overallScore,
            'overall_status' => $this->getOverallStatus(),
            'compliance_grade' => $this->getComplianceGrade(),
            'audit_trail_completeness' => $this->auditTrailCompleteness,
            'data_retention_compliance' => $this->dataRetentionCompliance,
            'regulatory_compliance' => $this->regulatoryCompliance,
            'security_compliance' => $this->securityCompliance,
            'data_quality_compliance' => $this->dataQualityCompliance,
            'violations' => $this->violations,
            'recommendations' => $this->recommendations,
            'critical_violations_count' => $this->getCriticalViolationsCount(),
            'high_violations_count' => $this->getHighViolationsCount(),
            'total_violations_count' => count($this->violations),
            'recommendations_count' => count($this->recommendations),
            'assessed_at' => $this->assessedAt->toISOString(),
        ];
    }

    /**
     * Get overall compliance status.
     */
    public function getOverallStatus(): string
    {
        return match (true) {
            $this->overallScore >= 95 => 'compliant',
            $this->overallScore >= 80 => 'warning',
            default => 'non_compliant',
        };
    }

    /**
     * Get compliance grade (A-F).
     */
    public function getComplianceGrade(): string
    {
        return match (true) {
            $this->overallScore >= 95 => 'A',
            $this->overallScore >= 90 => 'B',
            $this->overallScore >= 80 => 'C',
            $this->overallScore >= 70 => 'D',
            default => 'F',
        };
    }

    /**
     * Check if compliance status is acceptable.
     */
    public function isCompliant(): bool
    {
        return $this->overallScore >= 80 && $this->getCriticalViolationsCount() === 0;
    }

    /**
     * Get count of critical violations.
     */
    public function getCriticalViolationsCount(): int
    {
        return count(array_filter($this->violations, fn($v) => $v['severity'] === 'critical'));
    }

    /**
     * Get count of high severity violations.
     */
    public function getHighViolationsCount(): int
    {
        return count(array_filter($this->violations, fn($v) => $v['severity'] === 'high'));
    }

    /**
     * Get critical violations that require immediate attention.
     */
    public function getCriticalViolations(): array
    {
        return array_filter($this->violations, fn($v) => $v['severity'] === 'critical');
    }

    /**
     * Get high priority recommendations.
     */
    public function getHighPriorityRecommendations(): array
    {
        return array_filter($this->recommendations, fn($r) => $r['priority'] === 'critical' || $r['priority'] === 'high');
    }

    /**
     * Get compliance areas that need improvement.
     */
    public function getAreasNeedingImprovement(): array
    {
        $areas = [];
        
        if (($this->auditTrailCompleteness['score'] ?? 100) < 90) {
            $areas[] = [
                'area' => 'audit_trail_completeness',
                'score' => $this->auditTrailCompleteness['score'] ?? 0,
                'status' => $this->auditTrailCompleteness['status'] ?? 'unknown',
                'issues' => $this->auditTrailCompleteness['issues'] ?? [],
            ];
        }
        
        if (($this->dataRetentionCompliance['score'] ?? 100) < 90) {
            $areas[] = [
                'area' => 'data_retention_compliance',
                'score' => $this->dataRetentionCompliance['score'] ?? 0,
                'status' => $this->dataRetentionCompliance['status'] ?? 'unknown',
                'issues' => $this->dataRetentionCompliance['issues'] ?? [],
            ];
        }
        
        if (($this->regulatoryCompliance['score'] ?? 100) < 90) {
            $areas[] = [
                'area' => 'regulatory_compliance',
                'score' => $this->regulatoryCompliance['score'] ?? 0,
                'status' => $this->regulatoryCompliance['status'] ?? 'unknown',
                'issues' => $this->regulatoryCompliance['issues'] ?? [],
            ];
        }
        
        if (($this->securityCompliance['score'] ?? 100) < 90) {
            $areas[] = [
                'area' => 'security_compliance',
                'score' => $this->securityCompliance['score'] ?? 0,
                'status' => $this->securityCompliance['status'] ?? 'unknown',
                'issues' => $this->securityCompliance['issues'] ?? [],
            ];
        }
        
        if (($this->dataQualityCompliance['score'] ?? 100) < 90) {
            $areas[] = [
                'area' => 'data_quality_compliance',
                'score' => $this->dataQualityCompliance['score'] ?? 0,
                'status' => $this->dataQualityCompliance['status'] ?? 'unknown',
                'issues' => $this->dataQualityCompliance['issues'] ?? [],
            ];
        }
        
        return $areas;
    }

    /**
     * Get compliance summary for dashboard display.
     */
    public function getSummary(): array
    {
        return [
            'overall_score' => $this->overallScore,
            'status' => $this->getOverallStatus(),
            'grade' => $this->getComplianceGrade(),
            'critical_issues' => $this->getCriticalViolationsCount(),
            'total_violations' => count($this->violations),
            'areas_needing_improvement' => count($this->getAreasNeedingImprovement()),
            'is_compliant' => $this->isCompliant(),
        ];
    }

    /**
     * Get next assessment due date (typically 30 days).
     */
    public function getNextAssessmentDue(): Carbon
    {
        return $this->assessedAt->addDays(30);
    }

    /**
     * Check if assessment is overdue.
     */
    public function isAssessmentOverdue(): bool
    {
        return now()->gt($this->getNextAssessmentDue());
    }

    /**
     * Get compliance trend (requires historical data).
     */
    public function getComplianceTrend(): string
    {
        // This would typically compare with previous assessments
        // For now, we'll provide a simplified trend based on current score
        
        return match (true) {
            $this->overallScore >= 95 => 'excellent',
            $this->overallScore >= 85 => 'improving',
            $this->overallScore >= 75 => 'stable',
            default => 'declining',
        };
    }

    /**
     * Get estimated time to achieve full compliance.
     */
    public function getEstimatedTimeToCompliance(): ?string
    {
        if ($this->isCompliant()) {
            return null; // Already compliant
        }
        
        $violationsCount = count($this->violations);
        $criticalCount = $this->getCriticalViolationsCount();
        
        // Estimate based on violation severity and count
        if ($criticalCount > 0) {
            return '1-2 weeks'; // Critical issues need immediate attention
        }
        
        if ($violationsCount > 10) {
            return '4-6 weeks'; // Many violations need systematic approach
        }
        
        if ($violationsCount > 5) {
            return '2-3 weeks'; // Moderate violations
        }
        
        return '1 week'; // Few violations, quick fixes
    }

    /**
     * Get compliance action plan.
     */
    public function getActionPlan(): array
    {
        $plan = [];
        
        // Immediate actions for critical violations
        $criticalViolations = $this->getCriticalViolations();
        if (!empty($criticalViolations)) {
            $plan[] = [
                'priority' => 'immediate',
                'timeframe' => '24-48 hours',
                'title' => 'Address Critical Violations',
                'description' => 'Resolve critical compliance violations immediately',
                'actions' => array_map(fn($v) => $v['description'], $criticalViolations),
            ];
        }
        
        // High priority recommendations
        $highPriorityRecs = $this->getHighPriorityRecommendations();
        if (!empty($highPriorityRecs)) {
            $plan[] = [
                'priority' => 'high',
                'timeframe' => '1-2 weeks',
                'title' => 'Implement High Priority Improvements',
                'description' => 'Address high priority compliance recommendations',
                'actions' => array_map(fn($r) => $r['title'], $highPriorityRecs),
            ];
        }
        
        // Areas needing improvement
        $improvementAreas = $this->getAreasNeedingImprovement();
        if (!empty($improvementAreas)) {
            $plan[] = [
                'priority' => 'medium',
                'timeframe' => '2-4 weeks',
                'title' => 'Strengthen Compliance Areas',
                'description' => 'Improve compliance in identified weak areas',
                'actions' => array_map(fn($a) => "Improve {$a['area']}", $improvementAreas),
            ];
        }
        
        return $plan;
    }
}