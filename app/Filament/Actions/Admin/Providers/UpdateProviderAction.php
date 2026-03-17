<?php

namespace App\Filament\Actions\Admin\Providers;

use App\Enums\ServiceType;
use App\Http\Requests\Admin\Providers\ProviderRequest;
use App\Models\Provider;

class UpdateProviderAction
{
    public function handle(Provider $provider, array $data): Provider
    {
        $validated = $this->validate($data);

        $provider->update([
            'name' => $validated['name'],
            'service_type' => $validated['service_type'],
            'contact_info' => $this->normalizeContactInfo($validated['contact_info'] ?? []),
        ]);

        return $provider->fresh();
    }

    /**
     * @return array{
     *     name: string,
     *     service_type: ServiceType|string,
     *     contact_info?: array{phone?: string|null, email?: string|null, website?: string|null}
     * }
     */
    private function validate(array $data): array
    {
        /** @var ProviderRequest $request */
        $request = new ProviderRequest;
        $validated = $request->validatePayload($data);

        return $validated;
    }

    /**
     * @param  array{phone?: string|null, email?: string|null, website?: string|null}  $contactInfo
     * @return array<string, string>|null
     */
    private function normalizeContactInfo(array $contactInfo): ?array
    {
        $normalized = collect($contactInfo)
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(fn (mixed $value): string => (string) $value)
            ->all();

        return $normalized !== [] ? $normalized : null;
    }
}
