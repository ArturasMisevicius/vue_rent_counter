<?php

declare(strict_types=1);

namespace App\Services\Validation;

use Carbon\Carbon;

/**
 * Immutable validation result value object.
 * 
 * Encapsulates validation results with type safety and immutability.
 */
final readonly class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $warnings = [],
        public array $recommendations = [],
        public array $metadata = [],
    ) {
    }

    public static function valid(
        array $warnings = [],
        array $recommendations = [],
        array $metadata = []
    ): self {
        return new self(
            isValid: true,
            warnings: $warnings,
            recommendations: $recommendations,
            metadata: $metadata
        );
    }

    public static function invalid(
        array $errors,
        array $warnings = [],
        array $recommendations = [],
        array $metadata = []
    ): self {
        return new self(
            isValid: false,
            errors: $errors,
            warnings: $warnings,
            recommendations: $recommendations,
            metadata: $metadata
        );
    }

    public static function withError(string $error): self
    {
        return self::invalid([$error]);
    }

    public static function withWarning(string $warning): self
    {
        return self::valid([$warning]);
    }

    public function merge(ValidationResult $other): self
    {
        return new self(
            isValid: $this->isValid && $other->isValid,
            errors: array_merge($this->errors, $other->errors),
            warnings: array_merge($this->warnings, $other->warnings),
            recommendations: array_merge($this->recommendations, $other->recommendations),
            metadata: array_merge($this->metadata, $other->metadata)
        );
    }

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'recommendations' => $this->recommendations,
            'validation_metadata' => array_merge($this->metadata, [
                'validated_at' => now()->toISOString(),
            ]),
        ];
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function hasRecommendations(): bool
    {
        return !empty($this->recommendations);
    }
}