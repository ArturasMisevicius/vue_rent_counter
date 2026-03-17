<?php

namespace App\Support\Superadmin\Integration\Probes;

use App\Enums\IntegrationHealthStatus;
use App\Support\Superadmin\Integration\Contracts\IntegrationProbe;
use Illuminate\Support\Facades\Schema;

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
        $healthy = Schema::hasTable('migrations');

        return [
            'status' => $healthy ? IntegrationHealthStatus::HEALTHY : IntegrationHealthStatus::FAILED,
            'response_time_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
            'summary' => $healthy ? 'Database schema is reachable.' : 'Database schema could not be verified.',
            'details' => [
                'connection' => config('database.default'),
            ],
        ];
    }
}
