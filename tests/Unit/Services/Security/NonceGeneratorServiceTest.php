<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Services\Security\NonceGeneratorService;
use App\ValueObjects\SecurityNonce;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \App\Services\Security\NonceGeneratorService
 */
final class NonceGeneratorServiceTest extends TestCase
{
    private NonceGeneratorService $service;
    private CacheRepository&MockInterface $cache;
    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = Mockery::mock(CacheRepository::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->service = new NonceGeneratorService($this->cache, $this->logger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generates_nonce(): void
    {
        // No longer expecting debug logging or cache operations for performance
        $nonce = $this->service->generateNonce();

        $this->assertInstanceOf(SecurityNonce::class, $nonce);
        $this->assertTrue($nonce->isValid());
    }

    public function test_generates_nonce_with_custom_bytes(): void
    {
        // No longer expecting debug logging or cache operations for performance
        $nonce = $this->service->generateNonce(32);

        $this->assertEquals(64, strlen($nonce->value)); // 32 bytes = 64 hex chars
    }

    public function test_caches_nonce_per_request(): void
    {
        $request = new Request();
        
        // First call should generate and cache in request attributes
        $nonce1 = $this->service->getNonce($request);
        
        // Second call should return cached nonce from request attributes
        $nonce2 = $this->service->getNonce($request);

        $this->assertSame($nonce1, $nonce2);
    }

    public function test_validates_valid_nonce(): void
    {
        $nonce = SecurityNonce::generate();
        
        $result = $this->service->validateNonce($nonce->base64Encoded);

        $this->assertTrue($result);
    }

    public function test_validates_expired_nonce(): void
    {
        // Create a nonce that's immediately expired (maxAge = 0)
        $nonce = SecurityNonce::generate();
        
        // Use a very small maxAge to simulate expiry
        $result = $this->service->validateNonce($nonce->base64Encoded, -1);

        $this->assertFalse($result);
    }

    public function test_validates_invalid_nonce(): void
    {
        $this->logger->shouldReceive('warning')->once();

        $result = $this->service->validateNonce('invalid-nonce');

        $this->assertFalse($result);
    }

    public function test_logs_generation_errors(): void
    {
        $this->logger->shouldReceive('error')->once();

        // Force an error by using reflection to call with invalid bytes
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->generateNonce(8); // Too few bytes
    }

    public function test_nonce_generation_performance(): void
    {
        // Test that nonce generation is fast (performance optimization)
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $this->service->generateNonce();
        }
        
        $duration = (microtime(true) - $startTime) * 1000; // Convert to ms
        
        // Should generate 100 nonces in under 50ms
        $this->assertLessThan(50, $duration);
    }

    public function test_clear_expired_nonces_returns_zero(): void
    {
        // Current implementation relies on cache TTL
        $result = $this->service->clearExpiredNonces();

        $this->assertEquals(0, $result);
    }
}