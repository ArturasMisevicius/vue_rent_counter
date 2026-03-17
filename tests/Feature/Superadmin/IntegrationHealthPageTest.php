<?php

use App\Enums\IntegrationHealthStatus;
use App\Filament\Actions\Superadmin\Integration\ResetIntegrationCircuitBreakerAction;
use App\Filament\Actions\Superadmin\Integration\RunIntegrationHealthChecksAction;
use App\Models\IntegrationHealthCheck;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows integration health cards with polling only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    IntegrationHealthCheck::factory()->create([
        'key' => 'database',
        'label' => 'Database',
    ]);

    IntegrationHealthCheck::factory()->create([
        'key' => 'queue',
        'label' => 'Queue',
    ]);

    IntegrationHealthCheck::factory()->create([
        'key' => 'mail',
        'label' => 'Mail',
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.integration-health'))
        ->assertSuccessful()
        ->assertSeeText('Integration Health')
        ->assertSeeText('Database')
        ->assertSeeText('Queue')
        ->assertSeeText('Mail')
        ->assertSee('wire:poll.30s', false);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.integration-health'))
        ->assertForbidden();
});

it('runs health probes and resets failed checks', function () {
    app(RunIntegrationHealthChecksAction::class)->handle();

    $failed = IntegrationHealthCheck::query()
        ->where('key', 'queue')
        ->firstOrFail();

    $failed->update([
        'status' => IntegrationHealthStatus::FAILED,
        'summary' => 'Queue worker is paused.',
    ]);

    $reset = app(ResetIntegrationCircuitBreakerAction::class)->handle($failed->fresh());

    expect(IntegrationHealthCheck::query()->count())->toBeGreaterThanOrEqual(3)
        ->and($reset->status)->toBe(IntegrationHealthStatus::HEALTHY)
        ->and($reset->summary)->toContain('reset');
});
