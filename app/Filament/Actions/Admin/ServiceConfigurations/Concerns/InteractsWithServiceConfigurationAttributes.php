<?php

namespace App\Filament\Actions\Admin\ServiceConfigurations\Concerns;

use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\UtilityService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait InteractsWithServiceConfigurationAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function serviceConfigurationPayload(array $attributes, int $organizationId): array
    {
        return [
            'organization_id' => $organizationId,
            'property_id' => $attributes['property_id'],
            'utility_service_id' => $attributes['utility_service_id'],
            'pricing_model' => $attributes['pricing_model'],
            'rate_schedule' => Arr::get($attributes, 'rate_schedule'),
            'distribution_method' => $attributes['distribution_method'],
            'is_shared_service' => (bool) ($attributes['is_shared_service'] ?? false),
            'effective_from' => $attributes['effective_from'],
            'effective_until' => Arr::get($attributes, 'effective_until'),
            'configuration_overrides' => Arr::get($attributes, 'configuration_overrides'),
            'tariff_id' => Arr::get($attributes, 'tariff_id'),
            'provider_id' => Arr::get($attributes, 'provider_id'),
            'area_type' => Arr::get($attributes, 'area_type'),
            'custom_formula' => Arr::get($attributes, 'custom_formula'),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function guardReferences(Organization $organization, array $attributes): void
    {
        $property = Property::query()->select(['id', 'organization_id'])->find($attributes['property_id']);
        $utilityService = UtilityService::query()->select(['id', 'organization_id'])->find($attributes['utility_service_id']);
        $provider = Arr::get($attributes, 'provider_id')
            ? Provider::query()->select(['id', 'organization_id'])->find($attributes['provider_id'])
            : null;
        $tariff = Arr::get($attributes, 'tariff_id')
            ? Tariff::query()->select(['id', 'provider_id'])->find($attributes['tariff_id'])
            : null;

        if (! $property || $property->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'property_id' => __('admin.service_configurations.messages.invalid_property'),
            ]);
        }

        if (! $utilityService || ($utilityService->organization_id !== null && $utilityService->organization_id !== $organization->id)) {
            throw ValidationException::withMessages([
                'utility_service_id' => __('admin.service_configurations.messages.invalid_utility_service'),
            ]);
        }

        if ($provider && $provider->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'provider_id' => __('admin.service_configurations.messages.invalid_provider'),
            ]);
        }

        if ($tariff && $provider && $tariff->provider_id !== $provider->id) {
            throw ValidationException::withMessages([
                'tariff_id' => __('admin.service_configurations.messages.invalid_tariff'),
            ]);
        }
    }
}
