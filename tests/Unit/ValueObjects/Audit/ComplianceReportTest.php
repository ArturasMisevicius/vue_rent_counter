<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects\Audit;

use App\ValueObjects\Audit\ComplianceReport;
use Carbon\Carbon;
use Tests\TestCase;

final class ComplianceReportTest extends TestCase
{
    private function createSampleReport(array $overrides = []): ComplianceReport
    {
        $defaults = [
            'tenantId' => 123,
            'reportPeriod' => [
                Carbon::parse('2024-01-01'),
                Carbon::parse('2024-01-31'),
            ],
            'utilityTypes' => ['electricity', 'water', 'heating'],
            'executiveSummary' => [
                'overall_compliance_score' => 85.5,
                'compliance_grade' => 'B',
                'critical_issues_found' => 2,
                'total_recommendations' => 5,
            ],
            'regulatoryCompliance' => [
                'gdpr' => ['score' => 90, 'status' => 'compliant'],
                'financial_reporting' => ['score' => 80, 'status' => 'compliant'],
                'data_protection' => ['score' => 75, 'status' => 'needs_attention'],
            ],
            'dataRetentionCompliance' => [
                'score' => 88.0,
                'retention_days' => 2555,
                'required_days' => 2555,
                'status' => 'compliant',
            ],
            'auditTrailCompleteness' => [
                'score' => 95.0,
                'total_operations' => 1000,
                'audited_operations' => 950,
                'status' => 'compliant',
            ],
            'securityCompliance' => [
                'score' => 82.0,
                'access_control' => 85,
                'data_encryption' => 90,
                'audit_logging' => 70,
                'status' => 'compliant',
            ],
            'dataQualityAssessment' => [
                'overall_score' => 92.0,
                'completeness_score' => 95,
                'accuracy_score' => 90,
                'consistency_score' => 88,
                'timeliness_score' => 94,
            ],
            'complianceGaps' => [
                [
                    'area' => 'data_protection',
                    'severity' => 'high',
                    'description' => 'Missing encryption for sensitive fields',
                    'impact' => 'Data breach risk',
                ],
                [
                    'area' => 'audit_logging',
                    'severity' => 'critical',
                    'description' => 'Incomplete audit trail for user actions',
                    'impact' => 'Regulatory non-compliance',
                ],
                [
                    'area' => 'access_control',
                    'severity' => 'medium',
                    'description' => 'Weak password policy',
                    'impact' => 'Security vulnerability',
                ],
            ],
            'recommendations' => [
                [
                    'priority' => 'high',
                    'category' => 'security',
                    'description' => 'Implement field-level encryption',
                    'timeline' => '30 days',
                ],
                [
                    'priority' => 'medium',
                    'category' => 'audit',
                    'description' => 'Enhance audit logging coverage',
                    'timeline' => '60 days',
                ],
                [
                    'priority' => 'low',
                    'category' => 'documentation',
                    'description' => 'Update compliance documentation',
                    'timeline' => '90 days',
                ],
            ],
            'actionPlan' => [
                'immediate_actions' => [
                    'Enable audit logging for all user actions',
                    'Review data encryption implementation',
                ],
                'short_term_actions' => [
                    'Implement field-level encryption',
                    'Update password policy',
                ],
                'long_term_actions' => [
                    'Comprehensive security audit',
                    'Staff training on compliance',
                ],
            ],
            'generatedAt' => Carbon::parse('2024-02-01 10:00:00'),
        ];

        return new ComplianceReport(...array_merge($defaults, $overrides));
    }

    public function test_creates_compliance_report_with_all_properties(): void
    {
        $report = $this->createSampleReport();

        expect($report->tenantId)->toBe(123);
        expect($report->reportPeriod)->toHaveCount(2);
        expect($report->utilityTypes)->toBe(['electricity', 'water', 'heating']);
        expect($report->executiveSummary)->toBeArray();
        expect($report->generatedAt)->toBeInstanceOf(Carbon::class);
    }

