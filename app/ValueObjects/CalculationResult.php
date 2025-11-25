<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\Building;

/**
 * Value Object representing the result of a summer average calculation.
 */
final readonly class CalculationResult
{
    public function __construct(
        public Building $building,
        public string $status,
        public ?float $average = null,
        public ?string $errorMessage = null,
    ) {}

    /**
     * Create a successful calculation result.
     */
    public static function success(Building $building, float $average): self
    {
        return new self(
            building: $building,
            status: 'success',
            average: $average,
        );
    }

    /**
     * Create a skipped calculation result.
     */
    public static function skipped(Building $building, string $reason): self
    {
        return new self(
            building: $building,
            status: 'skipped',
            errorMessage: $reason,
        );
    }

    /**
     * Create a failed calculation result.
     */
    public static function failed(Building $building, string $errorMessage): self
    {
        return new self(
            building: $building,
            status: 'failed',
            errorMessage: $errorMessage,
        );
    }

    /**
     * Check if the calculation was successful.
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if the calculation was skipped.
     */
    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    /**
     * Check if the calculation failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get a formatted message for display.
     */
    public function getMessage(): string
    {
        return match ($this->status) {
            'success' => sprintf(
                'Building #%d (%s): %.2f kWh',
                $this->building->id,
                $this->building->display_name,
                $this->average
            ),
            'skipped' => sprintf(
                'Building #%d (%s): Skipped - %s',
                $this->building->id,
                $this->building->display_name,
                $this->errorMessage
            ),
            'failed' => sprintf(
                'Building #%d (%s): Failed - %s',
                $this->building->id,
                $this->building->display_name,
                $this->errorMessage
            ),
            default => 'Unknown status',
        };
    }
}
