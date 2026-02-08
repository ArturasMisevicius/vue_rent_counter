<?php

declare(strict_types=1);

namespace App\ValueObjects\Audit;

use Carbon\Carbon;

/**
 * Performance Metrics Value Object
 * 
 * Contains comprehensive performance metrics for universal billing calculations,
 * system response times, and operational efficiency indicators.
 */
final readonly class PerformanceMetrics
{
    public function __construct(
        public array $billingCalculationMetrics,
        public array $systemResponseMetrics,
        public array $dataQualityMetrics,
        public array $operationalEfficiency,
        public array $errorRates,
        public array $resourceUtilization,
        public Carbon $collectedAt,
    ) {}

    /**
     * Get metrics as array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'billing_calculation_metrics' => $this->billingCalculationMetrics,
            'system_response_metrics' => $this->systemResponseMetrics,
            'data_quality_metrics' => $this->dataQualityMetrics,
            'operational_efficiency' => $this->operationalEfficiency,
            'error_rates' => $this->errorRates,
            'resource_utilization' => $this->resourceUtilization,
            'overall_score' => $this->getOverallScore(),
            'performance_grade' => $this->getPerformanceGrade(),
            'collected_at' => $this->collectedAt->toISOString(),
        ];
    }

    /**
     * Calculate overall performance score (0-100).
     */
    public function getOverallScore(): float
    {
        $scores = [
            $this->getBillingPerformanceScore(),
            $this->getSystemResponseScore(),
            $this->getDataQualityScore(),
            $this->getOperationalEfficiencyScore(),
            $this->getErrorRateScore(),
            $this->getResourceUtilizationScore(),
        ];
        
        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Get performance grade (A-F).
     */
    public function getPerformanceGrade(): string
    {
        $score = $this->getOverallScore();
        
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    /**
     * Get billing calculation performance score.
     */
    public function getBillingPerformanceScore(): float
    {
        $successRate = $this->billingCalculationMetrics['calculation_success_rate'] ?? 0;
        $avgTime = $this->billingCalculationMetrics['average_processing_time_ms'] ?? 1000;
        
        // Score based on success rate (70%) and processing time (30%)
        $successScore = $successRate;
        $timeScore = max(0, 100 - ($avgTime / 10)); // Deduct points for slow processing
        
        return ($successScore * 0.7) + ($timeScore * 0.3);
    }

    /**
     * Get system response performance score.
     */
    public function getSystemResponseScore(): float
    {
        $avgResponse = $this->systemResponseMetrics['average_response_time_ms'] ?? 500;
        $errorRate = $this->systemResponseMetrics['error_rate_percentage'] ?? 5;
        $uptime = $this->systemResponseMetrics['uptime_percentage'] ?? 95;
        
        // Score based on response time (40%), error rate (30%), uptime (30%)
        $responseScore = max(0, 100 - ($avgResponse / 5));
        $errorScore = max(0, 100 - ($errorRate * 10));
        $uptimeScore = $uptime;
        
        return ($responseScore * 0.4) + ($errorScore * 0.3) + ($uptimeScore * 0.3);
    }

    /**
     * Get data quality performance score.
     */
    public function getDataQualityScore(): float
    {
        return $this->dataQualityMetrics['data_quality_score'] ?? 0;
    }

    /**
     * Get operational efficiency score.
     */
    public function getOperationalEfficiencyScore(): float
    {
        $utilizationRate = $this->operationalEfficiency['configuration_utilization_rate'] ?? 0;
        $automationRate = $this->operationalEfficiency['automation_rate'] ?? 0;
        $manualOverrideRate = $this->operationalEfficiency['manual_override_rate'] ?? 100;
        
        // Score based on utilization (40%), automation (40%), manual overrides (20%)
        $utilizationScore = $utilizationRate;
        $automationScore = $automationRate;
        $overrideScore = max(0, 100 - $manualOverrideRate);
        
        return ($utilizationScore * 0.4) + ($automationScore * 0.4) + ($overrideScore * 0.2);
    }

    /**
     * Get error rate score (lower errors = higher score).
     */
    public function getErrorRateScore(): float
    {
        $totalErrors = $this->errorRates['total_errors'] ?? 0;
        $criticalErrors = $this->errorRates['critical_errors'] ?? 0;
        $errorRatePerHour = $this->errorRates['error_rate_per_hour'] ?? 0;
        
        // Start with 100 and deduct points for errors
        $score = 100;
        $score -= $criticalErrors * 10; // Critical errors cost 10 points each
        $score -= ($totalErrors - $criticalErrors) * 2; // Other errors cost 2 points each
        $score -= $errorRatePerHour * 5; // High error rate costs additional points
        
        return max(0, $score);
    }

    /**
     * Get resource utilization score.
     */
    public function getResourceUtilizationScore(): float
    {
        $cpuUtil = $this->resourceUtilization['cpu_utilization_avg'] ?? 50;
        $memoryUtil = $this->resourceUtilization['memory_utilization_avg'] ?? 50;
        $cacheHitRate = $this->resourceUtilization['cache_hit_rate'] ?? 80;
        
        // Optimal utilization is around 60-80%
        $cpuScore = $this->getUtilizationScore($cpuUtil);
        $memoryScore = $this->getUtilizationScore($memoryUtil);
        $cacheScore = $cacheHitRate;
        
        return ($cpuScore * 0.4) + ($memoryScore * 0.4) + ($cacheScore * 0.2);
    }

    /**
     * Calculate utilization score (optimal range is 60-80%).
     */
    private function getUtilizationScore(float $utilization): float
    {
        if ($utilization >= 60 && $utilization <= 80) {
            return 100; // Optimal range
        }
        
        if ($utilization < 60) {
            return 50 + ($utilization / 60) * 50; // Under-utilization
        }
        
        // Over-utilization
        return max(0, 100 - (($utilization - 80) * 2));
    }

    /**
     * Get performance issues that need attention.
     */
    public function getPerformanceIssues(): array
    {
        $issues = [];
        
        // Check billing performance
        if ($this->getBillingPerformanceScore() < 80) {
            $issues[] = [
                'category' => 'billing_performance',
                'severity' => 'medium',
                'description' => 'Billing calculation performance below optimal',
                'metrics' => $this->billingCalculationMetrics,
            ];
        }
        
        // Check system response
        if ($this->getSystemResponseScore() < 70) {
            $issues[] = [
                'category' => 'system_response',
                'severity' => 'high',
                'description' => 'System response times or availability issues',
                'metrics' => $this->systemResponseMetrics,
            ];
        }
        
        // Check data quality
        if ($this->getDataQualityScore() < 90) {
            $issues[] = [
                'category' => 'data_quality',
                'severity' => 'medium',
                'description' => 'Data quality below acceptable threshold',
                'metrics' => $this->dataQualityMetrics,
            ];
        }
        
        // Check error rates
        if ($this->getErrorRateScore() < 85) {
            $issues[] = [
                'category' => 'error_rates',
                'severity' => 'high',
                'description' => 'High error rates detected',
                'metrics' => $this->errorRates,
            ];
        }
        
        return $issues;
    }

    /**
     * Get performance trends (requires historical data).
     */
    public function getPerformanceTrends(): array
    {
        // This would typically compare with historical metrics
        // For now, we'll provide a simplified trend analysis
        
        return [
            'billing_performance_trend' => 'stable', // Would be calculated from historical data
            'response_time_trend' => 'improving',
            'data_quality_trend' => 'stable',
            'error_rate_trend' => 'decreasing',
        ];
    }

    /**
     * Check if performance is within acceptable limits.
     */
    public function isPerformanceAcceptable(): bool
    {
        return $this->getOverallScore() >= 75;
    }

    /**
     * Get top performance bottlenecks.
     */
    public function getTopBottlenecks(): array
    {
        $bottlenecks = [];
        
        // Analyze each metric category for bottlenecks
        $scores = [
            'billing_calculation' => $this->getBillingPerformanceScore(),
            'system_response' => $this->getSystemResponseScore(),
            'data_quality' => $this->getDataQualityScore(),
            'operational_efficiency' => $this->getOperationalEfficiencyScore(),
            'error_rates' => $this->getErrorRateScore(),
            'resource_utilization' => $this->getResourceUtilizationScore(),
        ];
        
        // Sort by score (lowest first)
        asort($scores);
        
        // Take the bottom 3 as bottlenecks
        $bottomThree = array_slice($scores, 0, 3, true);
        
        foreach ($bottomThree as $category => $score) {
            if ($score < 80) {
                $bottlenecks[] = [
                    'category' => $category,
                    'score' => $score,
                    'severity' => $score < 60 ? 'high' : 'medium',
                ];
            }
        }
        
        return $bottlenecks;
    }
}