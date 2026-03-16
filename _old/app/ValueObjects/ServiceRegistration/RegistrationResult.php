<?php

declare(strict_types=1);

namespace App\ValueObjects\ServiceRegistration;

/**
 * Value object representing the result of a service registration operation
 */
final readonly class RegistrationResult
{
    public function __construct(
        public int $registered,
        public int $skipped,
        public array $errors,
        public float $durationMs = 0.0,
    ) {}

    public static function success(int $registered, float $durationMs = 0.0): self
    {
        return new self($registered, 0, [], $durationMs);
    }

    public static function withErrors(int $registered, int $skipped, array $errors, float $durationMs = 0.0): self
    {
        return new self($registered, $skipped, $errors, $durationMs);
    }

    public function isSuccessful(): bool
    {
        return empty($this->errors);
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getTotalProcessed(): int
    {
        return $this->registered + $this->skipped;
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    public function toArray(): array
    {
        return [
            'registered' => $this->registered,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'duration_ms' => $this->durationMs,
            'total_processed' => $this->getTotalProcessed(),
            'error_count' => $this->getErrorCount(),
            'success_rate' => $this->getTotalProcessed() > 0 
                ? ($this->registered / $this->getTotalProcessed()) * 100 
                : 0,
        ];
    }
}