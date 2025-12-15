<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * Legacy-compatible calculation result value object.
 *
 * This VO is kept for backward compatibility with older calculation flows
 * and existing unit tests. New billing calculations should use
 * {@see \App\ValueObjects\UniversalCalculationResult}.
 */
final readonly class CalculationResult
{
    public function __construct(
        public float $energy,
        public string $calculationType,
        public int $buildingId,
        public ?string $cacheKey = null,
        public array $metadata = [],
        public Carbon $calculatedAt = new Carbon(),
    ) {}

    /**
     * Factory that enforces rounding and minimum values.
     *
     * Supports both named and positional arguments.
     */
    public static function create(
        float $energy,
        string $calculationType,
        int $buildingId,
        ?string $cacheKey = null,
        array $metadata = [],
        ?Carbon $calculatedAt = null,
    ): self {
        $normalizedEnergy = max(0.0, round($energy, 2));

        return new self(
            energy: $normalizedEnergy,
            calculationType: $calculationType,
            buildingId: $buildingId,
            cacheKey: $cacheKey,
            metadata: $metadata,
            calculatedAt: $calculatedAt ?? Carbon::now(),
        );
    }

    public function toArray(): array
    {
        return [
            'energy' => $this->energy,
            'calculated_at' => $this->calculatedAt->toISOString(),
            'calculation_type' => $this->calculationType,
            'building_id' => $this->buildingId,
            'cache_key' => $this->cacheKey,
            'metadata' => $this->metadata,
        ];
    }

    public function isZero(): bool
    {
        return $this->energy <= 0.0;
    }

    public function hasMetadata(string $key): bool
    {
        return array_key_exists($key, $this->metadata);
    }

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->metadata)) {
            return $this->metadata[$key];
        }

        return $default;
    }
}

