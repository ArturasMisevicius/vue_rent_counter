<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Security Header Set Value Object
 * 
 * Immutable collection of security headers with validation.
 */
final readonly class SecurityHeaderSet
{
    /**
     * @param array<string, string> $headers
     */
    private function __construct(
        public array $headers
    ) {
        $this->validateHeaders($headers);
    }

    /**
     * Create a new security header set.
     * 
     * @param array<string, string> $headers
     */
    public static function create(array $headers): self
    {
        return new self($headers);
    }

    /**
     * Create an empty header set.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Add a header to the set.
     */
    public function withHeader(string $name, string $value): self
    {
        return new self([...$this->headers, $name => $value]);
    }

    /**
     * Remove a header from the set.
     */
    public function withoutHeader(string $name): self
    {
        $headers = $this->headers;
        unset($headers[$name]);
        
        return new self($headers);
    }

    /**
     * Merge with another header set.
     */
    public function merge(self $other): self
    {
        return new self([...$this->headers, ...$other->headers]);
    }

    /**
     * Get a specific header value.
     */
    public function get(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Check if header exists.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->headers);
    }

    /**
     * Get all headers as array.
     * 
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->headers;
    }

    /**
     * Get header count.
     */
    public function count(): int
    {
        return count($this->headers);
    }

    /**
     * Validate header names and values.
     * 
     * @param array<string, string> $headers
     */
    private function validateHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                throw new InvalidArgumentException('Header names and values must be strings');
            }

            if (empty($name)) {
                throw new InvalidArgumentException('Header name cannot be empty');
            }

            // Validate header name format (RFC 7230)
            if (!preg_match('/^[!#$%&\'*+\-.0-9A-Z^_`a-z|~]+$/', $name)) {
                throw new InvalidArgumentException("Invalid header name: {$name}");
            }

            // Validate header value (no control characters except tab)
            if (preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $value)) {
                throw new InvalidArgumentException("Invalid header value for {$name}");
            }
        }
    }
}