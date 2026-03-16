<?php

declare(strict_types=1);

namespace App\ValueObjects\Audit;

use Carbon\Carbon;

/**
 * Configuration Change Value Object
 * 
 * Represents a single configuration change with rollback capabilities
 * and impact assessment information.
 */
final readonly class ConfigurationChange
{
    public function __construct(
        public int $id,
        public string $modelType,
        public int $modelId,
        public string $event,
        public array $changes,
        public ?int $userId,
        public string $userName,
        public Carbon $timestamp,
        public bool $canRollback,
        public array $impactAssessment,
    ) {}

    /**
     * Get change as array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'model_type' => $this->modelType,
            'model_id' => $this->modelId,
            'event' => $this->event,
            'changes' => $this->changes,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'timestamp' => $this->timestamp->toISOString(),
            'can_rollback' => $this->canRollback,
            'impact_assessment' => $this->impactAssessment,
            'change_count' => count($this->changes),
            'has_high_impact' => $this->hasHighImpact(),
            'affected_areas_count' => count($this->impactAssessment['affected_areas'] ?? []),
            'risk_factors_count' => count($this->impactAssessment['risk_factors'] ?? []),
        ];
    }

    /**
     * Check if this change has high impact.
     */
    public function hasHighImpact(): bool
    {
        return ($this->impactAssessment['level'] ?? 'low') === 'high';
    }

    /**
     * Check if this change affects billing calculations.
     */
    public function affectsBilling(): bool
    {
        $affectedAreas = $this->impactAssessment['affected_areas'] ?? [];
        return in_array('billing_calculations', $affectedAreas);
    }

    /**
     * Check if this change affects service availability.
     */
    public function affectsServiceAvailability(): bool
    {
        $affectedAreas = $this->impactAssessment['affected_areas'] ?? [];
        return in_array('service_availability', $affectedAreas);
    }

    /**
     * Get the number of fields changed.
     */
    public function getChangeCount(): int
    {
        return count($this->changes);
    }

    /**
     * Get list of changed field names.
     */
    public function getChangedFields(): array
    {
        return array_keys($this->changes);
    }

    /**
     * Check if a specific field was changed.
     */
    public function hasFieldChange(string $fieldName): bool
    {
        return array_key_exists($fieldName, $this->changes);
    }

    /**
     * Get the old value for a specific field.
     */
    public function getOldValue(string $fieldName): mixed
    {
        return $this->changes[$fieldName]['old'] ?? null;
    }

    /**
     * Get the new value for a specific field.
     */
    public function getNewValue(string $fieldName): mixed
    {
        return $this->changes[$fieldName]['new'] ?? null;
    }

    /**
     * Get a human-readable description of the change.
     */
    public function getDescription(): string
    {
        $modelName = class_basename($this->modelType);
        $changeCount = $this->getChangeCount();
        
        return match ($this->event) {
            'created' => "Created {$modelName} #{$this->modelId}",
            'updated' => "Updated {$changeCount} field(s) in {$modelName} #{$this->modelId}",
            'deleted' => "Deleted {$modelName} #{$this->modelId}",
            'rollback' => "Rolled back changes to {$modelName} #{$this->modelId}",
            default => "Performed '{$this->event}' on {$modelName} #{$this->modelId}",
        };
    }

    /**
     * Get risk level based on impact assessment.
     */
    public function getRiskLevel(): string
    {
        $level = $this->impactAssessment['level'] ?? 'low';
        $riskFactors = count($this->impactAssessment['risk_factors'] ?? []);
        
        if ($level === 'high' || $riskFactors > 2) {
            return 'high';
        }
        
        if ($level === 'medium' || $riskFactors > 0) {
            return 'medium';
        }
        
        return 'low';
    }

    /**
     * Check if this change requires approval.
     */
    public function requiresApproval(): bool
    {
        return $this->hasHighImpact() || 
               $this->affectsBilling() || 
               $this->affectsServiceAvailability();
    }

    /**
     * Get time since change was made.
     */
    public function getTimeSinceChange(): string
    {
        return $this->timestamp->diffForHumans();
    }
}