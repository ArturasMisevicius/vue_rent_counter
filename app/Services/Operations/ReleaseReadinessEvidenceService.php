<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Enums\IntegrationHealthStatus;
use App\Filament\Actions\Superadmin\Integration\RunIntegrationHealthChecksAction;

final class ReleaseReadinessEvidenceService
{
    public function __construct(
        private readonly RunIntegrationHealthChecksAction $runIntegrationHealthChecksAction,
        private readonly BackupRestoreReadinessService $backupRestoreReadinessService,
    ) {}

    /**
     * @return array{
     *     ready: bool,
     *     integration_health: string,
     *     backup_and_restore: string,
     *     queue_worker: string,
     *     manual_checks: string
     * }
     */
    public function gather(): array
    {
        $checks = $this->runIntegrationHealthChecksAction->handle();
        $backupReadiness = $this->backupRestoreReadinessService->assess();
        $queueConnection = (string) config('queue.default', '');
        $queueDriver = (string) config("queue.connections.{$queueConnection}.driver", '');

        $integrationHealth = $checks
            ->map(fn ($check): string => sprintf('%s=%s', $check->key, strtoupper($check->status->value)))
            ->implode(', ');

        $backupAndRestore = $backupReadiness['ready']
            ? 'READY — php artisan ops:backup-restore-readiness'
            : 'NOT READY — php artisan ops:backup-restore-readiness';

        $queueWorker = blank($queueConnection) || blank($queueDriver)
            ? 'Queue worker guidance is unavailable because the queue connection is not configured.'
            : sprintf(
                'Driver %s on connection %s. Verify with php artisan queue:work in the target environment.',
                $queueDriver,
                $queueConnection,
            );

        return [
            'ready' => $backupReadiness['ready']
                && $checks->every(fn ($check): bool => $check->status !== IntegrationHealthStatus::FAILED),
            'integration_health' => $integrationHealth,
            'backup_and_restore' => $backupAndRestore,
            'queue_worker' => $queueWorker,
            'manual_checks' => 'Review /app/integration-health and the docs/operations runbooks before release.',
        ];
    }
}
