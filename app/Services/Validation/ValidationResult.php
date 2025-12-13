<?php

declare(strict_types=1);

namespace App\Services\Validation;

/**
 * Immutable validation result value object.
 * 
 * Represents the outcome of a validation operation with errors, warnings,
 * and metadata. Supports merging multiple validation results.
 */
final readonly class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $warnings = [],
        public array $recommendations = [],
        public array $metadata = [],
    ) {}

    /**
     * Create a valid result.
     */
    public static function valid(array $warnings = [], array $recommendations = [], array $metadata = []): self
    {
        return new self(
            isValid: true,
            warnings: $warnings,
            recommendations: $recommendations,
            metadata: $metadata,
        );
    }

    /**
     * Create an invalid result with errors.
     */
    public static function invalid(array $errors, array $warnings = [], array $recommendations = [], array $metadata = []): self
    {
        return new self(
            isValid: false,
            errors: $errors,
            warnings: $warnings,
            recommendations: $recommendations,
            metadata: $metadata,
        );
    }

    /**
     * Create an invalid result with a single error.
     */
    public static function withError(string $error, array $warnings = [], array $recommendations = [], array $metadata = []): self
    {
        return self::invalid([$error], $warnings, $recommendations, $metadata);
    }

    /**
     * Create a valid result with warnings.
     */
    public static function withWarnings(array $warnings, array $recommendations = [], array $metadata = []): self
    {
        return self::valid($warnings, $recommendations, $metadata);
    }

    /**
     * Create a valid result with a single warning.
     */
    public static function withWarning(string $warning, array $recommendations = [], array $metadata = []): self
    {
        return self::valid([$warning], $recommendations, $metadata);
    }

    /**
     * Merge this result with another result.
     * 
     * The merged result is invalid if either result is invalid.
     * Errors, warnings, recommendations, and metadata are combined.
     */
    public function merge(ValidationResult $other): self
    {
        return new self(
            isValid: $this->isValid && $other->isValid,
            errors: array_merge($this->errors, $other->errors),
            warnings: array_merge($this->warnings, $other->warnings),
            recommendations: array_merge($this->recommendations, $other->recommendations),
            metadata: array_merge($this->metadata, $other->metadata),
        );
    }

    /**
     * Add an error to this result.
     */
    public function addError(string $error): self
    {
        return new self(
            isValid: false,
            errors: array_merge($this->errors, [$error]),
            warnings: $this->warnings,
            recommendations: $this->recommendations,
            metadata: $this->metadata,
        );
    }

    /**
     * Add a warning to this result.
     */
    public function addWarning(string $warning): self
    {
        return new self(
            isValid: $this->isValid,
            errors: $this->errors,
            warnings: array_merge($this->warnings, [$warning]),
            recommendations: $this->recommendations,
            metadata: $this->metadata,
        );
    }

    /**
     * Add a recommendation to this result.
     */
    public function addRecommendation(string $recommendation): self
    {
        return new self(
            isValid: $this->isValid,
            errors: $this->errors,
            warnings: $this->warnings,
            recommendations: array_merge($this->recommendations, [$recommendation]),
            metadata: $this->metadata,
        );
    }

    /**
     * Add metadata to this result.
     */
    public function addMetadata(string $key, mixed $value): self
    {
        return new self(
            isValid: $this->isValid,
            errors: $this->errors,
            warnings: $this->warnings,
            recommendations: $this->recommendations,
            metadata: array_merge($this->metadata, [$key => $value]),
        );
    }

    /**
     * Check if this result has errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if this result has warnings.
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Check if this result has recommendations.
     */
    public function hasRecommendations(): bool
    {
        return !empty($this->recommendations);
    }

    /**
     * Get the error count.
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Get the warning count.
     */
    public function getWarningCount(): int
    {
        return count($this->warnings);
    }

    /**
     * Convert to array format for API responses.
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'recommendations' => $this->recommendations,
            'metadata' => array_merge($this->metadata, [
                'error_count' => $this->getErrorCount(),
                'warning_count' => $this->getWarningCount(),
                'has_recommendations' => $this->hasRecommendations(),
            ]),
        ];
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get a summary string of the validation result.
     */
    public function getSummary(): string
    {
        if ($this->isValid) {
            $summary = 'Validation passed';
            if ($this->hasWarnings()) {
                $summary .= ' with ' . $this->getWarningCount() . ' warning(s)';
            }
        } else {
            $summary = 'Validation failed with ' . $this->getErrorCount() . ' error(s)';
            if ($this->hasWarnings()) {
                $summary .= ' and ' . $this->getWarningCount() . ' warning(s)';
            }
        }

        return $summary;
    }
}