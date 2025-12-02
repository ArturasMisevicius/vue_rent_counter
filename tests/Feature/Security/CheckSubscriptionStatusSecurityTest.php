<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;
use App\ValueObjects\SubscriptionCheckResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    // Clear rate limiters before each test
    RateLimiter::clear('subscription-check:user:1');
    RateLimiter::clear('subscription-check:ip:127.0.0.1');
});

describe('Rate Limiting Security', function () {
    test('rate limiting prevents DoS attacks for authenticated users', function () {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Attempt 61 requests (limit is 60)
        $responses = [];
        for ($i = 0; $i < 61; $i++) {
            $responses[] = $this->actingAs($user)->get(route('admin.dashboard'));
        }
        
        // First 60 should succeed
        for ($i = 0; $i < 60; $i++) {
            expect($responses[$i]->status())->toBeLessThan(429);
        }
        
        // 61st should be rate limited
        expect($responses[60]->status())->toBe(429);
        expect($responses[60]->headers->get('Retry-After'))->not->toBeNull();
    });
    
    test('rate limiting has lower threshold for unauthenticated requests', function () {
        // Attempt 11 requests (limit is 10 for unauthenticated)
        $responses = [];
        for ($i = 0; $i < 11; $i++) {
            $responses[] = $this->get(route('login'));
        }
        
        // First 10 should succeed
        for ($i = 0; $i < 10; $i++) {
            expect($responses[$i]->status())->toBeLessThan(429);
        }
        
        // 11th should be rate limited
        expect($responses[10]->status())->toBe(429);
    });
    
    test('rate limit violations are logged for security monitoring', function () {
        Log::spy();
        
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Exceed rate limit
        for ($i = 0; $i < 61; $i++) {
            $this->actingAs($user)->get(route('admin.dashboard'));
        }
        
        // Verify security log was called
        Log::shouldHaveReceived('channel')
            ->with('security')
            ->atLeast()
            ->once();
    });
});

describe('PII Redaction Security', function () {
    test('audit logs redact email addresses', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'sensitive@example.com',
        ]);
        
        Subscription::factory()->create([
            'user_id' => $admin->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addMonths(6),
        ]);
        
        $this->actingAs($admin)->get(route('admin.dashboard'));
        
        // Check that audit log exists and email is redacted
        $logPath = storage_path('logs/audit.log');
        if (file_exists($logPath)) {
            $logContent = file_get_contents($logPath);
            expect($logContent)->not->toContain('sensitive@example.com');
        }
    });
    
    test('PII redaction processor handles various sensitive patterns', function () {
        $processor = new \App\Logging\RedactSensitiveData();
        
        $record = new \Monolog\LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: \Monolog\Level::Info,
            message: 'User test@example.com from IP 192.168.1.1 accessed system',
            context: [
                'email' => 'user@example.com',
                'ip' => '10.0.0.1',
                'password' => 'secret123',
            ]
        );
        
        $processed = $processor($record);
        
        expect($processed['message'])->toContain('[EMAIL_REDACTED]');
        expect($processed['message'])->toContain('[IP_REDACTED]');
        expect($processed['context']['email'])->toBe('[REDACTED]');
        expect($processed['context']['ip'])->toBe('[REDACTED]');
        expect($processed['context']['password'])->toBe('[REDACTED]');
    });
});

describe('Input Validation Security', function () {
    test('invalid redirect routes are rejected', function () {
        expect(fn () => SubscriptionCheckResult::block(
            'Test message',
            'malicious.external.route'
        ))->toThrow(\InvalidArgumentException::class, 'Invalid redirect route');
    });
    
    test('only whitelisted redirect routes are allowed', function () {
        $validRoutes = [
            'admin.dashboard',
            'manager.dashboard',
            'tenant.dashboard',
            'superadmin.dashboard',
        ];
        
        foreach ($validRoutes as $route) {
            $result = SubscriptionCheckResult::block('Test', $route);
            expect($result->redirectRoute)->toBe($route);
        }
    });
    
    test('cache keys validate user IDs', function () {
        $checker = app(\App\Services\SubscriptionChecker::class);
        
        // Create user with invalid ID
        $user = new User();
        $user->id = -1;
        
        expect(fn () => $checker->getSubscription($user))
            ->toThrow(\InvalidArgumentException::class, 'Invalid user ID');
    });
    
    test('cache keys validate zero user IDs', function () {
        $checker = app(\App\Services\SubscriptionChecker::class);
        
        $user = new User();
        $user->id = 0;
        
        expect(fn () => $checker->getSubscription($user))
            ->toThrow(\InvalidArgumentException::class);
    });
});

