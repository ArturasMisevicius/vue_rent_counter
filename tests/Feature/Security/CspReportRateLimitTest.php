<?php

declare(strict_types=1);

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Events\SecurityViolationDetected;
use App\Models\SecurityViolation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('rate limits repeated csp reports from the same ip', function (): void {
    Event::fake([
        SecurityViolationDetected::class,
    ]);

    $server = [
        'CONTENT_TYPE' => 'application/csp-report',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_USER_AGENT' => 'Tenanto CSP Throttle Test',
        'REMOTE_ADDR' => '198.51.100.10',
    ];

    $payload = json_encode([
        'csp-report' => [
            'document-uri' => 'https://tenanto.test/dashboard',
            'violated-directive' => 'script-src-elem',
            'effective-directive' => 'script-src-elem',
            'blocked-uri' => 'https://cdn.example.test/blocked.js',
            'original-policy' => "default-src 'self'; script-src 'self'",
            'disposition' => 'enforce',
        ],
    ], JSON_THROW_ON_ERROR);

    foreach (range(1, 10) as $attempt) {
        $this->call('POST', route('security.csp.report'), [], [], [], $server, $payload)
            ->assertAccepted();
    }

    $this->call('POST', route('security.csp.report'), [], [], [], $server, $payload)
        ->assertTooManyRequests();
});

it('only prunes old csp telemetry records', function (): void {
    $oldTaggedCspViolation = SecurityViolation::factory()->create([
        'type' => SecurityViolationType::DATA_ACCESS,
        'severity' => SecurityViolationSeverity::HIGH,
        'metadata' => ['source' => 'csp-report'],
        'occurred_at' => now()->subDays(15),
    ]);

    $recentTaggedCspViolation = SecurityViolation::factory()->create([
        'type' => SecurityViolationType::DATA_ACCESS,
        'severity' => SecurityViolationSeverity::HIGH,
        'metadata' => ['source' => 'csp-report'],
        'occurred_at' => now()->subDays(13),
    ]);

    $oldDifferentSourceViolation = SecurityViolation::factory()->create([
        'type' => SecurityViolationType::DATA_ACCESS,
        'severity' => SecurityViolationSeverity::HIGH,
        'metadata' => ['source' => 'manual-review'],
        'occurred_at' => now()->subDays(20),
    ]);

    $oldDifferentTypeViolation = SecurityViolation::factory()->create([
        'type' => SecurityViolationType::RATE_LIMIT,
        'severity' => SecurityViolationSeverity::HIGH,
        'metadata' => ['source' => 'csp-report'],
        'occurred_at' => now()->subDays(20),
    ]);

    $prunableIds = (new SecurityViolation)->prunable()
        ->pluck('id')
        ->all();

    expect($prunableIds)
        ->toContain($oldTaggedCspViolation->id)
        ->not->toContain($recentTaggedCspViolation->id)
        ->not->toContain($oldDifferentSourceViolation->id)
        ->not->toContain($oldDifferentTypeViolation->id);
});
