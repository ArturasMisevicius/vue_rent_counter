<?php

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Events\SecurityViolationDetected;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Services\Security\SecurityMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('records a policy registration violation when registration errors are present', function () {
    Event::fake([SecurityViolationDetected::class]);

    $service = app(SecurityMonitoringService::class);

    $service->recordPolicyRegistration(
        [
            'registered' => 2,
            'skipped' => 1,
            'errors' => ['Policy registration failed'],
        ],
        [
            'registered' => 1,
            'skipped' => 0,
            'errors' => ['Gate registration failed'],
        ],
    );

    $violation = SecurityViolation::query()->sole();

    expect($violation->type)->toBe(SecurityViolationType::AUTHORIZATION)
        ->and($violation->severity)->toBe(SecurityViolationSeverity::HIGH)
        ->and($violation->summary)->toBe('Policy registration failures detected')
        ->and($violation->metadata)->toMatchArray([
            'policy_errors' => 1,
            'gate_errors' => 1,
            'total_errors' => 2,
        ]);

    Event::assertDispatched(SecurityViolationDetected::class);
});

it('returns security metrics grouped by violation type', function () {
    SecurityViolation::factory()->create([
        'type' => SecurityViolationType::AUTHORIZATION,
        'severity' => SecurityViolationSeverity::HIGH,
    ]);

    SecurityViolation::factory()->count(2)->create([
        'type' => SecurityViolationType::CSP,
        'severity' => SecurityViolationSeverity::MEDIUM,
    ]);

    $metrics = app(SecurityMonitoringService::class)->getSecurityMetrics();

    expect($metrics['violations'])->toMatchArray([
        SecurityViolationType::AUTHORIZATION->value => 1,
        SecurityViolationType::CSP->value => 2,
    ])->and($metrics['timestamp'])->toBeString();
});

it('returns zeroed security metrics for all supported violation types when no violations exist', function () {
    $metrics = app(SecurityMonitoringService::class)->getSecurityMetrics();

    $expectedMetricKeys = collect(SecurityViolationType::cases())
        ->map(fn (SecurityViolationType $type): string => $type->value)
        ->values()
        ->all();

    expect(array_keys($metrics['violations']))
        ->toBe($expectedMetricKeys)
        ->and(array_sum($metrics['violations']))->toBe(0)
        ->and($metrics['timestamp'])->toBeString();
});

it('logs critical alerts once and refreshes suppression ttl on repeated checks', function () {
    $violation = SecurityViolation::factory()->create([
        'organization_id' => null,
        'type' => SecurityViolationType::AUTHORIZATION,
        'severity' => SecurityViolationSeverity::CRITICAL,
        'resolved_at' => null,
        'occurred_at' => now(),
    ]);

    Cache::shouldReceive('add')
        ->once()
        ->with('security_monitor:critical:'.$violation->id, true, 3600)
        ->andReturn(true);

    Cache::shouldReceive('touch')->never();

    Log::shouldReceive('critical')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Critical security violation detected'
            && $context['violation_id'] === $violation->id);

    app(SecurityMonitoringService::class)->monitorViolations();

    Cache::shouldReceive('add')
        ->once()
        ->with('security_monitor:critical:'.$violation->id, true, 3600)
        ->andReturn(false);

    Cache::shouldReceive('touch')
        ->once()
        ->with('security_monitor:critical:'.$violation->id, 3600)
        ->andReturn(true);

    Cache::shouldReceive('put')->never();

    Log::shouldReceive('critical')->never();

    app(SecurityMonitoringService::class)->monitorViolations();
});

it('falls back to put when touch cannot refresh critical alert suppression', function () {
    $violation = SecurityViolation::factory()->create([
        'organization_id' => null,
        'type' => SecurityViolationType::AUTHORIZATION,
        'severity' => SecurityViolationSeverity::CRITICAL,
        'resolved_at' => null,
        'occurred_at' => now(),
    ]);

    Cache::shouldReceive('add')
        ->once()
        ->with('security_monitor:critical:'.$violation->id, true, 3600)
        ->andReturn(false);

    Cache::shouldReceive('touch')
        ->once()
        ->with('security_monitor:critical:'.$violation->id, 3600)
        ->andReturn(false);

    Cache::shouldReceive('put')
        ->once()
        ->with('security_monitor:critical:'.$violation->id, true, 3600);

    Log::shouldReceive('critical')->never();

    app(SecurityMonitoringService::class)->monitorViolations();
});

