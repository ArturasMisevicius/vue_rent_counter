<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Log Processor for PII Redaction
 * 
 * Automatically redacts sensitive personal information from logs:
 * - Email addresses
 * - IP addresses
 * - Phone numbers
 * - Credit card numbers
 * - API tokens
 * 
 * Compliance: GDPR, CCPA, privacy regulations
 * 
 * @package App\Logging
 */
final class RedactSensitiveData implements ProcessorInterface
{
    /**
     * Regex pattern for email addresses
     */
    private const EMAIL_PATTERN = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
    
    /**
     * Regex pattern for IPv4 addresses
     */
    private const IP_PATTERN = '/\b(?:\d{1,3}\.){3}\d{1,3}\b/';
    
    /**
     * Regex pattern for phone numbers (various formats)
     */
    private const PHONE_PATTERN = '/\b(?:\+?1[-.]?)?\(?([0-9]{3})\)?[-.]?([0-9]{3})[-.]?([0-9]{4})\b/';
    
    /**
     * Regex pattern for credit card numbers
     */
    private const CC_PATTERN = '/\b(?:\d{4}[-\s]?){3}\d{4}\b/';
    
    /**
     * Regex pattern for API tokens (common formats)
     */
    private const TOKEN_PATTERN = '/\b[A-Za-z0-9_-]{32,}\b/';
    
    /**
     * Sensitive keys that should always be redacted
     */
    private const SENSITIVE_KEYS = [
        'email',
        'user_email',
        'ip',
        'ip_address',
        'password',
        'password_confirmation',
        'token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
        'private_key',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'social_security',
        'phone',
        'phone_number',
        'mobile',
    ];
    
    /**
     * Process log record to redact sensitive data.
     *
     * @param  LogRecord  $record
     * @return LogRecord
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        // Redact message
        $record['message'] = $this->redactString($record['message']);
        
        // Redact context
        $record['context'] = $this->redactArray($record['context']);
        
        // Redact extra data
        if (isset($record['extra'])) {
            $record['extra'] = $this->redactArray($record['extra']);
        }
        
        return $record;
    }
    
    /**
     * Redact sensitive patterns from string.
     *
     * @param  string  $text
     * @return string
     */
    private function redactString(string $text): string
    {
        // Redact emails
        $text = preg_replace(self::EMAIL_PATTERN, '[EMAIL_REDACTED]', $text);
        
        // Redact IPs
        $text = preg_replace(self::IP_PATTERN, '[IP_REDACTED]', $text);
        
        // Redact phone numbers
        $text = preg_replace(self::PHONE_PATTERN, '[PHONE_REDACTED]', $text);
        
        // Redact credit cards
        $text = preg_replace(self::CC_PATTERN, '[CC_REDACTED]', $text);
        
        // Redact tokens (be careful not to redact legitimate data)
        // Only redact if it looks like a token in a key=value context
        $text = preg_replace('/\b(token|key|secret)=[A-Za-z0-9_-]{32,}\b/i', '$1=[TOKEN_REDACTED]', $text);
        
        return $text;
    }
    
    /**
     * Redact sensitive data from array.
     *
     * @param  array  $data
     * @return array
     */
    private function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            // Check if key is sensitive
            if ($this->isSensitiveKey($key)) {
                $data[$key] = '[REDACTED]';
                continue;
            }
            
            // Recursively process arrays
            if (is_array($value)) {
                $data[$key] = $this->redactArray($value);
                continue;
            }
            
            // Process strings
            if (is_string($value)) {
                $data[$key] = $this->redactString($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Check if key name indicates sensitive data.
     *
     * @param  string|int  $key
     * @return bool
     */
    private function isSensitiveKey(string|int $key): bool
    {
        if (!is_string($key)) {
            return false;
        }
        
        $lowerKey = strtolower($key);
        
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($lowerKey, $sensitiveKey)) {
                return true;
            }
        }
        
        return false;
    }
}
