<?php

namespace App\Filament\Support\Superadmin\Integration\Contracts;

use App\Enums\IntegrationHealthStatus;

interface IntegrationProbe
{
    public function key(): string;

    public function label(): string;

    /**
     * @return array{
     *     status: IntegrationHealthStatus,
     *     response_time_ms: int,
     *     summary: string,
     *     details: array<string, mixed>
     * }
     */
    public function check(): array;
}
