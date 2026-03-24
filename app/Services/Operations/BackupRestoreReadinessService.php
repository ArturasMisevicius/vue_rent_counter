<?php

declare(strict_types=1);

namespace App\Services\Operations;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Throwable;

final class BackupRestoreReadinessService
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * @return array{
     *     ready: bool,
     *     checks: list<array{
     *         label: string,
     *         status: string,
     *         summary: string
     *     }>
     * }
     */
    public function assess(): array
    {
        $checks = [
            $this->databaseCheck(),
            $this->directoryCheck('Backup directory', storage_path('app/operations/backups')),
            $this->directoryCheck('Restore staging directory', storage_path('app/operations/restore')),
        ];

        return [
            'ready' => collect($checks)->every(fn (array $check): bool => $check['status'] === 'ready'),
            'checks' => $checks,
        ];
    }

    /**
     * @return array{label: string, status: string, summary: string}
     */
    private function databaseCheck(): array
    {
        try {
            $connection = DB::connection();
            $connection->getPdo();

            return [
                'label' => 'Database connection',
                'status' => 'ready',
                'summary' => sprintf(
                    '%s (%s) is reachable.',
                    $connection->getName(),
                    (string) ($connection->getDatabaseName() ?: 'unknown database'),
                ),
            ];
        } catch (Throwable $exception) {
            return [
                'label' => 'Database connection',
                'status' => 'not_ready',
                'summary' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{label: string, status: string, summary: string}
     */
    private function directoryCheck(string $label, string $path): array
    {
        try {
            if (! $this->files->isDirectory($path)) {
                $this->files->ensureDirectoryExists($path);
            }

            return [
                'label' => $label,
                'status' => is_writable($path) ? 'ready' : 'not_ready',
                'summary' => $path,
            ];
        } catch (Throwable $exception) {
            return [
                'label' => $label,
                'status' => 'not_ready',
                'summary' => $exception->getMessage(),
            ];
        }
    }
}
