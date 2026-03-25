<?php

namespace App\Filament\Actions\Superadmin\Integration;

use App\Enums\IntegrationHealthStatus;
use App\Models\IntegrationHealthCheck;

class ResetIntegrationCircuitBreakerAction
{
    public function handle(IntegrationHealthCheck $integrationHealthCheck): IntegrationHealthCheck
    {
        $details = $integrationHealthCheck->details ?? [];

        $integrationHealthCheck->update([
            'status' => IntegrationHealthStatus::HEALTHY,
            'checked_at' => now(),
            'response_time_ms' => 0,
            'summary' => __('superadmin.integration_health.messages.circuit_breaker_reset', ['label' => $integrationHealthCheck->label]),
            'details' => [
                ...$details,
                'reset_manually' => true,
            ],
        ]);

        return $integrationHealthCheck->fresh();
    }
}
