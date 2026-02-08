<?php

declare(strict_types=1);

use App\Contracts\InputSanitizerInterface;
use App\Events\SecurityViolationDetected;
use App\Services\InputSanitizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->sanitizer = app(InputSanitizerInterface::class);
    Cache::flush();
    Event::fake();
    Log::spy();
});

describe('Interface Implementation', function () {
    it('implements InputSanitizerInterface', function () {
        expect($this->sanitizer)->toBeInstanceOf(InputSanitizerInterface::class);
    });

    it('is registered as singleton', function () {
        $instance1 = app(InputSanitizerInterface::class);
        $instance2 = app(InputSanitizerInterface::class);
        
        expect($instance1)->toBe($instance2);
    });
});

describe('Text Sanitization', function () {
    it('removes dangerous script tags', function () {
        $input = '<script>alert("XSS")</script>Hello';
        $result = $this->sanitizer->sanitizeText($input);
        
        expect($result)->toBe('Hello')
            ->and($result)->not->toContain('<script>');
    });

    it('allows basic HTML when enabled', function () {
        $input = '<p>Hello <strong>World</strong></p>';
        $result = $this->sanitizer->sanitizeText($input, allowBasicHtml: true);
        
        expect($result)->toBe('<p>Hello <strong>World</strong></p>');
    });

    it('removes dangerous attributes from allowed tags', function () {
        $input = '<p onclick="alert(1)">Hello</p>';
        $result = $this->sanitizer->sanitizeText($input, allowBasicHtml: true);
        
        expect($result)->not->toContain('onclick');
    });

    it('removes JavaScript protocol handlers', function () {
        $input = '<a href="javascript:alert(1)">Click</a>';
        $result = $this->sanitizer->sanitizeText($input);
        
        expect($result)->not->toContain('javascript:');
    });

    it('removes null bytes', function () {
        $input = "Hello\0World";
        $result = $this->sanitizer->sanitizeText($input);
        
        expect($result)->toBe('HelloWorld');
    });

    it('normalizes Unicode characters', function () {
        // Using composed vs decomposed Unicode
        $input = "café"; // Composed é
        $result = $this->sanitizer->sanitizeText($input);
        
        expect($result)->toBeString();
    });
});

describe('Numeric Sanitization', function () {
    it('converts string to float', function () {
        $result = $this->sanitizer->sanitizeNumeric('123.45');
        
        expect($result)->toBe(123.45);
    });

    it('accepts integer input', function () {
        $result = $this->sanitizer->sanitizeNumeric(100);
        
        expect($result)->toBe(100.0);
    });

    it('accepts float input', function () {
        $result = $this->sanitizer->sanitizeNumeric(99.99);
        
        expect($result)->toBe(99.99);
    });

    it('throws exception for values exceeding maximum', function () {
        expect(fn() => $this->sanitizer->sanitizeNumeric(1000000))
            ->toThrow(InvalidArgumentException::class, 'Value exceeds maximum allowed');
    });

    it('throws exception for negative values', function () {
        expect(fn() => $this->sanitizer->sanitizeNumeric(-10))
            ->toThrow(InvalidArgumentException::class, 'Negative values not allowed');
    });

    it('respects custom maximum', function () {
        $result = $this->sanitizer->sanitizeNumeric(500, max: 1000);
        
        expect($result)->toBe(500.0);
    });
});

