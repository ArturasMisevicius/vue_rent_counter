<?php

declare(strict_types=1);

namespace App\ValueObjects\Audit;

use Carbon\Carbon;

/**
 * Audit Summary Value Object
 * 
 * Contains summary statistics for audit activities including
 * change counts, event breakdowns, and time period information.
 */
final readonly class AuditSummary
{
    public function __construct(
        public int $totalChanges,
        public int $userChanges,
        public int $systemChanges,
        public array $eventBreakdown,
        public array $modelBreakdown,
        public Carbon $periodStart,
        public Carbon $periodEnd,
    ) {}

    /**
     * Get summary as array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'total_changes' => $this->totalChanges,
            'user_changes' => $this->userChanges,
            'system_changes' => $this->systemChanges,
            'user_change_percentage' => $this->getUserChangePercentage(),
            'system_change_percentage' => $this->getSystemChangePercentage(),
            'event_breakdown' => $this->eventBreakdown,
            'model_breakdown' => $this->modelBreakdown,
            'period_start' => $this->periodStart->toISOString(),
            'period_end' => $this->periodEnd->toISOString(),
            'period_duration_days' => $this->getPeriodDurationDays(),
            'changes_per_day' => $this->getChangesPerDay(),
        ];
    }

    /**
     * Get percentage of changes made by users.
     */
    public function getUserChangePercentage(): float
    {
        return $this->totalChanges > 0 
            ? round(($this->userChanges / $this->totalChanges) * 100, 2)
            : 0;
    }

    /**
     * Get percentage of changes made by system.
     */
    public function getSystemChangePercentage(): float
    {
        return $this->totalChanges > 0 
            ? round(($this->systemChanges / $this->totalChanges) * 100, 2)
            : 0;
    }

    /**
     * Get period duration in days.
     */
    public function getPeriodDurationDays(): int
    {
        return $this->periodStart->diffInDays($this->periodEnd);
    }

    /**
     * Get average changes per day.
     */
    public function getChangesPerDay(): float
    {
        $days = $this->getPeriodDurationDays();
        return $days > 0 ? round($this->totalChanges / $days, 2) : 0;
    }

    /**
     * Get most frequent event type.
     */
    public function getMostFrequentEvent(): ?string
    {
        if (empty($this->eventBreakdown)) {
            return null;
        }

        return array_key_first(
            array_slice(
                arsort($this->eventBreakdown) ? $this->eventBreakdown : [],
                0,
                1,
                true
            )
        );
    }

    /**
     * Get most frequently modified model type.
     */
    public function getMostModifiedModel(): ?string
    {
        if (empty($this->modelBreakdown)) {
            return null;
        }

        return array_key_first(
            array_slice(
                arsort($this->modelBreakdown) ? $this->modelBreakdown : [],
                0,
                1,
                true
            )
        );
    }

    /**
     * Check if audit activity is above normal levels.
     */
    public function isHighActivity(): bool
    {
        $changesPerDay = $this->getChangesPerDay();
        
        // Consider high activity if more than 50 changes per day
        return $changesPerDay > 50;
    }

    /**
     * Check if there's unusual system activity.
     */
    public function hasUnusualSystemActivity(): bool
    {
        $systemPercentage = $this->getSystemChangePercentage();
        
        // Consider unusual if system changes exceed 30% of total
        return $systemPercentage > 30;
    }
}