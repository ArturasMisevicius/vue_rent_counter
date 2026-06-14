<?php

namespace App\Filament\Actions\Admin\ServiceConfigurations\Concerns;

use App\Enums\AssignmentScope;
use App\Enums\BillingMethod;
use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceConfigurationStatus;
use App\Filament\Support\Admin\ServiceConfigurations\ValidateServiceConfiguration;
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
        $billingMethod = BillingMethod::tryFrom((string) ($attributes['billing_method'] ?? '')) ?? BillingMethod::METER_BASED;
        $status = ServiceConfigurationStatus::tryFrom((string) ($attributes['status'] ?? '')) ?? ServiceConfigurationStatus::DRAFT;
        $validationResult = app(ValidateServiceConfiguration::class)->handle($attributes);

        if ($status === ServiceConfigurationStatus::ACTIVE && $validationResult['blocking_errors'] !== []) {
            throw ValidationException::withMessages([
                'status' => __('admin.service_configurations.messages.configuration_error_blocks_activation'),
            ]);
        }

        if ($validationResult['blocking_errors'] !== []) {
            $status = ServiceConfigurationStatus::CONFIGURATION_ERROR;
        }

        $effectiveFrom = Arr::get($attributes, 'starts_at') ?? Arr::get($attributes, 'effective_from') ?? now()->toDateString();
        $effectiveUntil = Arr::get($attributes, 'ends_at') ?? Arr::get($attributes, 'effective_until');
        $rateSchedule = $this->rateSchedulePayload($attributes, $billingMethod);

        return [
            'organization_id' => $organizationId,
            'property_id' => $attributes['property_id'],
            'utility_service_id' => $attributes['utility_service_id'],
            'service_name' => Arr::get($attributes, 'service_name'),
            'service_type' => Arr::get($attributes, 'service_type'),
            'billing_method' => $billingMethod,
            'unit' => Arr::get($attributes, 'unit'),
            'currency' => strtoupper((string) (Arr::get($attributes, 'currency') ?: 'EUR')),
            'fixed_amount' => Arr::get($attributes, 'fixed_amount'),
            'billing_frequency' => Arr::get($attributes, 'billing_frequency'),
            'assignment_scope' => Arr::get($attributes, 'assignment_scope') ?: AssignmentScope::PROPERTY->value,
            'tenant_visible' => (bool) Arr::get($attributes, 'tenant_visible', false),
            'tenant_visible_name' => Arr::get($attributes, 'tenant_visible_name'),
            'tenant_visible_description' => Arr::get($attributes, 'tenant_visible_description'),
            'show_formula_to_tenant' => (bool) Arr::get($attributes, 'show_formula_to_tenant', false),
            'show_provider_to_tenant' => (bool) Arr::get($attributes, 'show_provider_to_tenant', false),
            'show_readings_to_tenant' => (bool) Arr::get($attributes, 'show_readings_to_tenant', false),
            'internal_note' => Arr::get($attributes, 'internal_note'),
            'status' => $status,
            'starts_at' => $effectiveFrom,
            'ends_at' => $effectiveUntil,
            'meter_rules' => Arr::get($attributes, 'meter_rules'),
            'assignment_rules' => Arr::get($attributes, 'assignment_rules'),
            'validation_result' => $validationResult,
            'pricing_model' => $this->pricingModelForBillingMethod($billingMethod),
            'rate_schedule' => $rateSchedule,
            'distribution_method' => Arr::get($attributes, 'distribution_method') ?: DistributionMethod::EQUAL->value,
            'is_shared_service' => (bool) ($attributes['is_shared_service'] ?? false),
            'effective_from' => $effectiveFrom,
            'effective_until' => $effectiveUntil,
            'configuration_overrides' => Arr::get($attributes, 'configuration_overrides'),
            'tariff_id' => Arr::get($attributes, 'tariff_id'),
            'provider_id' => Arr::get($attributes, 'provider_id'),
            'area_type' => Arr::get($attributes, 'area_type'),
            'custom_formula' => Arr::get($attributes, 'custom_formula'),
            'invoice_description' => Arr::get($attributes, 'invoice_description'),
            'is_active' => $status === ServiceConfigurationStatus::ACTIVE,
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
            ? Tariff::query()->select(['id', 'provider_id'])->with('provider:id,organization_id')->find($attributes['tariff_id'])
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

        if ($tariff && ! $provider && (int) $tariff->provider?->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'tariff_id' => __('admin.service_configurations.messages.invalid_tariff'),
            ]);
        }
    }

    private function pricingModelForBillingMethod(BillingMethod $billingMethod): PricingModel
    {
        return match ($billingMethod) {
            BillingMethod::METER_BASED => PricingModel::CONSUMPTION_BASED,
            BillingMethod::FIXED_MONTHLY => PricingModel::FIXED_MONTHLY,
            BillingMethod::FORMULA_BASED => PricingModel::CUSTOM_FORMULA,
            BillingMethod::INCLUDED_FREE,
            BillingMethod::MANUAL,
            BillingMethod::ONE_TIME,
            BillingMethod::PERCENTAGE => PricingModel::FLAT,
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function rateSchedulePayload(array $attributes, BillingMethod $billingMethod): array
    {
        $rateSchedule = Arr::get($attributes, 'rate_schedule');
        $rateSchedule = is_array($rateSchedule) ? $rateSchedule : [];

        if ($billingMethod === BillingMethod::FIXED_MONTHLY) {
            $rateSchedule['unit_rate'] = Arr::get($attributes, 'fixed_amount') ?? Arr::get($rateSchedule, 'unit_rate', 0);
            $rateSchedule['base_fee'] = 0;
        }

        if ($billingMethod === BillingMethod::INCLUDED_FREE) {
            $rateSchedule['unit_rate'] = 0;
            $rateSchedule['base_fee'] = 0;
        }

        if (Arr::get($attributes, 'currency')) {
            $rateSchedule['currency'] = strtoupper((string) Arr::get($attributes, 'currency'));
        }

        return $rateSchedule;
    }
}