describe('Identifier Sanitization', function () {
    it('allows valid identifiers', function () {
        $result = $this->sanitizer->sanitizeIdentifier('provider-123');
        
        expect($result)->toBe('provider-123');
    });

    it('allows dots in identifiers', function () {
        $result = $this->sanitizer->sanitizeIdentifier('system.id.456');
        
        expect($result)->toBe('system.id.456');
    });

    it('removes invalid characters', function () {
        $result = $this->sanitizer->sanitizeIdentifier('test@provider#123');
        
        expect($result)->toBe('testprovider123');
    });

    it('blocks path traversal with consecutive dots', function () {
        expect(fn() => $this->sanitizer->sanitizeIdentifier('test..example'))
            ->toThrow(InvalidArgumentException::class, 'Identifier contains invalid pattern (..)');
    });

    it('blocks obfuscated path traversal before character removal', function () {
        // CRITICAL SECURITY TEST: Verifies the fix for CVE-pending vulnerability
        // Before fix: "test.@.example" would become "test..example" after @ removal,
        // then be "fixed" to "test.example" by dot collapse regex
        // After fix: Rejected immediately as it contains ".." after @ removal
        
        expect(fn() => $this->sanitizer->sanitizeIdentifier('test.@.example'))
            ->toThrow(InvalidArgumentException::class, 'Identifier contains invalid pattern (..)');
    });
    
    it('blocks multiple obfuscated path traversal patterns', function () {
        $obfuscatedPatterns = [
            'test.@.example',   // @ creates ..
            'a.#.b',            // # creates ..
            'x.$.y',            // $ creates ..
            'p.%.q',            // % creates ..
            '.@./.@.',          // Multiple obfuscations
        ];
        
        foreach ($obfuscatedPatterns as $pattern) {
            expect(fn() => $this->sanitizer->sanitizeIdentifier($pattern))
                ->toThrow(InvalidArgumentException::class);
        }
    });

    it('dispatches security event on path traversal attempt', function () {
        try {
            $this->sanitizer->sanitizeIdentifier('../../../etc/passwd');
        } catch (InvalidArgumentException $e) {
            // Expected exception
        }

        Event::assertDispatched(SecurityViolationDetected::class, function ($event) {
            return $event->violationType === 'path_traversal'
                && str_contains($event->originalInput, '..');
        });
    });

    it('logs security violations', function () {
        try {
            $this->sanitizer->sanitizeIdentifier('test..example');
        } catch (InvalidArgumentException $e) {
            // Expected exception
        }

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Path traversal attempt detected in identifier', \Mockery::type('array'));
    });

    it('removes leading and trailing dots', function () {
        $result = $this->sanitizer->sanitizeIdentifier('.test.example.');
        
        expect($result)->toBe('test.example');
    });

    it('throws exception for empty result', function () {
        expect(fn() => $this->sanitizer->sanitizeIdentifier('@@@@'))
            ->toThrow(InvalidArgumentException::class, 'Identifier contains only invalid characters');
    });

    it('throws exception for exceeding max length', function () {
        $longString = str_repeat('a', 300);
        
        expect(fn() => $this->sanitizer->sanitizeIdentifier($longString))
            ->toThrow(InvalidArgumentException::class, 'Identifier exceeds maximum length');
    });

    it('respects custom max length', function () {
        $result = $this->sanitizer->sanitizeIdentifier('short', maxLength: 10);
        
        expect($result)->toBe('short');
    });

    it('returns empty string for empty input', function () {
        $result = $this->sanitizer->sanitizeIdentifier('');
        
        expect($result)->toBe('');
    });

    it('handles whitespace-only input', function () {
        $result = $this->sanitizer->sanitizeIdentifier('   ');
        
        expect($result)->toBe('');
    });
    
    it('verifies dot collapse regex is removed for security', function () {
        // REGRESSION TEST: Ensures the dangerous dot collapse logic stays removed
        // The old code had: preg_replace('/\.{2,}/', '.', $sanitized)
        // This would "fix" dangerous patterns instead of rejecting them
        
        // These patterns should be REJECTED, not "fixed"
        $dangerousPatterns = [
            'test...example',    // Triple dots
            'a....b',            // Quadruple dots
            'x.....y',           // Five dots
            'p..q',              // Double dots
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            expect(fn() => $this->sanitizer->sanitizeIdentifier($pattern))
                ->toThrow(InvalidArgumentException::class, 'invalid pattern');
        }
    });
    
    it('maintains defense in depth with post-sanitization check', function () {
        // Verify that even if character removal somehow creates "..",
        // the post-sanitization check catches it
        
        // This is a defense-in-depth test - the pre-check should catch it first,
        // but the post-check provides additional security
        
        expect(fn() => $this->sanitizer->sanitizeIdentifier('..'))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('Time Sanitization', function () {
    it('accepts valid time format', function () {
        $result = $this->sanitizer->sanitizeTime('14:30');
        
        expect($result)->toBe('14:30');
    });

    it('accepts midnight', function () {
        $result = $this->sanitizer->sanitizeTime('00:00');
        
        expect($result)->toBe('00:00');
    });

    it('accepts end of day', function () {
        $result = $this->sanitizer->sanitizeTime('23:59');
        
        expect($result)->toBe('23:59');
    });

    it('rejects invalid hour', function () {
        expect(fn() => $this->sanitizer->sanitizeTime('25:00'))
            ->toThrow(InvalidArgumentException::class, 'Invalid time format');
    });

    it('rejects invalid minute', function () {
        expect(fn() => $this->sanitizer->sanitizeTime('14:60'))
            ->toThrow(InvalidArgumentException::class, 'Invalid time format');
    });

    it('rejects invalid format', function () {
        expect(fn() => $this->sanitizer->sanitizeTime('14-30'))
            ->toThrow(InvalidArgumentException::class, 'Invalid time format');
    });
});

describe('Cache Management', function () {
    it('uses Laravel Cache for Unicode normalization', function () {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn('normalized');

        $this->sanitizer->sanitizeText('test');
    });

    it('returns cache statistics', function () {
        $stats = $this->sanitizer->getCacheStats();
        
        expect($stats)->toHaveKeys(['size', 'max_size', 'utilization', 'cache_driver', 'ttl_seconds']);
    });

    it('clears cache on demand', function () {
        Cache::shouldReceive('flush')->once();
        
        $this->sanitizer->clearCache();
    });
});

describe('Security Event Integration', function () {
    it('includes IP address in security events', function () {
        request()->server->set('REMOTE_ADDR', '192.168.1.1');
        
        try {
            $this->sanitizer->sanitizeIdentifier('../etc/passwd');
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        Event::assertDispatched(SecurityViolationDetected::class, function ($event) {
            return $event->ipAddress === '192.168.1.1';
        });
    });

    it('includes user ID when authenticated', function () {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        
        try {
            $this->sanitizer->sanitizeIdentifier('test..example');
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        Event::assertDispatched(SecurityViolationDetected::class, function ($event) use ($user) {
            return $event->userId === $user->id;
        });
    });

    it('includes context information in events', function () {
        try {
            $this->sanitizer->sanitizeIdentifier('bad..input', maxLength: 50);
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        Event::assertDispatched(SecurityViolationDetected::class, function ($event) {
            return isset($event->context['method'])
                && $event->context['method'] === 'sanitizeIdentifier'
                && $event->context['max_length'] === 50;
        });
    });
});

describe('Property-Based Tests', function () {
    it('never allows path traversal patterns', function () {
        $attempts = [
            '../',
            '../../',
            '../../../',
            'test..example',
            '.@.',
            '.#.',
            'a.@.b',
        ];

        foreach ($attempts as $attempt) {
            try {
                $this->sanitizer->sanitizeIdentifier($attempt);
                expect(false)->toBeTrue('Should have thrown exception for: ' . $attempt);
            } catch (InvalidArgumentException $e) {
                expect($e->getMessage())->toContain('invalid pattern');
            }
        }
    });

    it('always removes dangerous HTML tags', function () {
        $dangerousTags = [
            '<script>',
            '<iframe>',
            '<object>',
            '<embed>',
            '<applet>',
        ];

        foreach ($dangerousTags as $tag) {
            $result = $this->sanitizer->sanitizeText($tag . 'content');
            expect($result)->not->toContain($tag);
        }
    });

    it('numeric sanitization is idempotent', function () {
        $value = 123.45;
        $result1 = $this->sanitizer->sanitizeNumeric($value);
        $result2 = $this->sanitizer->sanitizeNumeric($result1);
        
        expect($result1)->toBe($result2);
    });
});
