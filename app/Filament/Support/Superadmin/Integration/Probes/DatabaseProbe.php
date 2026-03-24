<?php

namespace App\Filament\Support\Superadmin\Integration\Probes;

use App\Enums\IntegrationHealthStatus;
use App\Filament\Support\Superadmin\Integration\Contracts\IntegrationProbe;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseProbe implements IntegrationProbe
{
    public function key(): string
    {
        return 'database';
    }

    public function label(): string
    {
        return 'Database';
    }

    public function check(): array
    {
        $startedAt = hrtime(true);

        try {
            $connection = DB::connection();
            $connection->getPdo();
            $healthy = $connection->getSchemaBuilder()->hasTable('migrations');

            return [
                'status' => $healthy ? IntegrationHealthStatus::HEALTHY : IntegrationHealthStatus::FAILED,
                'response_time_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                'summary' => $healthy
                    ? 'Database connection resolved and schema is reachable.'
                    : 'Database connection resolved but the schema verification failed.',
                'details' => [
                    'connection' => $connection->getName(),
                    'driver' => $connection->getDriverName(),
                    'database' => $connection->getDatabaseName(),
                ],
            ];
        } catch (Throwable $exception) {
            return [
                'status' => IntegrationHealthStatus::FAILED,
                'response_time_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                'summary' => 'Database runtime check failed.',
                'details' => [
                    'connection' => (string) config('database.default'),
                    'error' => $exception->getMessage(),
                ],
            ];
        }
    }
}
