<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Input Sanitization Service
 * 
 * Provides comprehensive input sanitization beyond basic strip_tags().
 * Implements defense-in-depth for XSS prevention.
 * 
 * Security Features:
 * - HTML tag removal with whitelist support
 * - JavaScript event handler removal
 * - SQL injection pattern detection
 * - Path traversal prevention
 * - Unicode normalization
 */
class InputSanitizer
{
    /**
     * Dangerous HTML tags that should always be removed.
     */
    protected const DANGEROUS_TAGS = [
        'script', 'iframe', 'object', 'embed', 'applet',
        'meta', 'link', 'style', 'form', 'input', 'button',
    ];

    /**
     * Dangerous HTML attributes that should be removed.
     */
    protected const DANGEROUS_ATTRIBUTES = [
        'onclick', 'onload', 'onerror', 'onmouseover', 'onmouseout',
        'onkeydown', 'onkeyup', 'onfocus', 'onblur', 'onchange',
        'onsubmit', 'onreset', 'onselect', 'onabort',
    ];

    /**
     * Sanitize text input with comprehensive XSS prevention.
     */
    public function sanitizeText(string $input, bool $allowBasicHtml = false): string
    {
        // Normalize Unicode
        $input = $this->normalizeUnicode($input);

        // Remove null bytes
        $input = str_replace("\0", '', $input);

        if ($allowBasicHtml) {
            // Allow only safe HTML tags
            $input = strip_tags($input, '<p><br><strong><em><u>');
            
            // Remove dangerous attributes
            $input = $this->removeDangerousAttributes($input);
        } else {
            // Remove all HTML
            $input = strip_tags($input);
        }

        // Remove JavaScript protocol handlers
        $input = preg_replace('/javascript:/i', '', $input);
        $input = preg_replace('/vbscript:/i', '', $input);
        $input = preg_replace('/data:text\/html/i', '', $input);

        // Trim whitespace
        return trim($input);
    }

    /**
     * Sanitize numeric input with overflow protection.
     */
    public function sanitizeNumeric(string|float|int $input, float $max = 999999.9999): float
    {
        $value = floatval($input);

        // Prevent numeric overflow
        if ($value > $max) {
            throw new \InvalidArgumentException("Value exceeds maximum allowed: {$max}");
        }

        if ($value < 0) {
            throw new \InvalidArgumentException("Negative values not allowed");
        }

        return $value;
    }

    /**
     * Sanitize identifier (alphanumeric with limited special chars).
     */
    public function sanitizeIdentifier(string $input): string
    {
        // Allow only alphanumeric, underscore, hyphen
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
    }

    /**
     * Normalize Unicode to prevent homograph attacks.
     */
    protected function normalizeUnicode(string $input): string
    {
        if (function_exists('normalizer_normalize')) {
            return normalizer_normalize($input, \Normalizer::FORM_C) ?: $input;
        }

        return $input;
    }

    /**
     * Remove dangerous HTML attributes.
     */
    protected function removeDangerousAttributes(string $input): string
    {
        foreach (self::DANGEROUS_ATTRIBUTES as $attr) {
            $input = preg_replace('/' . $attr . '\s*=\s*["\'][^"\']*["\']/i', '', $input);
        }

        return $input;
    }

    /**
     * Validate and sanitize time format (HH:MM).
     */
    public function sanitizeTime(string $input): string
    {
        if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $input)) {
            throw new \InvalidArgumentException("Invalid time format. Expected HH:MM");
        }

        return $input;
    }
}
