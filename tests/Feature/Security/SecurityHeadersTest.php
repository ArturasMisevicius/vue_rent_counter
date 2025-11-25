<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * Security Headers Test Suite
 *
 * Validates that all required security headers are present and properly
 * configured to protect against common web vulnerabilities.
 */

describe('Security Headers', function () {
    test('content security policy header is present', function () {
        $response = $this->get('/');

        $response->assertHeader('Content-Security-Policy');
    });

    test('csp allows required resources', function () {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)->toContain("script-src 'self'")
            ->and($csp)->toContain('cdn.tailwindcss.com')
            ->and($csp)->toContain("frame-ancestors 'none'")
            ->and($csp)->toContain("default-src 'self'");
    });

    test('x-frame-options prevents clickjacking', function () {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
    });

    test('x-content-type-options prevents mime sniffing', function () {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    });

    test('referrer policy is set', function () {
        $response = $this->get('/');

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    });

    test('permissions policy restricts features', function () {
        $response = $this->get('/');

        $policy = $response->headers->get('Permissions-Policy');

        expect($policy)->toContain('geolocation=()')
            ->and($policy)->toContain('microphone=()')
            ->and($policy)->toContain('camera=()');
    });

    test('server information is removed', function () {
        $response = $this->get('/');

        expect($response->headers->has('X-Powered-By'))->toBeFalse()
            ->and($response->headers->has('Server'))->toBeFalse();
    });

    test('security headers are present on authenticated routes', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        actingAs($admin);

        $response = $this->get(route('filament.admin.resources.buildings.index'));

        $response->assertHeader('Content-Security-Policy')
            ->assertHeader('X-Frame-Options')
            ->assertHeader('X-Content-Type-Options')
            ->assertHeader('Referrer-Policy');
    });

    test('security headers are present on api routes', function () {
        $response = $this->get('/api/health');

        $response->assertHeader('Content-Security-Policy')
            ->assertHeader('X-Frame-Options')
            ->assertHeader('X-Content-Type-Options');
    });
});

describe('HTTPS Enforcement', function () {
    test('secure cookies are enforced in production', function () {
        config(['app.env' => 'production']);
        config(['session.secure' => true]);

        $response = $this->get('/');

        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            expect($cookie->isSecure())->toBeTrue();
        }
    })->skip('Requires production environment');

    test('hsts header is present in production', function () {
        config(['app.env' => 'production']);

        $response = $this->get('/');

        $response->assertHeader('Strict-Transport-Security');
    })->skip('HSTS not yet configured');
});
