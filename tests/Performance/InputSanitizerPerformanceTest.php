<?php

declare(strict_types=1);

use App\Contracts\InputSanitizerInterface;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->sanitizer = app(InputSanitizerInterface::class);
    Cache::flush();
});

describe('InputSanitizer Performance', function () {
    it('benefits from request-level memoization', function () {
        $input = 'test-identifier-123';
        
        // First call - no cache
        $start = microtime(true);
        $result1 = $this->sanitizer->sanitizeIdentifier($input);
        $time1 = microtime(true) - $start;
        
        // Second call - should hit request cache
        $start = microtime(true);
        $result2 = $this->sanitizer->sanitizeIdentifier($input);
        $time2 = microtime(true) - $start;
        
        expect($result1)->toBe($result2)
            ->and($time2)->toBeLessThan($time1 * 0.5); // At least 50% faster
    });
    
    it('maintains or improves performance after removing dot collapse regex', function () {
        // PERFORMANCE REGRESSION TEST
        // Removing the dot collapse regex should slightly improve performance
        // or at minimum not degrade it
        
        $testInputs = [
            'system.id.456',
            'provider-123',
            'aws.s3.bucket.name',
            'test.example.com',
        ];
        
        $totalTime = 0;
        $iterations = 100;
        
        foreach ($testInputs as $input) {
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $this->sanitizer->sanitizeIdentifier($input);
            }
            $totalTime += microtime(true) - $start;
        }
        
        $avgTimePerCall = ($totalTime / (count($testInputs) * $iterations)) * 1000000; // Convert to microseconds
        
        // Should complete in under 200 microseconds per call on average
        expect($avgTimePerCall)->toBeLessThan(200);
    });
    
    it('uses optimized cache key generation', function () {
        $input = 'test-string-for-unicode-normalization';
        
        // Warm up
        $this->sanitizer->sanitizeIdentifier($input);
        
        // Measure with optimized hash
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->sanitizer->sanitizeIdentifier("test-{$i}");
        }
        $optimizedTime = microtime(true) - $start;
        
        // Should complete in reasonable time
        expect($optimizedTime)->toBeLessThan(0.1); // 100ms for 100 operations
    });
    
    it('efficiently removes dangerous attributes', function () {
        $input = '<p onclick="alert(1)" onload="alert(2)" onerror="alert(3)">Test</p>';
        
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->sanitizer->sanitizeText($input, allowBasicHtml: true);
        }
        $time = microtime(true) - $start;
        
        // Should complete quickly with combined regex
        expect($time)->toBeLessThan(0.05); // 50ms for 100 operations
    });
    
    it('combines protocol handler removal efficiently', function () {
        $input = 'javascript:alert(1) vbscript:alert(2) data:text/html,<script>alert(3)</script>';
        
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->sanitizer->sanitizeText($input);
        }
        $time = microtime(true) - $start;
        
        // Should be faster with combined regex
        expect($time)->toBeLessThan(0.05); // 50ms for 100 operations
    });
    
    it('caches normalizer_normalize function check', function () {
        // Multiple calls should not repeatedly check function_exists
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $this->sanitizer->sanitizeIdentifier("test-{$i}");
        }
        $time = microtime(true) - $start;
        
        // Should complete quickly
        expect($time)->toBeLessThan(0.5); // 500ms for 1000 operations
    });
    
    it('reports accurate cache statistics', function () {
        // Sanitize some identifiers
        $this->sanitizer->sanitizeIdentifier('test-1');
        $this->sanitizer->sanitizeIdentifier('test-2');
        $this->sanitizer->sanitizeIdentifier('test-1'); // Duplicate
        
        $stats = $this->sanitizer->getCacheStats();
        
        expect($stats)->toHaveKeys([
            'size',
            'max_size',
            'utilization',
            'cache_driver',
            'ttl_seconds',
            'request_cache_size',
            'request_cache_hits'
        ])
        ->and($stats['request_cache_size'])->toBeGreaterThan(0)
        ->and($stats['max_size'])->toBe(500);
    });
});

describe('InputSanitizer Security with Performance', function () {
    it('checks path traversal before AND after character removal', function () {
        // Test that both checks are in place
        $attempts = [
            '..',           // Direct attempt
            '../',          // With slash
            'test..test',   // Embedded
            'test.@.test',  // Obfuscated (@ will be removed)
        ];
        
        foreach ($attempts as $attempt) {
            try {
                $this->sanitizer->sanitizeIdentifier($attempt);
                expect(false)->toBeTrue("Should have thrown for: {$attempt}");
            } catch (InvalidArgumentException $e) {
                expect($e->getMessage())->toContain('invalid pattern');
            }
        }
    });
    
    it('maintains security while using request cache', function () {
        // First call should detect and throw
        try {
            $this->sanitizer->sanitizeIdentifier('test..example');
            expect(false)->toBeTrue('Should have thrown');
        } catch (InvalidArgumentException $e) {
            expect($e->getMessage())->toContain('invalid pattern');
        }
        
        // Second call should also throw (not return cached invalid result)
        try {
            $this->sanitizer->sanitizeIdentifier('test..example');
            expect(false)->toBeTrue('Should have thrown on second call');
        } catch (InvalidArgumentException $e) {
            expect($e->getMessage())->toContain('invalid pattern');
        }
    });
});
