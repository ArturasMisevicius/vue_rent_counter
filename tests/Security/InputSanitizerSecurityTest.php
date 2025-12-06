<?php

declare(strict_types=1);

use App\Contracts\InputSanitizerInterface;
use App\Events\SecurityViolationDetected;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->sanitizer = app(InputSanitizerInterface::class);
    Event::fake();
    Log::spy();
});

describe('Path Traversal Prevention', function () {
    it('blocks direct path traversal patterns', function () {
        $attacks = [
            '../etc/passwd',
            '../../etc/passwd',
            '../../../etc/passwd',
            '..\\windows\\system32',
            '..',
            '../',
        ];
        
        foreach ($attacks as $attack) {
            expect(fn() => $this->sanitizer->sanitizeIdentifier($attack))
                ->toThrow(InvalidArgumentException::class, 'invalid pattern')
                ->and(fn() => Event::assertDispatched(SecurityViolationDetected::class));
        }
    });
    
    it('blocks obfuscated path traversal patterns before character removal', function () {
        // CRITICAL: These patterns would create ".." after character removal
        // The fix ensures they're rejected BEFORE the dangerous pattern is created
        $attacks = [
            'test.@.example',      // @ removed would create "test..example"
            'test.#.example',      // # removed would create "test..example"
            '.@./.@./etc/passwd',  // Multiple obfuscations creating "../etc/passwd"
            'test.!.!.example',    // Multiple invalid chars creating "test...example"
            'a.$.b',               // $ removed would create "a..b"
            'x.%.y',               // % removed would create "x..y"
            'p.&.q',               // & removed would create "p..q"
        ];
        
        foreach ($attacks as $attack) {
            try {
                $this->sanitizer->sanitizeIdentifier($attack);
                expect(false)->toBeTrue("Should have thrown for: {$attack}");
            } catch (InvalidArgumentException $e) {
                expect($e->getMessage())->toContain('invalid pattern');
            }
        }
    });
    
    it('blocks embedded path traversal patterns', function () {
        $attacks = [
            'valid..invalid',
            'test..example',
            'a..b..c',
            'x...y',           // Triple dots
            'p....q',          // Quadruple dots
        ];
        
        foreach ($attacks as $attack) {
            expect(fn() => $this->sanitizer->sanitizeIdentifier($attack))
                ->toThrow(InvalidArgumentException::class, 'invalid pattern');
        }
    });
    
    it('blocks path traversal with mixed valid and invalid characters', function () {
        // These combine valid characters with patterns that create ".." after removal
        $attacks = [
            'valid.@.path',
            'system.#.id',
            'aws.!.s3',
            'test.*.example',
        ];
        
        foreach ($attacks as $attack) {
            expect(fn() => $this->sanitizer->sanitizeIdentifier($attack))
                ->toThrow(InvalidArgumentException::class);
        }
    });
    
    it('allows valid identifiers with single dots', function () {
        $valid = [
            'system.id.456',
            'provider-123',
            'aws.s3.bucket',
            'test.example',
            'a.b.c.d.e',       // Multiple single dots
            'x.y',             // Simple case
        ];
        
        foreach ($valid as $input) {
            expect($this->sanitizer->sanitizeIdentifier($input))
                ->toBe($input);
        }
    });
    
    it('verifies dot collapse logic is removed', function () {
        // This test ensures the dangerous dot collapse regex is NOT present
        // If it were present, "test...example" might be "fixed" to "test.example"
        // Instead, it should be rejected
        
        $multiDotPatterns = [
            'test...example',
            'a....b',
            'x.....y',
        ];
        
        foreach ($multiDotPatterns as $pattern) {
            expect(fn() => $this->sanitizer->sanitizeIdentifier($pattern))
                ->toThrow(InvalidArgumentException::class, 'invalid pattern');
        }
    });
});

describe('Security Event Logging', function () {
    it('dispatches security events on path traversal', function () {
        try {
            $this->sanitizer->sanitizeIdentifier('../etc/passwd');
        } catch (InvalidArgumentException $e) {
            // Expected
        }
        
        Event::assertDispatched(SecurityViolationDetected::class, function ($event) {
            return $event->violationType === 'path_traversal'
                && str_contains($event->originalInput, '[')  // Redacted
                && $event->ipAddress !== null;  // IP hash present
        });
    });
    
    it('logs security violations with redacted PII', function () {
        try {
            $this->sanitizer->sanitizeIdentifier('../user@example.com/data');
        } catch (InvalidArgumentException $e) {
            // Expected
        }
        
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Path traversal')
                    && str_contains($context['original_input'], '[EMAIL]')  // Email redacted
                    && isset($context['ip_hash']);  // IP hashed
            });
    });
    
    it('includes tenant context in cache keys', function () {
        $user = \App\Models\User::factory()->create(['tenant_id' => 1]);
        $this->actingAs($user);
        
        $result1 = $this->sanitizer->sanitizeIdentifier('test-id');
        $result2 = $this->sanitizer->sanitizeIdentifier('test-id');
        
        // Should hit cache (same tenant)
        expect($result1)->toBe($result2);
    });
});

