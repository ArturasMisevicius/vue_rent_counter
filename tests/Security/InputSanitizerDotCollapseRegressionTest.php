<?php

declare(strict_types=1);

/**
 * Security Regression Tests for InputSanitizer Dot Collapse Removal
 * 
 * CRITICAL SECURITY FIX: 2024-12-06
 * CVE: Pending assignment
 * CVSS Score: 8.1 (High)
 * 
 * This test suite specifically verifies the fix for the path traversal bypass
 * vulnerability where the dot collapse regex was masking security issues.
 * 
 * VULNERABILITY DETAILS:
 * - Old code had: preg_replace('/\.{2,}/', '.', $sanitized)
 * - This would "fix" dangerous patterns like "test..example" to "test.example"
 * - Attackers could bypass path traversal checks by inserting invalid characters
 *   between dots (e.g., "test.@.example" → "test..example" → "test.example")
 * 
 * FIX:
 * - Removed the dot collapse regex entirely
 * - Path traversal check now occurs BEFORE character removal
 * - Defense-in-depth: Additional check AFTER character removal
 * 
 * @see docs/security/input-sanitizer-security-fix.md
 * @see docs/security/SECURITY_PATCH_2024-12-05.md
 */

use App\Contracts\InputSanitizerInterface;
use App\Events\SecurityViolationDetected;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->sanitizer = app(InputSanitizerInterface::class);
    Event::fake();
    Log::spy();
});

describe('Dot Collapse Removal - Security Regression Tests', function () {
    it('rejects patterns that would be fixed by dot collapse', function () {
        // These patterns contain multiple consecutive dots
        // Old code would "fix" them, new code rejects them
        $patterns = [
            'test..example',
            'a...b',
            'x....y',
            'p.....q',
        ];
        
        foreach ($patterns as $pattern) {
            try {
                $this->sanitizer->sanitizeIdentifier($pattern);
                expect(false)->toBeTrue("Should have rejected: {$pattern}");
            } catch (InvalidArgumentException $e) {
                expect($e->getMessage())->toContain('invalid pattern');
            }
        }
    });
    
    it('rejects obfuscated patterns before they create double dots', function () {
        // CRITICAL: These are the attack vectors that bypass the old implementation
        $attackVectors = [
            [
                'input' => 'test.@.example',
                'after_removal' => 'test..example',
                'description' => '@ between dots creates ..',
            ],
            [
                'input' => '.@./.@./etc/passwd',
                'after_removal' => '../etc/passwd',
                'description' => 'Multiple @ create path traversal',
            ],
            [
                'input' => 'system.#.id',
                'after_removal' => 'system..id',
                'description' => '# between dots creates ..',
            ],
            [
                'input' => 'aws.$.s3',
                'after_removal' => 'aws..s3',
                'description' => '$ between dots creates ..',
            ],
            [
                'input' => 'test.%.example',
                'after_removal' => 'test..example',
                'description' => '% between dots creates ..',
            ],
        ];
        
        foreach ($attackVectors as $vector) {
            try {
                $this->sanitizer->sanitizeIdentifier($vector['input']);
                expect(false)->toBeTrue("Should have rejected: {$vector['input']} ({$vector['description']})");
            } catch (InvalidArgumentException $e) {
                expect($e->getMessage())->toContain('invalid pattern');
            }
        }
    });
    
    it('logs security violations for obfuscated patterns', function () {
        try {
            $this->sanitizer->sanitizeIdentifier('test.@.example');
        } catch (InvalidArgumentException $e) {
            // Expected
        }
        
        // Verify security event was dispatched
        Event::assertDispatched(SecurityViolationDetected::class, function ($event) {
            return $event->violationType === 'path_traversal';
        });
        
        // Verify warning was logged
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Path traversal attempt');
            });
    });
    
    it('maintains valid single dot functionality', function () {
        // Ensure the fix doesn't break legitimate use cases
        $validPatterns = [
            'system.id.456',
            'provider-123',
            'aws.s3.bucket',
            'test.example',
            'a.b.c.d.e',
        ];
        
        foreach ($validPatterns as $pattern) {
            $result = $this->sanitizer->sanitizeIdentifier($pattern);
            expect($result)->toBe($pattern);
        }
    });
    
    it('verifies defense in depth with post-sanitization check', function () {
        // Even if the pre-check somehow fails, the post-check should catch it
        // This tests the defense-in-depth architecture
        
        $directDoubleD ots = [
            '..',
            '../',
            '../../',
        ];
        
        foreach ($directDoubleDots as $pattern) {
            expect(fn() => $this->sanitizer->sanitizeIdentifier($pattern))
                ->toThrow(InvalidArgumentException::class);
        }
    });
    
    it('handles edge cases with mixed valid and invalid characters', function () {
        $edgeCases = [
            'valid.@.path.#.example',  // Multiple obfuscations
            'test.!.!.example',         // Multiple invalid chars in sequence
            'a.*.b.$.c',                // Mixed invalid chars
        ];
        
        foreach ($edgeCases as $case) {
            expect(fn() => $this->sanitizer->sanitizeIdentifier($case))
                ->toThrow(InvalidArgumentException::class);
        }
    });
    
    it('ensures no false positives for valid complex identifiers', function () {
        // Complex but valid identifiers should still work
        $complexValid = [
            'system-v2.api.endpoint',
            'aws-s3-bucket.region-us-east-1',
            'provider_123.service_456',
            'test-env.config.production',
        ];
        
        foreach ($complexValid as $identifier) {
            $result = $this->sanitizer->sanitizeIdentifier($identifier);
            expect($result)->toBeString()
                ->and($result)->not->toBeEmpty();
        }
    });
});

