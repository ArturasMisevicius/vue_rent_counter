<?php

use App\Models\User;
use App\Support\TraceReplay\TraceReplayStorageHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use TraceReplay\Models\Trace;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! Route::has('test.trace-replay.dashboard-safe')) {
        Route::middleware('web')
            ->get('/__test/trace-replay/dashboard-safe', fn () => response('dashboard-ok'))
            ->name('test.trace-replay.dashboard-safe');
    }

    if (! Route::has('test.trace-replay.storage-missing')) {
        Route::middleware('web')
            ->get('/__test/trace-replay/storage-missing', fn () => response('storage-ok'))
            ->name('test.trace-replay.storage-missing');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
});

it('uses dashboard-safe trace replay defaults', function (): void {
    expect(config('trace-replay.trace_bar.enabled'))->toBeFalse()
        ->and(config('trace-replay.sample_rate'))->toBe(0.1)
        ->and(config('trace-replay.max_payload_size'))->toBe(16384)
        ->and(config('trace-replay.track_db_queries'))->toBeFalse()
        ->and(config('trace-replay.skip_paths'))->toContain('livewire/*')
        ->and(config('trace-replay.skip_routes'))->toContain('filament.admin.pages.dashboard')
        ->and(config('trace-replay.auto_trace.jobs'))->toBeFalse()
        ->and(config('trace-replay.auto_trace.commands'))->toBeFalse()
        ->and(config('trace-replay.auto_trace.livewire'))->toBeFalse()
        ->and(config('trace-replay.auto_trace.exclude_commands'))->toContain('migrate');
});

it('skips configured dashboard routes without creating http traces', function (): void {
    config()->set('trace-replay.enabled', true);
    config()->set('trace-replay.sample_rate', 1.0);
    config()->set('trace-replay.skip_paths', []);
    config()->set('trace-replay.skip_routes', ['test.trace-replay.dashboard-safe']);

    $this->get(route('test.trace-replay.dashboard-safe'))
        ->assertOk()
        ->assertSee('dashboard-ok');

    expect(Trace::query()
        ->where('name', 'HTTP GET /__test/trace-replay/dashboard-safe')
        ->exists())->toBeFalse();
});

it('loads the filament dashboard without tracing the dashboard request', function (): void {
    $superadmin = User::factory()->superadmin()->create();

    config()->set('trace-replay.enabled', true);
    config()->set('trace-replay.sample_rate', 1.0);
    config()->set('trace-replay.skip_paths', []);
    config()->set('trace-replay.skip_routes', ['filament.admin.pages.dashboard']);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful();

    expect(Trace::query()
        ->where('name', 'HTTP GET /app')
        ->exists())->toBeFalse();
});

it('lets requests pass when trace replay tables are unavailable', function (): void {
    config()->set('trace-replay.enabled', true);
    config()->set('trace-replay.sample_rate', 1.0);
    config()->set('trace-replay.skip_paths', []);
    config()->set('trace-replay.skip_routes', []);

    app()->instance(TraceReplayStorageHealth::class, new class extends TraceReplayStorageHealth
    {
        public function isReady(): bool
        {
            return false;
        }
    });

    $this->get(route('test.trace-replay.storage-missing'))
        ->assertOk()
        ->assertSee('storage-ok');
});
