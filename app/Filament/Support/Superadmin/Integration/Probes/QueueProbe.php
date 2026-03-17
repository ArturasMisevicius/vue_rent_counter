<?php

namespace App\Filament\Support\Superadmin\Integration\Probes;

use App\Enums\IntegrationHealthStatus;
use App\Filament\Support\Superadmin\Integration\Contracts\IntegrationProbe;

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
        $configured = filled($connection) && config("queue.connections.{$connection}") !== null;

        return [
            'status' => $configured ? IntegrationHealthStatus::HEALTHY : IntegrationHealthStatus::FAILED,
            'response_time_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
            'summary' => $configured ? 'Queue configuration is available.' : 'Queue configuration is missing.',
            'details' => [
                'connection' => $connection,
            ],
        ];
    }
}
