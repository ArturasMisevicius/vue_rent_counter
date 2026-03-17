<?php

namespace App\Filament\Actions\Admin\Tariffs;

use App\Enums\TariffType;
use App\Http\Requests\Admin\Tariffs\TariffRequest;
use App\Models\Tariff;

class UpdateTariffAction
{
    public function handle(Tariff $tariff, array $data): Tariff
    {
        $organizationId = $tariff->provider()->value('organization_id');
        $validated = $this->validate($organizationId, $data);

        $tariff->update([
            'provider_id' => $validated['provider_id'],
            'remote_id' => $validated['remote_id'],
            'name' => $validated['name'],
            'configuration' => $this->normalizeConfiguration($validated['configuration'] ?? []),
            'active_from' => $validated['active_from'],
            'active_until' => $validated['active_until'],
        ]);

        return $tariff->fresh(['provider']);
    }

    /**
     * @return array{
     *     provider_id: int,
     *     remote_id: string|null,
     *     name: string,
     *     configuration?: array{type?: TariffType|string|null, currency?: string|null, rate?: float|int|string|null},
     *     active_from: string,
     *     active_until: string|null
     * }
     */
    private function validate(?int $organizationId, array $data): array
    {
        /** @var TariffRequest $request */
        $request = new TariffRequest;
        $validated = $request
            ->forOrganization($organizationId)
            ->validatePayload($data);

        return $validated;
    }

    /**
     * @param  array{type?: TariffType|string|null, currency?: string|null, rate?: float|int|string|null}  $configuration
     * @return array<string, string|float>
     */
    private function normalizeConfiguration(array $configuration): array
    {
        /** @var array<string, string|float> $normalized */
        $normalized = collect($configuration)
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(function (mixed $value, string $key): string|float {
                if ($key === 'rate') {
                    return round((float) $value, 4);
                }

                return (string) $value;
            })
            ->all();

        return $normalized;
    }
}
