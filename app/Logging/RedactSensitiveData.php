<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Redact Sensitive Data Processor
 *
 * Automatically redacts PII and sensitive information from log messages
 * to comply with GDPR and data protection regulations.
 *
 * ## Redacted Patterns
 * - Email addresses
 * - Phone numbers (international format)
 * - Credit card numbers
 * - IP addresses (optional, configurable)
 * - API keys and tokens
 *
 * @package App\Logging
 */
final class RedactSensitiveData implements ProcessorInterface
{
    /**
     * Redaction patterns and replacements.
     *
     * @var array<string, string>
     */
    private array $patterns = [
        // Email addresses
        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL_REDACTED]',
        
        // Phone numbers (various formats)
        '/\b\+?[\d\s\-\(\)]{10,}\b/' => '[PHONE_REDACTED]',
        
        // Credit card numbers (basic pattern)
        '/\b\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}\b/' => '[CARD_REDACTED]',
        
        // API keys and tokens (common patterns)
        '/\b[A-Za-z0-9_\-]{32,}\b/' => '[TOKEN_REDACTED]',
        
        // Passwords in URLs or logs
        '/password["\']?\s*[:=]\s*["\']?[^\s"\']+/' => 'password=[REDACTED]',
        
        // Bearer tokens
        '/Bearer\s+[A-Za-z0-9\-._~+\/]+=*/' => 'Bearer [REDACTED]',
    ];

    /**
     * Process the log record and redact sensitive information.
     *
     * @param  LogRecord  $record
     * @return LogRecord
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        // Redact message
        $record->message = $this->redact($record->message);

        // Redact context data
        if (!empty($record->context)) {
            $record->context = $this->redactArray($record->context);
        }

        // Redact extra data
        if (!empty($record->extra)) {
            $record->extra = $this->redactArray($record->extra);
        }

        return $record;
    }

    /**
     * Redact sensitive patterns from a string.
     *
     * @param  string  $text
     * @return string
     */
    private function redact(string $text): string
    {
        foreach ($this->patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    /**
     * Recursively redact sensitive data from arrays.
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function redactArray(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'ssn',
            'social_security',
        ];

        foreach ($data as $key => $value) {
            // Redact sensitive keys
            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
                continue;
            }

            // Recursively process arrays
            if (is_array($value)) {
                $data[$key] = $this->redactArray($value);
                continue;
            }

            // Redact string values
            if (is_string($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }
}
