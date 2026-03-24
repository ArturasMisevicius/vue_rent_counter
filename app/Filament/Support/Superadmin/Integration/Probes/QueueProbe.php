<?php

namespace App\Filament\Support\Superadmin\Integration\Probes;

use App\Enums\IntegrationHealthStatus;
use App\Filament\Support\Superadmin\Integration\Contracts\IntegrationProbe;
use Illuminate\Support\Facades\Queue;
use Throwable;

class QueueProbe implements IntegrationProbe
{
    public function key(): string
    {
        return 'queue';
    }

    public function label(): string
    {
        return 'Queue';
    }

    public function check(): array
    {
        $startedAt = hrtime(true);
        $connection = (string) config('queue.default', '');
        $driver = (string) config("queue.connections.{$connection}.driver", '');

        try {
            if (blank($connection) || blank($driver)) {
                return [
                    'status' => IntegrationHealthStatus::FAILED,
                    'response_time_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                    'summary' => 'Queue configuration is missing.',
                    'details' => [
                        'connection' => $connection,
                        'driver' => $driver,
                    ],
                ];
            }

            $resolvedConnection = Queue::connection($connection);
            $status = in_array($driver, ['sync', 'deferred', 'null'], true)
                ? IntegrationHealthStatus::DEGRADED
                : IntegrationHealthStatus::HEALTHY;
            $summary = $status === IntegrationHealthStatus::DEGRADED
                ? "Queue connection [{$connection}] uses the {$driver} driver, so background worker dispatch is not verified."
                : "Queue connection [{$connection}] resolved successfully.";

            return [
                'status' => $status,
                'response_time_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                'summary' => $summary,
                'details' => [
                    'connection' => $connection,
                    'driver' => $driver,
                    'resolved_class' => $resolvedConnection::class,
                ],
            ];
        } catch (Throwable $exception) {
            return [
                'status' => IntegrationHealthStatus::FAILED,
                'response_time_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                'summary' => 'Queue runtime check failed.',
                'details' => [
                    'connection' => $connection,
                    'driver' => $driver,
                    'error' => $exception->getMessage(),
                ],
            ];
        }
    }
}
