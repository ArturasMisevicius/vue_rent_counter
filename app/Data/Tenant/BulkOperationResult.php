<?php

declare(strict_types=1);

namespace App\Data\Tenant;

final readonly class BulkOperationResult
{
    public function __construct(
        public int $totalProcessed,
        public int $successful,
        public int $failed,
        public array $errors = [],
        public array $successfulIds = [],
        public array $failedIds = [],
        public float $executionTimeMs = 0.0,
    ) {}

    public static function success(int $count, array $ids = [], float $executionTime = 0.0): self
    {
        return new self(
            totalProcessed: $count,
            successful: $count,
            failed: 0,
            successfulIds: $ids,
            executionTimeMs: $executionTime,
        );
    }

    public static function failure(int $count, array $errors = [], array $ids = [], float $executionTime = 0.0): self
    {
        return new self(
            totalProcessed: $count,
            successful: 0,
            failed: $count,
            errors: $errors,
            failedIds: $ids,
            executionTimeMs: $executionTime,
        );
    }

    public static function mixed(
        int $total,
        int $successful,
        int $failed,
        array $errors = [],
        array $successfulIds = [],
        array $failedIds = [],
        float $executionTime = 0.0
    ): self {
        return new self(
            totalProcessed: $total,
            successful: $successful,
            failed: $failed,
            errors: $errors,
            successfulIds: $successfulIds,
            failedIds: $failedIds,
            executionTimeMs: $executionTime,
        );
    }

    public function isSuccessful(): bool
    {
        return $this->failed === 0;
    }

    public function hasErrors(): bool
    {
        return $this->failed > 0;
    }

    public function getSuccessRate(): float
    {
        if ($this->totalProcessed === 0) {
            return 0.0;
        }

        return ($this->successful / $this->totalProcessed) * 100;
    }
}