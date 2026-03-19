<?php

declare(strict_types=1);

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Events\SecurityViolationDetected;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! Route::has('test.security.secure-page')) {
        Route::middleware(['web', 'auth'])
            ->get('/__test/security/secure-page', fn () => 'secure')
            ->name('test.security.secure-page');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
});

it('adds security headers with a csp nonce to authenticated page responses', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $response = $this->actingAs($admin)->get(route('test.security.secure-page'));

    $response->assertSuccessful();

    $cspHeader = (string) $response->headers->get('Content-Security-Policy');

    expect($cspHeader)
        ->not->toBe('')
        ->and($cspHeader)->toContain("default-src 'self'")
        ->and($cspHeader)->toContain('report-uri')
        ->and($cspHeader)->toMatch("/'nonce-[^']+'/");

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('rate limits repeated login failures with a 429 response', function (): void {
    $user = User::factory()->create([
        'email' => 'security@example.test',
    ]);

    foreach (range(1, 5) as $attempt) {
        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'));
    }

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertTooManyRequests();
});

it('accepts valid csp violation reports and records a security violation', function (): void {
    Event::fake([
        SecurityViolationDetected::class,
    ]);

    $response = $this->call(
        'POST',
        route('security.csp.report'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/csp-report',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_USER_AGENT' => 'Tenanto Security Test',
            'REMOTE_ADDR' => '203.0.113.10',
        ],
        json_encode([
            'csp-report' => [
                'document-uri' => 'https://tenanto.test/app',
                'violated-directive' => 'script-src-elem',
                'effective-directive' => 'script-src-elem',
                'blocked-uri' => 'https://malicious.example/script.js',
                'original-policy' => "default-src 'self'; script-src 'self'",
                'disposition' => 'enforce',
                'referrer' => 'https://tenanto.test/login',
            ],
        ], JSON_THROW_ON_ERROR),
    );

    $response->assertAccepted();

    $violation = SecurityViolation::query()->first();

    expect($violation)->not->toBeNull()
        ->and($violation?->type)->toBe(SecurityViolationType::DATA_ACCESS)
        ->and($violation?->severity)->toBe(SecurityViolationSeverity::HIGH)
        ->and($violation?->ip_address)->toBe('203.0.113.10')
        ->and($violation?->metadata)->toMatchArray([
            'url' => 'https://tenanto.test/app',
            'user_agent' => 'Tenanto Security Test',
            'source' => 'csp-report',
            'blocked_uri' => 'https://malicious.example/script.js',
            'violated_directive' => 'script-src-elem',
        ]);

    Event::assertDispatched(SecurityViolationDetected::class, function (SecurityViolationDetected $event) use ($violation): bool {
        return $event->securityViolation->is($violation);
    });
});

it('shows recorded security violations in the superadmin security resource and integration page', function (): void {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);

    $violation = SecurityViolation::factory()->create([
        'organization_id' => $organization->id,
        'type' => SecurityViolationType::RATE_LIMIT,
        'severity' => SecurityViolationSeverity::HIGH,
        'summary' => 'Excessive failed login attempts detected.',
        'metadata' => [
            'url' => 'https://tenanto.test/login',
            'user_agent' => 'Threat model probe',
        ],
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.security-violations.index'))
        ->assertSuccessful()
        ->assertSeeText($violation->summary)
        ->assertSeeText('Northwind Estates');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.integration-health'))
        ->assertSuccessful()
        ->assertSeeText('Recent Security Violations')
        ->assertSeeText($violation->summary)
        ->assertSeeText('Northwind Estates');
});
