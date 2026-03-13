<?php

declare(strict_types=1);

namespace App\ValueObjects\Audit;

/**
 * Configuration Change History Value Object
 * 
 * Represents the complete history of configuration changes
 * with rollback capabilities and impact analysis.
 */
final readonly class ConfigurationChangeHistory
{
    public function __construct(
        public array $changes,
        public array $rollbackCapabilities,
        public array $changeFrequency,
        public array $impactAnalysis,
        public array $recommendations,
    ) {}

    /**
     * Get changes by impact level.
     */
    public function getChangesByImpact(string $impactLevel): array
    {
        return array_filter($this->changes, fn($change) => $change['impact_level'] === $impactLevel);
    }

    /**
     * Get rollbackable changes.
     */
    public function getRollbackableChanges(): array
    {
        return array_filter($this->changes, fn($change) => $change['rollback_available']);
    }

    /**
     * Get changes by user.
     */
    public function getChangesByUser(string $userName): array
    {
        return array_filter($this->changes, fn($change) => $change['user'] === $userName);
    }

    /**
     * Get changes by model type.
     */
    public function getChangesByModelType(string $modelType): array
    {
        return array_filter($this->changes, fn($change) => $change['model_type'] === $modelType);
    }

    /**
     * Get high-priority recommendations.
     */
    public function getHighPriorityRecommendations(): array
    {
        return array_filter($this->recommendations, fn($rec) => $rec['priority'] === 'high');
    }

    /**
     * Get change frequency statistics.
     */
    public function getChangeFrequencyStats(): array
    {
        return [
            'total_changes' => $this->changeFrequency['total_changes'],
            'average_per_day' => $this->changeFrequency['average_per_day'],
            'peak_day' => $this->changeFrequency['peak_day'],
            'peak_count' => $this->changeFrequency['peak_count'],
        ];
    }

    /**
     * Get impact distribution summary.
     */
    public function getImpactDistribution(): array
    {
        return $this->impactAnalysis['impact_distribution'] ?? [];
    }

    /**
     * Check if rollback percentage is healthy.
     */
    public function hasHealthyRollbackRate(): bool
    {
        return ($this->rollbackCapabilities['rollback_percentage'] ?? 0) < 10; // Less than 10% rollbacks is healthy
    }

    /**
     * Get total number of changes.
     */
    public function getTotalChanges(): int
    {
        return count($this->changes);
    }

    /**
     * Get changes within date range.
     */
    public function getChangesInDateRange(string $startDate, string $endDate): array
    {
        return array_filter($this->changes, function ($change) use ($startDate, $endDate) {
            $changeDate = substr($change['timestamp'], 0, 10); // Extract date part
            return $changeDate >= $startDate && $changeDate <= $endDate;
        });
    }
}