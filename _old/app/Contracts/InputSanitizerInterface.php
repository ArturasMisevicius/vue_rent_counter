<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Input Sanitizer Contract
 * 
 * Defines the interface for input sanitization services.
 * Enables dependency inversion and easier testing/mocking.
 * 
 * @package App\Contracts
 */
interface InputSanitizerInterface
{
    /**
     * Sanitize text input with comprehensive XSS prevention.
     * 
     * @param string $input The text to sanitize
     * @param bool $allowBasicHtml Whether to allow safe HTML tags
     * @return string Sanitized text
     */
    public function sanitizeText(string $input, bool $allowBasicHtml = false): string;

    /**
     * Sanitize numeric input with overflow protection.
     * 
     * @param string|float|int $input The numeric value to sanitize
     * @param float $max Maximum allowed value
     * @return float Sanitized numeric value
     * @throws \InvalidArgumentException If value exceeds maximum or is negative
     */
    public function sanitizeNumeric(string|float|int $input, float $max = 999999.9999): float;

    /**
     * Sanitize identifier (alphanumeric with limited special chars).
     * 
     * @param string $input The identifier to sanitize
     * @param int $maxLength Maximum allowed length
     * @return string Sanitized identifier
     * @throws \InvalidArgumentException If input is invalid
     */
    public function sanitizeIdentifier(string $input, int $maxLength = 255): string;

    /**
     * Validate and sanitize time format (HH:MM).
     * 
     * @param string $input Time string to validate
     * @return string Validated time string
     * @throws \InvalidArgumentException If time format is invalid
     */
    public function sanitizeTime(string $input): string;

    /**
     * Get cache statistics for monitoring.
     * 
     * @return array{size: int, max_size: int, utilization: float}
     */
    public function getCacheStats(): array;

    /**
     * Clear the Unicode normalization cache.
     * 
     * @return void
     */
    public function clearCache(): void;
}