describe('Dot Collapse Removal - Attack Simulation', function () {
    it('simulates real-world path traversal attack attempts', function () {
        // Simulate actual attack patterns that might be used in the wild
        $realWorldAttacks = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32',
            '.@./.@./.@./etc/shadow',
            'test.#.#.#.example',
            'valid.$.$.malicious',
        ];
        
        foreach ($realWorldAttacks as $attack) {
            try {
                $this->sanitizer->sanitizeIdentifier($attack);
                expect(false)->toBeTrue("Attack should have been blocked: {$attack}");
            } catch (InvalidArgumentException $e) {
                // Attack successfully blocked
                expect($e->getMessage())->toContain('invalid pattern');
            }
            
            // Verify security event was dispatched for each attack
            Event::assertDispatched(SecurityViolationDetected::class);
        }
    });
    
    it('prevents bypass attempts with URL encoding', function () {
        // Attackers might try URL-encoded characters
        // Note: These should be decoded before reaching the sanitizer
        $encodedAttempts = [
            'test%2E%2Eexample',  // URL-encoded ..
            'test.%40.example',    // URL-encoded @
        ];
        
        foreach ($encodedAttempts as $attempt) {
            // The sanitizer should handle these after URL decoding
            $decoded = urldecode($attempt);
            
            if (str_contains($decoded, '..')) {
                expect(fn() => $this->sanitizer->sanitizeIdentifier($decoded))
                    ->toThrow(InvalidArgumentException::class);
            }
        }
    });
});

describe('Dot Collapse Removal - Performance Impact', function () {
    it('verifies no performance degradation after fix', function () {
        // Removing a regex operation should improve or maintain performance
        $testInputs = [
            'system.id.456',
            'provider-123',
            'aws.s3.bucket.name',
        ];
        
        $iterations = 1000;
        $start = microtime(true);
        
        foreach ($testInputs as $input) {
            for ($i = 0; $i < $iterations; $i++) {
                $this->sanitizer->sanitizeIdentifier($input);
            }
        }
        
        $totalTime = microtime(true) - $start;
        $avgTimePerCall = ($totalTime / (count($testInputs) * $iterations)) * 1000000; // microseconds
        
        // Should complete in under 200 microseconds per call
        expect($avgTimePerCall)->toBeLessThan(200);
    });
});
