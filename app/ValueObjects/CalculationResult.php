<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * Value object representing a gyvatukas calculation result.
 */
final readonly class CalculationResult
{
    public function __construct(
        public float $energy,
        public Carbon $calculatedAt,
        public string $calculationType,
        public int $buildingId,
        public ?string $cacheKey = null,
        public ?array $metadata = null,
    ) {}

    public static function create(
        float $energy,
        string $calculationType,
        int $buildingId,
        ?string $cacheKey = null,
        ?array $metadata = null,
    ): self {
        return new self(
            energy: max(0.0, round($energy, 2)),
            calculatedAt: now(),
            calculationType: $calculationType,
            buildingId: $buildingId,
            cacheKey: $cacheKey,
            metadata: $metadata ?? [],
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
        return $this->energy === 0.0;
    }

    public function hasMetadata(string $key): bool
    {
        return isset($this->metadata[$key]);
    }

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}