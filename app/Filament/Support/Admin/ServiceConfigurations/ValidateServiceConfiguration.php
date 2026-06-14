<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\ServiceConfigurations;

use App\Enums\BillingMethod;
use App\Enums\ServiceConfigurationStatus;
use App\Models\ServiceConfiguration;
use Illuminate\Support\Arr;

final class ValidateServiceConfiguration
{
    /**
     * @param  array<string, mixed>|ServiceConfiguration  $configuration
     * @return array{
     *     status: string,
     *     blocking_errors: array<int, string>,
     *     warnings: array<int, string>,
     *     recommendations: array<int, string>
     * }
     */
    public function handle(array|ServiceConfiguration $configuration): array
    {
        $data = $configuration instanceof ServiceConfiguration
            ? $this->fromModel($configuration)
            : $configuration;

        $billingMethod = BillingMethod::tryFrom((string) Arr::get($data, 'billing_method', ''));
        $blockingErrors = [];
        $warnings = [];
        $recommendations = [];

        if (blank(Arr::get($data, 'service_name'))) {
            $blockingErrors[] = __('admin.service_configurations.validation.errors.service_name_required');
        }

        if (blank(Arr::get($data, 'service_type'))) {
            $blockingErrors[] = __('admin.service_configurations.validation.errors.service_type_required');
        }

        if (! $billingMethod instanceof BillingMethod) {
            $blockingErrors[] = __('admin.service_configurations.validation.errors.billing_method_required');
        }

        if ($billingMethod === BillingMethod::METER_BASED) {
            if (blank(Arr::get($data, 'tariff_id'))) {
                $blockingErrors[] = __('admin.service_configurations.validation.errors.meter_tariff_required');
            }

            if (blank(Arr::get($data, 'unit'))) {
                $blockingErrors[] = __('admin.service_configurations.validation.errors.meter_unit_required');
            }

            if (! $this->hasMeterRules($data)) {
                $blockingErrors[] = __('admin.service_configurations.validation.errors.meter_rules_required');
            }
        }

        if ($billingMethod === BillingMethod::FIXED_MONTHLY) {
            if (blank(Arr::get($data, 'fixed_amount'))) {
                $blockingErrors[] = __('admin.service_configurations.validation.errors.fixed_amount_required');
            }

            if (blank(Arr::get($data, 'currency'))) {
                $blockingErrors[] = __('admin.service_configurations.validation.errors.fixed_currency_required');
            }

            if (blank(Arr::get($data, 'billing_frequency'))) {
                $blockingErrors[] = __('admin.service_configurations.validation.errors.fixed_frequency_required');
            }
        }

        if ($billingMethod === BillingMethod::FORMULA_BASED && blank(Arr::get($data, 'custom_formula'))) {
            $blockingErrors[] = __('admin.service_configurations.validation.errors.formula_required');
        }

        if ((bool) Arr::get($data, 'tenant_visible', false)) {
            if (blank(Arr::get($data, 'tenant_visible_name'))) {
                $blockingErrors[] = __('admin.service_configurations.validation.errors.tenant_name_required');
            }

            if (blank(Arr::get($data, 'tenant_visible_description'))) {
                $blockingErrors[] = __('admin.service_configurations.validation.errors.tenant_description_required');
            }
        }

        $currency = strtoupper((string) Arr::get($data, 'currency', 'EUR'));

        if ($currency !== '' && $currency !== 'EUR') {
            $blockingErrors[] = __('admin.service_configurations.validation.errors.currency_unsupported', [
                'currency' => $currency,
            ]);
        }

        if (blank(Arr::get($data, 'provider_id'))) {
            $warnings[] = __('admin.service_configurations.validation.warnings.no_provider');
        }

        if (! (bool) Arr::get($data, 'tenant_visible', false)) {
            $warnings[] = __('admin.service_configurations.validation.warnings.no_tenant_visibility');
            $recommendations[] = __('admin.service_configurations.validation.recommendations.tenant_visibility');
        }

        if ($billingMethod instanceof BillingMethod && ! $billingMethod->createsAutomaticInvoiceItems()) {
            $warnings[] = __('admin.service_configurations.validation.warnings.manual_not_automatic');
        }

        if (blank(Arr::get($data, 'provider_id')) || blank(Arr::get($data, 'tariff_id'))) {
            $recommendations[] = __('admin.service_configurations.validation.recommendations.provider_snapshot');
        }

        if ($billingMethod === BillingMethod::FIXED_MONTHLY && blank(Arr::get($data, 'invoice_description'))) {
            $recommendations[] = __('admin.service_configurations.validation.recommendations.fixed_description');
        }

        return [
            'status' => $blockingErrors === []
                ? ServiceConfigurationStatus::ACTIVE->value
                : ServiceConfigurationStatus::CONFIGURATION_ERROR->value,
            'blocking_errors' => array_values(array_unique($blockingErrors)),
            'warnings' => array_values(array_unique($warnings)),
            'recommendations' => array_values(array_unique($recommendations)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fromModel(ServiceConfiguration $configuration): array
    {
        return [
            ...$configuration->getAttributes(),
            'billing_method' => $configuration->billing_method?->value,
            'service_type' => $configuration->service_type?->value,
            'status' => $configuration->status?->value,
            'billing_frequency' => $configuration->billing_frequency?->value,
            'assignment_scope' => $configuration->assignment_scope?->value,
            'meter_rules' => $configuration->meter_rules,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hasMeterRules(array $data): bool
    {
        $meterRules = Arr::get($data, 'meter_rules');

        if (! is_array($meterRules)) {
            return false;
        }

        return (bool) ($meterRules['require_readings'] ?? false)
            || filled($meterRules['minimum_readings'] ?? null);
    }
}
