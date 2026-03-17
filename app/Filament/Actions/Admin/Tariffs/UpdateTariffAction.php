<?php

namespace App\Filament\Actions\Admin\Tariffs;

use App\Enums\TariffType;
use App\Models\Tariff;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        $data['configuration']['type'] = ($data['configuration']['type'] ?? null) instanceof TariffType
            ? $data['configuration']['type']->value
            : ($data['configuration']['type'] ?? null);

        /** @var array{
         *     provider_id: int,
         *     remote_id: string|null,
         *     name: string,
         *     configuration?: array{type?: TariffType|string|null, currency?: string|null, rate?: float|int|string|null},
         *     active_from: string,
         *     active_until: string|null
         * } $validated
         */
        $validated = Validator::make($data, [
            'provider_id' => [
                'required',
                'integer',
                Rule::exists('providers', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'remote_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'configuration.type' => ['required', Rule::enum(TariffType::class)],
            'configuration.currency' => ['required', 'string', 'max:10'],
            'configuration.rate' => [
                Rule::requiredIf(fn () => ($data['configuration']['type'] ?? null) === TariffType::FLAT->value),
                'nullable',
                'numeric',
                'min:0',
            ],
            'active_from' => ['required', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
        ])->validate();

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