it('logs organization rate alerts once and refreshes suppression ttl on repeated bursts', function () {
    $organization = Organization::factory()->create();

    SecurityViolation::factory()->count(20)->create([
        'organization_id' => $organization->id,
        'user_id' => null,
        'type' => SecurityViolationType::AUTHORIZATION,
        'severity' => SecurityViolationSeverity::HIGH,
        'resolved_at' => null,
        'occurred_at' => now(),
    ]);

    Cache::shouldReceive('add')
        ->once()
        ->with('security_monitor:rate:'.$organization->id, true, 600)
        ->andReturn(true);

    Cache::shouldReceive('touch')->never();

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'High security violation rate detected'
            && $context['organization_id'] === $organization->id
            && $context['violation_count'] === 20);

    app(SecurityMonitoringService::class)->monitorViolations();

    Cache::shouldReceive('add')
        ->once()
        ->with('security_monitor:rate:'.$organization->id, true, 600)
        ->andReturn(false);

    Cache::shouldReceive('touch')
        ->once()
        ->with('security_monitor:rate:'.$organization->id, 600)
        ->andReturn(true);

    Cache::shouldReceive('put')->never();

    Log::shouldReceive('warning')->never();

    app(SecurityMonitoringService::class)->monitorViolations();
});

it('falls back to put when touch cannot refresh rate alert suppression', function () {
    $organization = Organization::factory()->create();

    SecurityViolation::factory()->count(20)->create([
        'organization_id' => $organization->id,
        'user_id' => null,
        'type' => SecurityViolationType::AUTHORIZATION,
        'severity' => SecurityViolationSeverity::HIGH,
        'resolved_at' => null,
        'occurred_at' => now(),
    ]);

    Cache::shouldReceive('add')
        ->once()
        ->with('security_monitor:rate:'.$organization->id, true, 600)
        ->andReturn(false);

    Cache::shouldReceive('touch')
        ->once()
        ->with('security_monitor:rate:'.$organization->id, 600)
        ->andReturn(false);

    Cache::shouldReceive('put')
        ->once()
        ->with('security_monitor:rate:'.$organization->id, true, 600);

    Log::shouldReceive('warning')->never();

    app(SecurityMonitoringService::class)->monitorViolations();
});

it('does not trigger rate alerts when organization violations stay below the threshold', function () {
    $organization = Organization::factory()->create();

    SecurityViolation::factory()->count(19)->create([
        'organization_id' => $organization->id,
        'user_id' => null,
        'type' => SecurityViolationType::AUTHORIZATION,
        'severity' => SecurityViolationSeverity::HIGH,
        'resolved_at' => null,
        'occurred_at' => now(),
    ]);

    Cache::shouldReceive('add')->never();
    Cache::shouldReceive('touch')->never();
    Cache::shouldReceive('put')->never();

    Log::shouldReceive('warning')->never();

    app(SecurityMonitoringService::class)->monitorViolations();
});

it('triggers rate alerts only for organizations that reach the threshold', function () {
    $atThresholdOrganization = Organization::factory()->create();
    $belowThresholdOrganization = Organization::factory()->create();

    SecurityViolation::factory()->count(20)->create([
        'organization_id' => $atThresholdOrganization->id,
        'user_id' => null,
        'type' => SecurityViolationType::AUTHORIZATION,
        'severity' => SecurityViolationSeverity::HIGH,
        'resolved_at' => null,
        'occurred_at' => now(),
    ]);

    SecurityViolation::factory()->count(19)->create([
        'organization_id' => $belowThresholdOrganization->id,
        'user_id' => null,
        'type' => SecurityViolationType::AUTHORIZATION,
        'severity' => SecurityViolationSeverity::HIGH,
        'resolved_at' => null,
        'occurred_at' => now(),
    ]);

    Cache::shouldReceive('add')
        ->once()
        ->with('security_monitor:rate:'.$atThresholdOrganization->id, true, 600)
        ->andReturn(true);

    Cache::shouldReceive('touch')->never();
    Cache::shouldReceive('put')->never();

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'High security violation rate detected'
            && $context['organization_id'] === $atThresholdOrganization->id
            && $context['violation_count'] === 20);

    app(SecurityMonitoringService::class)->monitorViolations();
});
