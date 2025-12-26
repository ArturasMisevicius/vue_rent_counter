<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Security Log Processor
 * 
 * Sanitizes logs to prevent sensitive data exposure
 */
final class SecurityLogProcessor implements ProcessorInterface
{
    private const SENSITIVE_KEYS = [
        'password',
        'token',
        'secret',
        'key',
        'authorization',
        'cookie',
        'session',
        'csrf_token',
        'api_key',
        'access_token',
        'refresh_token',
    ];

    private const PII_KEYS = [
        'email',
        'phone',
        'address',
        'ssn',
        'credit_card',
        'ip',
        'user_agent',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra = $this->sanitizeArray($record->extra);
        $record->context = $this->sanitizeArray($record->context);
        
        return $record;
    }

    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif ($this->isSensitiveKey($key)) {
                $data[$key] = '[REDACTED]';
            } elseif ($this->isPiiKey($key)) {
                $data[$key] = $this->hashValue($value);
            }
        }

        return $data;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);
        
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private function isPiiKey(string $key): bool
    {
        $key = strtolower($key);
        
        foreach (self::PII_KEYS as $piiKey) {
            if (str_contains($key, $piiKey)) {
                return true;
            }
        }

        return false;
    }

    private function hashValue(mixed $value): string
    {
        if (!is_string($value)) {
            $value = (string) $value;
        }

        return 'hash:' . substr(hash('sha256', $value), 0, 8);
    }
}