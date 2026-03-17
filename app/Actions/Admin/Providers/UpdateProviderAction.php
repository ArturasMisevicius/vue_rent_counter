<?php

namespace App\Actions\Admin\Providers;

use App\Enums\ServiceType;
use App\Models\Provider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        $data['service_type'] = $data['service_type'] instanceof ServiceType
            ? $data['service_type']->value
            : $data['service_type'];

        /** @var array{
         *     name: string,
         *     service_type: ServiceType|string,
         *     contact_info?: array{phone?: string|null, email?: string|null, website?: string|null}
         * } $validated
         */
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['required', Rule::in(collect(ServiceType::cases())->map->value->all())],
            'contact_info.phone' => ['nullable', 'string', 'max:255'],
            'contact_info.email' => ['nullable', 'email', 'max:255'],
            'contact_info.website' => ['nullable', 'url', 'max:255'],
        ])->validate();

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
