<?php

use App\Enums\IntegrationHealthStatus;
use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Filament\Pages\IntegrationHealth;
use App\Models\IntegrationHealthCheck;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the integration health operations page only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    IntegrationHealthCheck::factory()->create([
        'key' => 'database',
        'label' => __('superadmin.integration_health.probes.database.label'),
        'status' => IntegrationHealthStatus::HEALTHY,
        'summary' => __('superadmin.integration_health.probes.database.summary_healthy'),
        'response_time_ms' => 23,
        'checked_at' => now()->subMinutes(5),
    ]);

    IntegrationHealthCheck::factory()->create([
        'key' => 'queue',
        'label' => __('superadmin.integration_health.probes.queue.label'),
        'status' => IntegrationHealthStatus::FAILED,
        'summary' => 'Queue worker is paused.',
        'response_time_ms' => 0,
        'details' => [
            'circuit_breaker_tripped' => true,
        ],
        'checked_at' => now()->subMinutes(2),
    ]);

    IntegrationHealthCheck::factory()->create([
        'key' => 'mail',
        'label' => __('superadmin.integration_health.probes.mail.label'),
        'status' => IntegrationHealthStatus::HEALTHY,
        'summary' => __('superadmin.integration_health.probes.mail.summary_healthy', ['mailer' => 'smtp']),
        'response_time_ms' => 18,
        'checked_at' => now()->subMinutes(4),
    ]);

    SecurityViolation::factory()->create([
        'organization_id' => $organization->id,
        'type' => SecurityViolationType::DATA_ACCESS,
        'severity' => SecurityViolationSeverity::HIGH,
        'summary' => 'CSP report blocked inline script',
        'ip_address' => '203.0.113.20',
        'metadata' => [
            'source' => 'csp-report',
            'url' => 'https://app.example.test/dashboard',
        ],
        'occurred_at' => now()->subHour(),
    ]);

    SecurityViolation::factory()->create([
        'organization_id' => null,
        'type' => SecurityViolationType::RATE_LIMIT,
        'severity' => SecurityViolationSeverity::MEDIUM,
        'summary' => 'Repeated login bursts',
        'ip_address' => '203.0.113.21',
        'metadata' => [],
        'occurred_at' => now()->subMinutes(30),
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.integration-health'))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.integration_health.title'))
        ->assertSeeText(__('superadmin.integration_health.description'))
        ->assertSeeText(__('superadmin.integration_health.checks.columns.integration'))
        ->assertSeeText(__('superadmin.integration_health.checks.columns.status'))
        ->assertSeeText(__('superadmin.integration_health.checks.columns.summary'))
        ->assertSeeText(__('superadmin.integration_health.checks.columns.checked'))
        ->assertSeeText(__('superadmin.integration_health.checks.columns.actions'))
        ->assertSeeText(__('superadmin.integration_health.probes.database.label'))
        ->assertSeeText(__('superadmin.integration_health.probes.queue.label'))
        ->assertSeeText(__('superadmin.integration_health.probes.mail.label'))
        ->assertSeeText(__('superadmin.integration_health.probes.database.summary_healthy'))
        ->assertSeeText('Queue worker is paused.')
        ->assertSeeText(__('superadmin.integration_health.response_time', ['value' => 23]))
        ->assertSeeText(__('superadmin.integration_health.checks.actions.check_now'))
        ->assertSeeText(__('superadmin.integration_health.checks.actions.reset_circuit_breaker'))
        ->assertSeeText(__('superadmin.integration_health.violations.heading'))
        ->assertSeeText(__('superadmin.integration_health.violations.description'))
        ->assertSeeText('CSP report blocked inline script')
        ->assertSeeText('csp-report')
        ->assertSeeText('203.0.113.20')
        ->assertSeeText('Northwind Towers')
        ->assertSeeText(__('superadmin.integration_health.placeholders.platform'))
        ->assertSee('wire:poll.30s', false);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.integration-health'))
        ->assertForbidden();
});

it('runs checks and resets circuit breakers from the page actions', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $databaseCheck = IntegrationHealthCheck::factory()->create([
        'key' => 'database',
        'label' => 'Database',
        'status' => IntegrationHealthStatus::UNKNOWN,
        'summary' => '',
        'checked_at' => null,
        'response_time_ms' => null,
        'details' => [],
    ]);

    $failedCheck = IntegrationHealthCheck::factory()->create([
        'key' => 'queue',
        'label' => 'Queue',
        'status' => IntegrationHealthStatus::FAILED,
        'summary' => 'Circuit breaker open.',
        'details' => [
            'circuit_breaker_tripped' => true,
        ],
        'checked_at' => now()->subMinutes(10),
        'response_time_ms' => 0,
    ]);

    Livewire::test(IntegrationHealth::class)
        ->call('checkNow', $databaseCheck->id)
        ->assertNotified();

    expect($databaseCheck->fresh()->status)->not->toBe(IntegrationHealthStatus::UNKNOWN)
        ->and($databaseCheck->fresh()->checked_at)->not->toBeNull()
        ->and($databaseCheck->fresh()->label)->toBe(__('superadmin.integration_health.probes.database.label'))
        ->and($databaseCheck->fresh()->summary)->toBe(__('superadmin.integration_health.probes.database.summary_healthy'));

    Livewire::test(IntegrationHealth::class)
        ->call('resetCircuitBreaker', $failedCheck->id)
        ->assertNotified();

    expect($failedCheck->fresh()->status)->toBe(IntegrationHealthStatus::HEALTHY)
        ->and(data_get($failedCheck->fresh()->details, 'reset_manually'))->toBeTrue();
});