    public function test_gets_overall_score_from_executive_summary(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => ['overall_compliance_score' => 92.5],
        ]);

        expect($report->getOverallScore())->toBe(92.5);
    }

    public function test_returns_zero_score_when_missing_from_executive_summary(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => [],
        ]);

        expect($report->getOverallScore())->toBe(0.0);
    }

    public function test_gets_compliance_grade_from_executive_summary(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => ['compliance_grade' => 'A+'],
        ]);

        expect($report->getComplianceGrade())->toBe('A+');
    }

    public function test_returns_f_grade_when_missing_from_executive_summary(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => [],
        ]);

        expect($report->getComplianceGrade())->toBe('F');
    }

    public function test_gets_critical_issues_count_from_executive_summary(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => ['critical_issues_found' => 7],
        ]);

        expect($report->getCriticalIssuesCount())->toBe(7);
    }

    public function test_returns_zero_critical_issues_when_missing(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => [],
        ]);

        expect($report->getCriticalIssuesCount())->toBe(0);
    }

    public function test_filters_high_priority_gaps(): void
    {
        $report = $this->createSampleReport();

        $highPriorityGaps = $report->getHighPriorityGaps();

        expect($highPriorityGaps)->toHaveCount(1);
        expect($highPriorityGaps[0]['severity'])->toBe('high');
        expect($highPriorityGaps[0]['area'])->toBe('data_protection');
    }

    public function test_filters_critical_gaps(): void
    {
        $report = $this->createSampleReport();

        $criticalGaps = $report->getCriticalGaps();

        expect($criticalGaps)->toHaveCount(1);
        expect($criticalGaps[0]['severity'])->toBe('critical');
        expect($criticalGaps[0]['area'])->toBe('audit_logging');
    }

    public function test_returns_empty_array_when_no_gaps_match_severity(): void
    {
        $report = $this->createSampleReport([
            'complianceGaps' => [
                ['severity' => 'low', 'area' => 'documentation'],
                ['severity' => 'medium', 'area' => 'training'],
            ],
        ]);

        expect($report->getHighPriorityGaps())->toBeEmpty();
        expect($report->getCriticalGaps())->toBeEmpty();
    }

    public function test_handles_gaps_with_missing_severity_key(): void
    {
        $report = $this->createSampleReport([
            'complianceGaps' => [
                ['area' => 'missing_severity'],
                ['severity' => 'high', 'area' => 'valid_gap'],
            ],
        ]);

        $highPriorityGaps = $report->getHighPriorityGaps();

        expect($highPriorityGaps)->toHaveCount(1);
        expect($highPriorityGaps[0]['area'])->toBe('valid_gap');
    }

    public function test_filters_high_priority_recommendations(): void
    {
        $report = $this->createSampleReport();

        $highPriorityRecommendations = $report->getHighPriorityRecommendations();

        expect($highPriorityRecommendations)->toHaveCount(1);
        expect($highPriorityRecommendations[0]['priority'])->toBe('high');
        expect($highPriorityRecommendations[0]['category'])->toBe('security');
    }

    public function test_returns_empty_array_when_no_high_priority_recommendations(): void
    {
        $report = $this->createSampleReport([
            'recommendations' => [
                ['priority' => 'medium', 'category' => 'audit'],
                ['priority' => 'low', 'category' => 'documentation'],
            ],
        ]);

        expect($report->getHighPriorityRecommendations())->toBeEmpty();
    }

    public function test_handles_recommendations_with_missing_priority_key(): void
    {
        $report = $this->createSampleReport([
            'recommendations' => [
                ['category' => 'missing_priority'],
                ['priority' => 'high', 'category' => 'valid_recommendation'],
            ],
        ]);

        $highPriorityRecommendations = $report->getHighPriorityRecommendations();

        expect($highPriorityRecommendations)->toHaveCount(1);
        expect($highPriorityRecommendations[0]['category'])->toBe('valid_recommendation');
    }

    public function test_gets_immediate_actions_from_action_plan(): void
    {
        $report = $this->createSampleReport();

        $immediateActions = $report->getImmediateActions();

        expect($immediateActions)->toHaveCount(2);
        expect($immediateActions[0])->toBe('Enable audit logging for all user actions');
        expect($immediateActions[1])->toBe('Review data encryption implementation');
    }

    public function test_returns_empty_array_when_no_immediate_actions(): void
    {
        $report = $this->createSampleReport([
            'actionPlan' => [],
        ]);

        expect($report->getImmediateActions())->toBeEmpty();
    }

    public function test_determines_compliance_when_score_high_and_no_critical_issues(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => [
                'overall_compliance_score' => 85.0,
                'critical_issues_found' => 0,
            ],
        ]);

        expect($report->isCompliant())->toBeTrue();
    }

    public function test_determines_non_compliance_when_score_low(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => [
                'overall_compliance_score' => 75.0,
                'critical_issues_found' => 0,
            ],
        ]);

        expect($report->isCompliant())->toBeFalse();
    }

    public function test_determines_non_compliance_when_critical_issues_exist(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => [
                'overall_compliance_score' => 90.0,
                'critical_issues_found' => 1,
            ],
        ]);

        expect($report->isCompliant())->toBeFalse();
    }

    public function test_determines_non_compliance_when_both_score_low_and_critical_issues(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => [
                'overall_compliance_score' => 70.0,
                'critical_issues_found' => 3,
            ],
        ]);

        expect($report->isCompliant())->toBeFalse();
    }

    public function test_formats_report_period_string(): void
    {
        $report = $this->createSampleReport([
            'reportPeriod' => [
                Carbon::parse('2024-03-15'),
                Carbon::parse('2024-04-14'),
            ],
        ]);

        expect($report->getReportPeriodString())->toBe('Mar 15, 2024 - Apr 14, 2024');
    }

    public function test_formats_utility_types_string_with_multiple_types(): void
    {
        $report = $this->createSampleReport([
            'utilityTypes' => ['electricity', 'gas', 'water'],
        ]);

        expect($report->getUtilityTypesString())->toBe('electricity, gas, water');
    }

    public function test_formats_utility_types_string_with_single_type(): void
    {
        $report = $this->createSampleReport([
            'utilityTypes' => ['heating'],
        ]);

        expect($report->getUtilityTypesString())->toBe('heating');
    }

    public function test_returns_all_utilities_when_empty_utility_types(): void
    {
        $report = $this->createSampleReport([
            'utilityTypes' => [],
        ]);

        expect($report->getUtilityTypesString())->toBe('All Utilities');
    }

    public function test_generates_compliance_status_summary(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => [
                'overall_compliance_score' => 88.5,
                'compliance_grade' => 'B+',
                'critical_issues_found' => 1,
            ],
        ]);

        $summary = $report->getComplianceStatusSummary();

        expect($summary)->toBe([
            'overall_score' => 88.5,
            'grade' => 'B+',
            'is_compliant' => false, // Due to critical issue
            'critical_issues' => 1,
            'high_priority_gaps' => 1,
            'immediate_actions' => 2,
        ]);
    }

    public function test_generates_regulatory_compliance_breakdown(): void
    {
        $report = $this->createSampleReport();

        $breakdown = $report->getRegulatoryComplianceBreakdown();

        expect($breakdown)->toBe([
            'gdpr' => [
                'score' => 90,
                'status' => 'compliant',
            ],
            'financial_reporting' => [
                'score' => 80,
                'status' => 'compliant',
            ],
            'data_protection' => [
                'score' => 75,
                'status' => 'needs_attention',
            ],
        ]);
    }

    public function test_handles_regulatory_compliance_with_non_array_values(): void
    {
        $report = $this->createSampleReport([
            'regulatoryCompliance' => [
                'gdpr' => ['score' => 90],
                'invalid_entry' => 'not_an_array',
                'missing_score' => ['status' => 'compliant'],
            ],
        ]);

        $breakdown = $report->getRegulatoryComplianceBreakdown();

        expect($breakdown)->toBe([
            'gdpr' => [
                'score' => 90,
                'status' => 'compliant',
            ],
        ]);
    }

    public function test_generates_data_quality_metrics(): void
    {
        $report = $this->createSampleReport();

        $metrics = $report->getDataQualityMetrics();

        expect($metrics)->toBe([
            'overall_score' => 92.0,
            'completeness' => 95,
            'accuracy' => 90,
            'consistency' => 88,
            'timeliness' => 94,
        ]);
    }

    public function test_handles_missing_data_quality_metrics(): void
    {
        $report = $this->createSampleReport([
            'dataQualityAssessment' => [],
        ]);

        $metrics = $report->getDataQualityMetrics();

        expect($metrics)->toBe([
            'overall_score' => 0,
            'completeness' => 0,
            'accuracy' => 0,
            'consistency' => 0,
            'timeliness' => 0,
        ]);
    }

    public function test_converts_to_array_with_all_data(): void
    {
        $report = $this->createSampleReport();

        $array = $report->toArray();

        expect($array)->toHaveKey('tenant_id', 123);
        expect($array)->toHaveKey('report_period');
        expect($array['report_period'])->toHaveKey('start');
        expect($array['report_period'])->toHaveKey('end');
        expect($array)->toHaveKey('utility_types');
        expect($array)->toHaveKey('executive_summary');
        expect($array)->toHaveKey('regulatory_compliance');
        expect($array)->toHaveKey('data_retention_compliance');
        expect($array)->toHaveKey('audit_trail_completeness');
        expect($array)->toHaveKey('security_compliance');
        expect($array)->toHaveKey('data_quality_assessment');
        expect($array)->toHaveKey('compliance_gaps');
        expect($array)->toHaveKey('recommendations');
        expect($array)->toHaveKey('action_plan');
        expect($array)->toHaveKey('generated_at');
        expect($array)->toHaveKey('compliance_status_summary');
    }

    public function test_converts_dates_to_iso_strings_in_array(): void
    {
        $startDate = Carbon::parse('2024-01-15 09:30:00');
        $endDate = Carbon::parse('2024-02-14 17:45:00');
        $generatedAt = Carbon::parse('2024-02-15 10:15:30');

        $report = $this->createSampleReport([
            'reportPeriod' => [$startDate, $endDate],
            'generatedAt' => $generatedAt,
        ]);

        $array = $report->toArray();

        expect($array['report_period']['start'])->toBe($startDate->toISOString());
        expect($array['report_period']['end'])->toBe($endDate->toISOString());
        expect($array['generated_at'])->toBe($generatedAt->toISOString());
    }

    public function test_includes_compliance_status_summary_in_array(): void
    {
        $report = $this->createSampleReport([
            'executiveSummary' => [
                'overall_compliance_score' => 95.0,
                'compliance_grade' => 'A',
                'critical_issues_found' => 0,
            ],
        ]);

        $array = $report->toArray();

        expect($array['compliance_status_summary'])->toBe([
            'overall_score' => 95.0,
            'grade' => 'A',
            'is_compliant' => true,
            'critical_issues' => 0,
            'high_priority_gaps' => 1,
            'immediate_actions' => 2,
        ]);
    }

    public function test_handles_empty_compliance_gaps_array(): void
    {
        $report = $this->createSampleReport([
            'complianceGaps' => [],
        ]);

        expect($report->getHighPriorityGaps())->toBeEmpty();
        expect($report->getCriticalGaps())->toBeEmpty();
    }

    public function test_handles_empty_recommendations_array(): void
    {
        $report = $this->createSampleReport([
            'recommendations' => [],
        ]);

        expect($report->getHighPriorityRecommendations())->toBeEmpty();
    }

    public function test_handles_empty_action_plan(): void
    {
        $report = $this->createSampleReport([
            'actionPlan' => [],
        ]);

        expect($report->getImmediateActions())->toBeEmpty();
    }

    public function test_compliance_threshold_boundary_conditions(): void
    {
        // Test exactly at threshold (80.0)
        $reportAtThreshold = $this->createSampleReport([
            'executiveSummary' => [
                'overall_compliance_score' => 80.0,
                'critical_issues_found' => 0,
            ],
        ]);

        expect($reportAtThreshold->isCompliant())->toBeTrue();

        // Test just below threshold (79.9)
        $reportBelowThreshold = $this->createSampleReport([
            'executiveSummary' => [
                'overall_compliance_score' => 79.9,
                'critical_issues_found' => 0,
            ],
        ]);

        expect($reportBelowThreshold->isCompliant())->toBeFalse();
    }

    public function test_regulatory_compliance_breakdown_score_threshold(): void
    {
        $report = $this->createSampleReport([
            'regulatoryCompliance' => [
                'area_at_threshold' => ['score' => 80],
                'area_below_threshold' => ['score' => 79],
                'area_above_threshold' => ['score' => 85],
            ],
        ]);

        $breakdown = $report->getRegulatoryComplianceBreakdown();

        expect($breakdown['area_at_threshold']['status'])->toBe('compliant');
        expect($breakdown['area_below_threshold']['status'])->toBe('needs_attention');
        expect($breakdown['area_above_threshold']['status'])->toBe('compliant');
    }

    public function test_data_quality_metrics_with_partial_data(): void
    {
        $report = $this->createSampleReport([
            'dataQualityAssessment' => [
                'overall_score' => 75,
                'completeness_score' => 80,
                // Missing other scores
            ],
        ]);

        $metrics = $report->getDataQualityMetrics();

        expect($metrics)->toBe([
            'overall_score' => 75,
            'completeness' => 80,
            'accuracy' => 0,
            'consistency' => 0,
            'timeliness' => 0,
        ]);
    }
}