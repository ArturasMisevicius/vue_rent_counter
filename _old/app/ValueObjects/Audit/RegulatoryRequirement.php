<?php

declare(strict_types=1);

namespace App\ValueObjects\Audit;

/**
 * Regulatory Requirement Value Object
 * 
 * Represents a specific regulatory requirement that must be validated
 * for compliance reporting and assessment.
 */
final readonly class RegulatoryRequirement
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public array $requirements,
        public float $complianceThreshold,
        public ?string $category = null,
        public ?string $jurisdiction = null,
        public ?string $effectiveDate = null,
        public ?array $applicableUtilityTypes = null,
        public ?array $validationRules = null,
    ) {}

    /**
     * Check if requirement applies to given utility types.
     */
    public function appliesTo(array $utilityTypes): bool
    {
        if ($this->applicableUtilityTypes === null) {
            return true; // Applies to all utility types
        }
        
        return !empty(array_intersect($utilityTypes, $this->applicableUtilityTypes));
    }

    /**
     * Get validation rules for this requirement.
     */
    public function getValidationRules(): array
    {
        return $this->validationRules ?? [];
    }

    /**
     * Check if requirement is currently effective.
     */
    public function isEffective(): bool
    {
        if ($this->effectiveDate === null) {
            return true;
        }
        
        return now()->gte($this->effectiveDate);
    }

    /**
     * Get requirement severity level.
     */
    public function getSeverityLevel(): string
    {
        return match (true) {
            $this->complianceThreshold >= 95 => 'critical',
            $this->complianceThreshold >= 85 => 'high',
            $this->complianceThreshold >= 70 => 'medium',
            default => 'low',
        };
    }

    /**
     * Convert to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'compliance_threshold' => $this->complianceThreshold,
            'category' => $this->category,
            'jurisdiction' => $this->jurisdiction,
            'effective_date' => $this->effectiveDate,
            'applicable_utility_types' => $this->applicableUtilityTypes,
            'validation_rules' => $this->validationRules,
            'severity_level' => $this->getSeverityLevel(),
            'is_effective' => $this->isEffective(),
        ];
    }
}