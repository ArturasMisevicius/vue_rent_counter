<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a tenant identifier with validation.
 * 
 * Encapsulates tenant ID validation logic and ensures type safety
 * throughout the tenant context system.
 */
final readonly class TenantId
{
    public function __construct(
        private int $value
    ) {
        if ($this->value <= 0) {
            throw new InvalidArgumentException("Tenant ID must be a positive integer, got: {$this->value}");
        }
    }

    public static function from(int $value): self
    {
        return new self($value);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(TenantId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}