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
        return __('superadmin.integration_health.probes.database.label');
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
                    ? __('superadmin.integration_health.probes.database.summary_healthy')
                    : __('superadmin.integration_health.probes.database.summary_schema_failed'),
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
                'summary' => __('superadmin.integration_health.probes.database.summary_failed'),
                'details' => [
                    'connection' => (string) config('database.default'),
                    'error' => $exception->getMessage(),
                ],
            ];
        }
    }
}
