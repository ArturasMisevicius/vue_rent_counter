<?php

namespace App\Filament\Actions\Superadmin\Integration;

use App\Filament\Support\Superadmin\Integration\IntegrationProbeRegistry;
use App\Models\IntegrationHealthCheck;
use Illuminate\Support\Collection;

class RunIntegrationHealthChecksAction
{
    public function __construct(
        private readonly IntegrationProbeRegistry $integrationProbeRegistry,
    ) {}

    /**
     * @return Collection<int, IntegrationHealthCheck>
     */
    public function handle(?string $only = null): Collection
    {
        $probes = $only === null
            ? collect($this->integrationProbeRegistry->all())
            : collect([$this->integrationProbeRegistry->for($only)]);

        return $probes->map(function ($probe): IntegrationHealthCheck {
            $result = $probe->check();

            return IntegrationHealthCheck::query()->updateOrCreate(
                ['key' => $probe->key()],
                [
                    'label' => $probe->label(),
                    'status' => $result['status'],
                    'checked_at' => now(),
                    'response_time_ms' => $result['response_time_ms'],
                    'summary' => $result['summary'],
                    'details' => $result['details'],
                ],
            );
        });
    }
}
