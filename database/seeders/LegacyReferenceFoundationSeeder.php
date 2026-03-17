<?php

namespace Database\Seeders;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Faq;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\Translation;
use App\Models\UtilityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class LegacyReferenceFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $currencies = $this->seedCurrencies();

        $this->seedExchangeRates($currencies);
        $this->seedFaqs();
        $this->seedTranslations();

        $providers = $this->seedProviders();
        $tariffs = $this->seedTariffs($providers);
        $utilityServices = $this->seedUtilityServices();

        $this->seedServiceConfigurations($utilityServices, $providers, $tariffs);
    }

    /**
     * @return Collection<string, Currency>
     */
    private function seedCurrencies(): Collection
    {
        $currencies = collect([
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => 'EUR', 'is_default' => true],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => 'USD', 'is_default' => false],
            ['code' => 'GBP', 'name' => 'British Pound Sterling', 'symbol' => 'GBP', 'is_default' => false],
        ])->mapWithKeys(function (array $currency): array {
            $record = Currency::query()->updateOrCreate(
                ['code' => $currency['code']],
                [
                    'name' => $currency['name'],
                    'symbol' => $currency['symbol'],
                    'decimal_places' => 2,
                    'is_active' => true,
                    'is_default' => $currency['is_default'],
                ],
            );

            return [$currency['code'] => $record];
        });

        return $currencies;
    }

    /**
     * @param  Collection<string, Currency>  $currencies
     */
    private function seedExchangeRates(Collection $currencies): void
    {
        $pairs = [
            ['from' => 'EUR', 'to' => 'USD', 'rate' => 1.09],
            ['from' => 'USD', 'to' => 'EUR', 'rate' => 0.92],
            ['from' => 'EUR', 'to' => 'GBP', 'rate' => 0.85],
            ['from' => 'GBP', 'to' => 'EUR', 'rate' => 1.17],
        ];

        foreach ($pairs as $pair) {
            ExchangeRate::query()->updateOrCreate(
                [
                    'from_currency_id' => $currencies[$pair['from']]->id,
                    'to_currency_id' => $currencies[$pair['to']]->id,
                    'effective_date' => now()->toDateString(),
                ],
                [
                    'rate' => $pair['rate'],
                    'source' => 'legacy_reference_foundation',
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedFaqs(): void
    {
        collect([
            [
                'question' => 'How are utility rates configured?',
                'answer' => 'Providers, tariffs, and service configurations define the rate schedule used for billing.',
                'category' => 'Billing',
                'display_order' => 1,
            ],
            [
                'question' => 'Can administrators maintain provider reference data?',
                'answer' => 'Yes. Legacy provider and tariff structures are now available for current admin workflows.',
                'category' => 'Admin',
                'display_order' => 2,
            ],
            [
                'question' => 'Do organizations share the same utility templates?',
                'answer' => 'Global utility service templates can be reused while organization-specific service configurations stay isolated.',
                'category' => 'Utilities',
                'display_order' => 3,
            ],
        ])->each(function (array $faq): void {
            Faq::query()->updateOrCreate(
                ['question' => $faq['question']],
                $faq + [
                    'is_published' => true,
                ],
            );
        });
    }

    private function seedTranslations(): void
    {
        Translation::query()->updateOrCreate(
            [
                'group' => 'legacy-reference',
                'key' => 'foundation.ready',
            ],
            [
                'values' => [
                    'en' => 'Legacy reference foundation is ready.',
                    'lt' => 'Istorinis nuorodu pagrindas paruostas.',
                    'ru' => 'Osnova spravochnykh dannykh iz naslediya podgotovlena.',
                ],
            ],
        );
    }

    /**
     * @return Collection<string, Provider>
     */
    private function seedProviders(): Collection
    {
        return collect([
            [
                'name' => 'Ignitis',
                'service_type' => ServiceType::ELECTRICITY,
                'contact_info' => [
                    'phone' => '+370 700 55 055',
                    'email' => 'info@ignitis.lt',
                    'website' => 'https://www.ignitis.lt',
                ],
            ],
            [
                'name' => 'Vilniaus Vandenys',
                'service_type' => ServiceType::WATER,
                'contact_info' => [
                    'phone' => '+370 5 266 2600',
                    'email' => 'info@vv.lt',
                    'website' => 'https://www.vv.lt',
                ],
            ],
            [
                'name' => 'Vilniaus Energija',
                'service_type' => ServiceType::HEATING,
                'contact_info' => [
                    'phone' => '+370 5 239 5555',
                    'email' => 'info@ve.lt',
                    'website' => 'https://www.ve.lt',
                ],
            ],
        ])->mapWithKeys(function (array $provider): array {
            $record = Provider::query()->updateOrCreate(
                [
                    'organization_id' => null,
                    'name' => $provider['name'],
                ],
                [
                    'service_type' => $provider['service_type']->value,
                    'contact_info' => $provider['contact_info'],
                ],
            );

            return [$provider['service_type']->value => $record];
        });
    }

    /**
     * @param  Collection<string, Provider>  $providers
     * @return Collection<int, Tariff>
     */
    private function seedTariffs(Collection $providers): Collection
    {
        $definitions = [
            [
                'provider' => ServiceType::ELECTRICITY->value,
                'name' => 'Ignitis Standard Time-of-Use',
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                        ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
                    ],
                    'weekend_logic' => 'apply_night_rate',
                ],
            ],
            [
                'provider' => ServiceType::WATER->value,
                'name' => 'Vilniaus Vandenys Standard',
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'supply_rate' => 0.97,
                    'sewage_rate' => 1.23,
                    'fixed_fee' => 0.85,
                ],
            ],
            [
                'provider' => ServiceType::HEATING->value,
                'name' => 'Vilniaus Energija Standard',
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.065,
                    'fixed_fee' => 0.00,
                ],
            ],
        ];

        return collect($definitions)->map(function (array $definition) use ($providers): Tariff {
            $provider = $providers[$definition['provider']];

            return Tariff::query()->updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'name' => $definition['name'],
                ],
                [
                    'remote_id' => null,
                    'configuration' => $definition['configuration'],
                    'active_from' => now()->subMonths(6)->startOfDay(),
                    'active_until' => null,
                ],
            );
        });
    }

    /**
     * @return Collection<string, UtilityService>
     */
    private function seedUtilityServices(): Collection
    {
        return collect([
            [
                'name' => 'Electricity',
                'slug' => 'electricity',
                'unit' => 'kWh',
                'pricing_model' => PricingModel::CONSUMPTION_BASED,
                'service_type' => ServiceType::ELECTRICITY,
                'description' => 'Electricity consumption charges for residential properties.',
            ],
            [
                'name' => 'Water',
                'slug' => 'water',
                'unit' => 'm3',
                'pricing_model' => PricingModel::HYBRID,
                'service_type' => ServiceType::WATER,
                'description' => 'Water supply and sewage charges with a fixed and variable component.',
            ],
            [
                'name' => 'Heating',
                'slug' => 'heating',
                'unit' => 'kWh',
                'pricing_model' => PricingModel::CONSUMPTION_BASED,
                'service_type' => ServiceType::HEATING,
                'description' => 'District heating utility charges.',
            ],
        ])->mapWithKeys(function (array $service): array {
            $record = UtilityService::query()->updateOrCreate(
                ['slug' => $service['slug']],
                [
                    'organization_id' => null,
                    'name' => $service['name'],
                    'unit_of_measurement' => $service['unit'],
                    'default_pricing_model' => $service['pricing_model']->value,
                    'calculation_formula' => null,
                    'is_global_template' => true,
                    'created_by_organization_id' => null,
                    'configuration_schema' => [
                        'required' => ['rate_schedule'],
                    ],
                    'validation_rules' => [
                        'rate_schedule' => 'array',
                    ],
                    'business_logic_config' => [
                        'auto_validation' => true,
                    ],
                    'service_type_bridge' => $service['service_type']->value,
                    'description' => $service['description'],
                    'is_active' => true,
                ],
            );

            return [$service['service_type']->value => $record];
        });
    }

    /**
     * @param  Collection<string, UtilityService>  $utilityServices
     * @param  Collection<string, Provider>  $providers
     * @param  Collection<int, Tariff>  $tariffs
     */
    private function seedServiceConfigurations(
        Collection $utilityServices,
        Collection $providers,
        Collection $tariffs,
    ): void {
        $properties = Property::query()
            ->select(['id', 'organization_id'])
            ->orderBy('id')
            ->get();

        if ($properties->isEmpty()) {
            return;
        }

        $organizations = Organization::query()
            ->select(['id'])
            ->whereIn('id', $properties->pluck('organization_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $tariffsByProvider = $tariffs->keyBy('provider_id');
        $effectiveFrom = now()->startOfMonth();

        $properties->each(function (Property $property) use (
            $effectiveFrom,
            $organizations,
            $providers,
            $tariffsByProvider,
            $utilityServices,
        ): void {
            $organization = $organizations->get($property->organization_id);

            if ($organization === null) {
                return;
            }

            foreach ($utilityServices as $serviceType => $utilityService) {
                $provider = $providers->get($serviceType);
                $tariff = $provider === null ? null : $tariffsByProvider->get($provider->id);

                ServiceConfiguration::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'property_id' => $property->id,
                        'utility_service_id' => $utilityService->id,
                        'effective_from' => $effectiveFrom,
                    ],
                    [
                        'pricing_model' => $utilityService->default_pricing_model->value,
                        'rate_schedule' => $this->rateScheduleFor($serviceType),
                        'distribution_method' => DistributionMethod::EQUAL->value,
                        'is_shared_service' => false,
                        'effective_until' => null,
                        'configuration_overrides' => null,
                        'tariff_id' => $tariff?->id,
                        'provider_id' => $provider?->id,
                        'area_type' => null,
                        'custom_formula' => null,
                        'is_active' => true,
                    ],
                );
            }
        });
    }

    /**
     * @return array<string, float>
     */
    private function rateScheduleFor(string $serviceType): array
    {
        return match ($serviceType) {
            ServiceType::ELECTRICITY->value => [
                'unit_rate' => 0.18,
            ],
            ServiceType::WATER->value => [
                'fixed_fee' => 0.85,
                'unit_rate' => 2.20,
            ],
            ServiceType::HEATING->value => [
                'unit_rate' => 0.065,
            ],
            default => [
                'unit_rate' => 0.10,
            ],
        };
    }
}
