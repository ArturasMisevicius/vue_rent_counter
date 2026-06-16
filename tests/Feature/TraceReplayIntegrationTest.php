<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use TraceReplay\Facades\TraceReplay;
use TraceReplay\Models\Trace;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('trace-replay.enabled', true);
    config()->set('trace-replay.sample_rate', 1.0);
    config()->set('trace-replay.batch_persistence', true);
    config()->set('trace-replay.queue.enabled', false);
    config()->set('trace-replay.trace_bar.enabled', false);
    config()->set('trace-replay.auto_trace.commands', true);

    if (! Route::has('test.trace-replay.captured')) {
        Route::middleware('web')
            ->get('/__test/trace-replay/captured', fn () => response()->json(['ok' => true]))
            ->name('test.trace-replay.captured');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
});

it('protects the dashboard behind authentication and the superadmin gate', function (): void {
    $this->get(route('trace-replay.index'))
        ->assertRedirect(route('login'));

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('trace-replay.index'))
        ->assertForbidden();

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('trace-replay.index'))
        ->assertSuccessful();
});

it('keeps the agent api disabled until a bearer token is configured', function (): void {
    $this->getJson(route('trace-replay.api.list'))
        ->assertForbidden()
        ->assertJsonPath('status', 'error');

    config()->set('trace-replay.api.token', 'test-token');

    $this->withHeader('Authorization', 'Bearer test-token')
        ->postJson(route('trace-replay.api.mcp.rpc'), [
            'jsonrpc' => '2.0',
            'method' => 'list_traces',
            'id' => 1,
        ])
        ->assertSuccessful()
        ->assertJsonPath('jsonrpc', '2.0')
        ->assertJsonPath('id', 1);
});

it('records http traces with the authenticated organization workspace scope', function (): void {
    $workspace = createOrgWithAdmin();
    $admin = $workspace['admin'];

    $this->actingAs($admin)
        ->getJson(route('test.trace-replay.captured'))
        ->assertSuccessful()
        ->assertJson(['ok' => true]);

    $trace = Trace::query()
        ->where('name', 'HTTP GET /__test/trace-replay/captured')
        ->firstOrFail();

    expect($trace->status)->toBe('success')
        ->and($trace->workspace_id)->toBe(traceReplayWorkspaceIdForOrganization((int) $admin->organization_id))
        ->and($trace->user_id)->toBe((string) $admin->id)
        ->and($trace->steps()->where('label', 'HTTP Request')->exists())->toBeTrue();
});

it('records manual instrumentation steps and checkpoints', function (): void {
    TraceReplay::start('Billing diagnostics probe', ['feature' => 'trace-replay'], 'manual', forceSample: true);

    $result = TraceReplay::step('Calculate invoice preview', fn (): string => 'preview-ready');

    TraceReplay::checkpoint('Preview calculated', ['status' => $result]);
    TraceReplay::end('success');

    $trace = Trace::query()
        ->where('name', 'Billing diagnostics probe')
        ->firstOrFail();

    expect($result)->toBe('preview-ready')
        ->and($trace->type)->toBe('manual')
        ->and($trace->status)->toBe('success')
        ->and($trace->steps()->where('label', 'Calculate invoice preview')->exists())->toBeTrue()
        ->and($trace->steps()->where('label', 'Preview calculated')->where('type', 'checkpoint')->exists())->toBeTrue();
});

function traceReplayWorkspaceIdForOrganization(int $organizationId): string
{
    $hash = md5('tenanto-organization:'.$organizationId);

    return sprintf(
        '%s-%s-%s-%s-%s',
        substr($hash, 0, 8),
        substr($hash, 8, 4),
        substr($hash, 12, 4),
        substr($hash, 16, 4),
        substr($hash, 20, 12),
    );
}
