<?php

use App\Enums\IntegrationHealthStatus;
use App\Filament\Actions\Superadmin\Integration\RunIntegrationHealthChecksAction;
use App\Models\IntegrationHealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('marks sync queue and array mail as degraded runtime checks instead of healthy', function () {
    app(RunIntegrationHealthChecksAction::class)->handle();

    $database = IntegrationHealthCheck::query()->where('key', 'database')->firstOrFail();
    $queue = IntegrationHealthCheck::query()->where('key', 'queue')->firstOrFail();
    $mail = IntegrationHealthCheck::query()->where('key', 'mail')->firstOrFail();

    expect($database->status)->toBe(IntegrationHealthStatus::HEALTHY)
        ->and($queue->status)->toBe(IntegrationHealthStatus::DEGRADED)
        ->and($queue->summary)->toContain('sync')
        ->and($mail->status)->toBe(IntegrationHealthStatus::DEGRADED)
        ->and($mail->summary)->toContain('array');
});

it('captures runtime dependency failures for database, queue, and mail probes', function () {
    DB::shouldReceive('connection')
        ->once()
        ->andThrow(new RuntimeException('database unavailable'));

    Queue::shouldReceive('connection')
        ->once()
        ->andThrow(new RuntimeException('queue unavailable'));

    Mail::shouldReceive('mailer')
        ->once()
        ->andThrow(new RuntimeException('mail unavailable'));

    app(RunIntegrationHealthChecksAction::class)->handle();

    $database = IntegrationHealthCheck::query()->where('key', 'database')->firstOrFail();
    $queue = IntegrationHealthCheck::query()->where('key', 'queue')->firstOrFail();
    $mail = IntegrationHealthCheck::query()->where('key', 'mail')->firstOrFail();

    expect($database->status)->toBe(IntegrationHealthStatus::FAILED)
        ->and(data_get($database->details, 'error'))->toContain('database unavailable')
        ->and($queue->status)->toBe(IntegrationHealthStatus::FAILED)
        ->and(data_get($queue->details, 'error'))->toContain('queue unavailable')
        ->and($mail->status)->toBe(IntegrationHealthStatus::FAILED)
        ->and(data_get($mail->details, 'error'))->toContain('mail unavailable');
});