describe('Null Byte Injection Prevention', function () {
    it('removes null bytes from identifiers', function () {
        $input = "test\0example";
        $result = $this->sanitizer->sanitizeIdentifier($input);
        
        expect($result)->toBe('testexample')
            ->and(str_contains($result, "\0"))->toBeFalse();
    });
    
    it('removes null bytes from text', function () {
        $input = "Hello\0World";
        $result = $this->sanitizer->sanitizeText($input);
        
        expect($result)->toBe('HelloWorld')
            ->and(str_contains($result, "\0"))->toBeFalse();
    });
    
    it('removes null bytes from numeric input', function () {
        $input = "123\0.45";
        $result = $this->sanitizer->sanitizeNumeric($input);
        
        expect($result)->toBe(123.45);
    });
    
    it('removes null bytes from time input', function () {
        $input = "14\0:30";
        
        expect(fn() => $this->sanitizer->sanitizeTime($input))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('XSS Prevention', function () {
    it('removes dangerous script tags', function () {
        $attacks = [
            '<script>alert("XSS")</script>',
            '<iframe src="evil.com"></iframe>',
            '<object data="evil.swf"></object>',
            '<embed src="evil.swf">',
        ];
        
        foreach ($attacks as $attack) {
            $result = $this->sanitizer->sanitizeText($attack);
            expect($result)->not->toContain('<script>')
                ->and($result)->not->toContain('<iframe>')
                ->and($result)->not->toContain('<object>')
                ->and($result)->not->toContain('<embed>');
        }
    });
    
    it('removes dangerous event handlers', function () {
        $input = '<p onclick="alert(1)" onload="alert(2)">Test</p>';
        $result = $this->sanitizer->sanitizeText($input, allowBasicHtml: true);
        
        expect($result)->not->toContain('onclick')
            ->and($result)->not->toContain('onload');
    });
    
    it('removes javascript protocol handlers', function () {
        $attacks = [
            'javascript:alert(1)',
            'vbscript:msgbox(1)',
            'data:text/html,<script>alert(1)</script>',
        ];
        
        foreach ($attacks as $attack) {
            $result = $this->sanitizer->sanitizeText($attack);
            expect($result)->not->toContain('javascript:')
                ->and($result)->not->toContain('vbscript:')
                ->and($result)->not->toContain('data:text/html');
        }
    });
});

describe('Input Validation', function () {
    it('enforces maximum length on identifiers', function () {
        $longInput = str_repeat('a', 300);
        
        expect(fn() => $this->sanitizer->sanitizeIdentifier($longInput))
            ->toThrow(InvalidArgumentException::class, 'exceeds maximum length');
    });
    
    it('rejects empty identifiers after sanitization', function () {
        $input = '@@@###!!!';  // All invalid chars
        
        expect(fn() => $this->sanitizer->sanitizeIdentifier($input))
            ->toThrow(InvalidArgumentException::class, 'only invalid characters');
    });
    
    it('enforces numeric overflow protection', function () {
        expect(fn() => $this->sanitizer->sanitizeNumeric(1000000))
            ->toThrow(InvalidArgumentException::class, 'exceeds maximum');
    });
    
    it('rejects negative numbers', function () {
        expect(fn() => $this->sanitizer->sanitizeNumeric(-10))
            ->toThrow(InvalidArgumentException::class, 'Negative values');
    });
    
    it('validates time format strictly', function () {
        $invalid = ['25:00', '14:60', '1:30', '14-30', 'invalid'];
        
        foreach ($invalid as $time) {
            expect(fn() => $this->sanitizer->sanitizeTime($time))
                ->toThrow(InvalidArgumentException::class, 'Invalid time format');
        }
    });
});

describe('Cache Security', function () {
    it('isolates cache by tenant', function () {
        $user1 = \App\Models\User::factory()->create(['tenant_id' => 1]);
        $user2 = \App\Models\User::factory()->create(['tenant_id' => 2]);
        
        // Tenant 1 sanitizes
        $this->actingAs($user1);
        $result1 = $this->sanitizer->sanitizeIdentifier('shared-id');
        
        // Tenant 2 sanitizes same ID
        $this->actingAs($user2);
        $result2 = $this->sanitizer->sanitizeIdentifier('shared-id');
        
        // Both should get same result but from separate cache entries
        expect($result1)->toBe($result2)->toBe('shared-id');
    });
    
    it('does not clear entire application cache', function () {
        // Set a non-sanitizer cache value
        cache()->put('test-key', 'test-value', 3600);
        
        // Clear sanitizer cache
        $this->sanitizer->clearCache();
        
        // Non-sanitizer cache should still exist
        expect(cache()->get('test-key'))->toBe('test-value');
    });
});

describe('Performance & DoS Prevention', function () {
    it('uses request-level memoization', function () {
        $input = 'test-identifier-123';
        
        $start = microtime(true);
        $result1 = $this->sanitizer->sanitizeIdentifier($input);
        $time1 = microtime(true) - $start;
        
        $start = microtime(true);
        $result2 = $this->sanitizer->sanitizeIdentifier($input);
        $time2 = microtime(true) - $start;
        
        expect($result1)->toBe($result2)
            ->and($time2)->toBeLessThan($time1 * 0.5);  // At least 50% faster
    });
    
    it('handles large input efficiently', function () {
        $input = str_repeat('a', 255);
        
        $start = microtime(true);
        $result = $this->sanitizer->sanitizeIdentifier($input);
        $time = microtime(true) - $start;
        
        expect($time)->toBeLessThan(0.01)  // Less than 10ms
            ->and(strlen($result))->toBe(255);
    });
});