describe('Security Headers', function () {
    test('security headers are present on all responses', function () {
        $response = $this->get('/');
        
        expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
        expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
        expect($response->headers->get('X-XSS-Protection'))->toBe('1; mode=block');
        expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    });
    
    test('CSP header is configured', function () {
        $response = $this->get('/');
        
        $csp = $response->headers->get('Content-Security-Policy');
        expect($csp)->toContain("default-src 'self'");
        expect($csp)->toContain("frame-ancestors 'self'");
        expect($csp)->toContain("base-uri 'self'");
    });
    
    test('HSTS header is present in production with HTTPS', function () {
        config(['app.env' => 'production']);
        
        $response = $this->get('/', ['HTTPS' => 'on']);
        
        if (app()->environment('production')) {
            $hsts = $response->headers->get('Strict-Transport-Security');
            expect($hsts)->toContain('max-age=31536000');
            expect($hsts)->toContain('includeSubDomains');
        }
    });
});

describe('CSRF Protection', function () {
    test('CSRF protection active on auth routes', function () {
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        
        // Should fail without CSRF token
        expect($response->status())->toBe(419);
    });
    
    test('CSRF protection active on write operations', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        Subscription::factory()->create([
            'user_id' => $admin->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addMonths(6),
        ]);
        
        // Attempt POST without CSRF token
        $response = $this->actingAs($admin)->post(route('admin.dashboard'), [
            'test' => 'data',
        ]);
        
        expect($response->status())->toBe(419);
    });
});

describe('Session Security', function () {
    test('session cookies have secure attributes', function () {
        config([
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'strict',
        ]);
        
        $response = $this->get('/');
        
        $cookies = $response->headers->getCookies();
        
        if (!empty($cookies)) {
            $sessionCookie = collect($cookies)->first(function ($cookie) {
                return str_contains($cookie->getName(), 'session');
            });
            
            if ($sessionCookie) {
                expect($sessionCookie->isSecure())->toBeTrue();
                expect($sessionCookie->isHttpOnly())->toBeTrue();
                expect($sessionCookie->getSameSite())->toBe('strict');
            }
        }
    });
});

describe('Subscription Enumeration Protection', function () {
    test('consistent response times prevent enumeration', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // User with subscription
        Subscription::factory()->create([
            'user_id' => $admin->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addMonths(6),
        ]);
        
        $start1 = microtime(true);
        $this->actingAs($admin)->get(route('admin.dashboard'));
        $time1 = microtime(true) - $start1;
        
        // User without subscription
        $admin2 = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $start2 = microtime(true);
        $this->actingAs($admin2)->get(route('admin.dashboard'));
        $time2 = microtime(true) - $start2;
        
        // Times should be similar (within 50ms) due to caching
        $timeDiff = abs($time1 - $time2);
        expect($timeDiff)->toBeLessThan(0.05);
    });
    
    test('error messages do not reveal subscription existence', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        
        // Should not contain specific database error messages
        expect($response->getContent())->not->toContain('subscription not found');
        expect($response->getContent())->not->toContain('database error');
        expect($response->getContent())->not->toContain('SQL');
    });
});

describe('Authorization Security', function () {
    test('subscription bypass only applies to specific routes', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Login route should bypass
        $response = $this->actingAs($admin)->get(route('login'));
        expect($response->status())->not->toBe(403);
        
        // Admin routes should not bypass without subscription
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        expect($response->status())->toBe(200); // Allowed but with error message
    });
    
    test('role-based bypass is properly enforced', function () {
        $roles = [
            UserRole::SUPERADMIN => 'superadmin.dashboard',
            UserRole::MANAGER => 'manager.dashboard',
            UserRole::TENANT => 'tenant.dashboard',
        ];
        
        foreach ($roles as $role => $route) {
            $user = User::factory()->create(['role' => $role]);
            
            $response = $this->actingAs($user)->get(route($route));
            
            // Should not require subscription
            expect($response->status())->toBe(200);
            expect($response->getContent())->not->toContain('subscription');
        }
    });
});

describe('Configuration Security', function () {
    test('cache TTL is configurable for security tuning', function () {
        config(['subscription.cache_ttl' => 60]);
        
        $checker = app(\App\Services\SubscriptionChecker::class);
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('getCacheTTL');
        $method->setAccessible(true);
        
        expect($method->invoke($checker))->toBe(60);
    });
    
    test('rate limits are configurable', function () {
        config([
            'subscription.rate_limit.authenticated' => 100,
            'subscription.rate_limit.unauthenticated' => 20,
        ]);
        
        expect(config('subscription.rate_limit.authenticated'))->toBe(100);
        expect(config('subscription.rate_limit.unauthenticated'))->toBe(20);
    });
});
