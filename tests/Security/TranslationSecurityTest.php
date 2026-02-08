<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Http\Middleware\SecureTranslationMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class TranslationSecurityTest extends TestCase
{
    public function test_blocks_invalid_locale(): void
    {
        $request = Request::create('/test', 'GET', ['locale' => '../../../etc/passwd']);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Invalid locale attempted', \Mockery::on(function ($context) {
                $this->assertArrayHasKey('locale_hash', $context);
                $this->assertArrayHasKey('ip_hash', $context);
                $this->assertArrayHasKey('context', $context);
                $this->assertEquals('security_violation', $context['context']);
                return true;
            }));
        
        $middleware = new SecureTranslationMiddleware();
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Invalid locale');
        
        $middleware->handle($request, function () {
            return response('OK');
        });
    }

    public function test_blocks_path_traversal_in_translation_key(): void
    {
        $request = Request::create('/test', 'GET', ['translation_key' => '../../../config/app']);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Invalid translation key attempted', \Mockery::any());
        
        $middleware = new SecureTranslationMiddleware();
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($request, function () {
            return response('OK');
        });
    }

    public function test_blocks_xss_attempts(): void
    {
        $request = Request::create('/test', 'GET', ['locale' => '<script>alert("xss")</script>']);
        
        $middleware = new SecureTranslationMiddleware();
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($request, function () {
            return response('OK');
        });
    }

    public function test_blocks_sql_injection_attempts(): void
    {
        $request = Request::create('/test', 'GET', ['translate' => "'; DROP TABLE users; --"]);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Suspicious translation parameter detected', \Mockery::any());
        
        $middleware = new SecureTranslationMiddleware();
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($request, function () {
            return response('OK');
        });
    }

    public function test_rate_limits_translation_requests(): void
    {
        RateLimiter::clear('translation-requests:' . hash('sha256', '127.0.0.1'));
        
        $middleware = new SecureTranslationMiddleware();
        
        // Make 101 requests (exceeding the limit of 100)
        for ($i = 0; $i < 101; $i++) {
            $request = Request::create('/test', 'GET', ['locale' => 'en']);
            $request->server->set('REMOTE_ADDR', '127.0.0.1');
            
            try {
                $middleware->handle($request, function () {
                    return response('OK');
                });
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                if ($i >= 100) {
                    $this->assertEquals(429, $e->getStatusCode());
                    return;
                }
            }
        }
        
        $this->fail('Rate limiting should have been triggered');
    }

    public function test_allows_valid_translation_requests(): void
    {
        $request = Request::create('/test', 'GET', [
            'locale' => 'en',
            'translation_key' => 'common.welcome'
        ]);
        
        $middleware = new SecureTranslationMiddleware();
        
        $response = $middleware->handle($request, function () {
            return response('OK');
        });
        
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_validates_all_translation_parameters(): void
    {
        $suspiciousParams = [
            'locale' => 'javascript:alert(1)',
            'lang' => 'onload=alert(1)',
            'translate' => 'UNION SELECT * FROM users',
            'i18n' => 'exec(rm -rf /)',
        ];
        
        $middleware = new SecureTranslationMiddleware();
        
        foreach ($suspiciousParams as $param => $value) {
            $request = Request::create('/test', 'GET', [$param => $value]);
            
            $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
            
            try {
                $middleware->handle($request, function () {
                    return response('OK');
                });
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertEquals(400, $e->getStatusCode());
                continue;
            }
            
            $this->fail("Should have blocked suspicious parameter: {$param}");
        }
    }
}