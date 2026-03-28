<?php

namespace Database\Seeders;

use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Geography\BalticReferenceCatalog;
use App\Models\BillingRecord;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\ManagerPermission;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Subscription;
use App\Models\SystemTenant;
use App\Models\Tariff;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UtilityService;
use Database\Seeders\Support\OrganizationShowcaseCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OperationalDemoDatasetSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = $this->upsertPlatformSuperadmin();
        $systemTenant = $this->upsertSystemTenant($superadmin);

        $cities = BalticReferenceCatalog::cities();
        $locales = BalticReferenceCatalog::supportedLocaleCodes();

        foreach (OrganizationShowcaseCatalog::blueprints() as $organizationIndex => $blueprint) {
            $sequence = $organizationIndex + 1;
            $volumes = $blueprint['volumes'];

            $organizationPrototype = Organization::factory()
                ->{$blueprint['organization_factory_state']}()
                ->make([
                    'name' => $blueprint['name'],
                    'slug' => $blueprint['slug'],
                    'status' => $blueprint['status'],
                    'owner_user_id' => null,
                    'system_tenant_id' => $systemTenant->id,
                ]);

            $organization = Organization::query()->updateOrCreate(
                ['slug' => $organizationPrototype->slug],
                [
                    'name' => $organizationPrototype->name,
                    'status' => $organizationPrototype->status,
                    'system_tenant_id' => $systemTenant->id,
                ],
            );

            $admin = $this->upsertOrganizationUser(
                organization: $organization,
                email: sprintf('org%02d-admin@tenanto-demo.test', $sequence),
                name: $blueprint['admin_name'],
                role: UserRole::ADMIN,
                locale: $locales[$organizationIndex % count($locales)],
            );

            $manager = $this->upsertOrganizationUser(
                organization: $organization,
                email: sprintf('org%02d-manager@tenanto-demo.test', $sequence),
                name: $blueprint['manager_name'],
                role: UserRole::MANAGER,
                locale: $locales[($organizationIndex + 1) % count($locales)],
            );

            $organization->forceFill([
                'owner_user_id' => $admin->id,
                'system_tenant_id' => $systemTenant->id,
            ])->save();

            $this->syncMembership($organization, $admin, UserRole::ADMIN, $admin);
            $this->syncMembership($organization, $manager, UserRole::MANAGER, $admin);

            // Showcase managers intentionally demonstrate different write profiles per organization.
            ManagerPermission::syncForManager(
                $manager,
                $organization,
                $this->showcaseManagerPermissionMatrix($blueprint['slug']),
            );

            $subscriptionStartsAt = Carbon::create(2026, 1, 1)->addDays($organizationIndex);
            $subscriptionExpiresAt = $blueprint['is_trial']
                ? $subscriptionStartsAt->copy()->addDays(14)
                : Carbon::create(2027, 1, 1)->addDays($organizationIndex);

            $subscriptionPrototype = Subscription::factory()
                ->forPlan($blueprint['plan'])
                ->make([
                    'organization_id' => $organization->id,
                    'status' => $blueprint['subscription_status'],
                    'starts_at' => $subscriptionStartsAt,
                    'expires_at' => $subscriptionExpiresAt,
                    'is_trial' => $blueprint['is_trial'],
                ]);

            Subscription::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'starts_at' => $subscriptionStartsAt,
                ],
                [
                    'plan' => $subscriptionPrototype->plan,
                    'status' => $subscriptionPrototype->status,
                    'expires_at' => $subscriptionPrototype->expires_at,
                    'is_trial' => $subscriptionPrototype->is_trial,
                    'property_limit_snapshot' => $subscriptionPrototype->property_limit_snapshot,
                    'tenant_limit_snapshot' => $subscriptionPrototype->tenant_limit_snapshot,
                    'meter_limit_snapshot' => $subscriptionPrototype->meter_limit_snapshot,
                    'invoice_limit_snapshot' => $subscriptionPrototype->invoice_limit_snapshot,
                ],
            );

            $organizationSettingPrototype = OrganizationSetting::factory()
                ->demoBilling(
                    shortName: $blueprint['short_name'],
                    email: sprintf('billing-org%02d@tenanto-demo.test', $sequence),
                    phone: sprintf('+370600%04d', 1000 + $sequence),
                )
                ->make([
                    'organization_id' => $organization->id,
                ]);

            OrganizationSetting::query()->updateOrCreate(
                ['organization_id' => $organization->id],
                [
                    'billing_contact_name' => $organizationSettingPrototype->billing_contact_name,
                    'billing_contact_email' => $organizationSettingPrototype->billing_contact_email,
                    'billing_contact_phone' => $organizationSettingPrototype->billing_contact_phone,
                    'payment_instructions' => $organizationSettingPrototype->payment_instructions,
                    'invoice_footer' => $organizationSettingPrototype->invoice_footer,
                    'notification_preferences' => $organizationSettingPrototype->notification_preferences,
                ],
            );

            $organizationUtilityServices = $this->upsertOrganizationUtilityServices($organization, $sequence);
            $organizationProviderGraph = $this->upsertOrganizationProvidersAndTariffs($organization, $sequence);

            $buildings = collect(range(1, $volumes['buildings']))->map(function (int $buildingIndex) use ($blueprint, $cities, $organization, $organizationIndex, $sequence): Building {
                $city = $cities[(($organizationIndex * 3) + ($buildingIndex - 1)) % count($cities)];
                $buildingPrototype = Building::factory()
                    ->named(sprintf('Demo Building %02d-%02d', $sequence, $buildingIndex))
                    ->atBalticAddress(
                        city: $city,
                        street: sprintf('%s %d', $blueprint['street'], 10 + $buildingIndex),
                        addressLine2: $buildingIndex === 3 ? 'Block C' : null,
                        postalCode: $this->postalCodeFor($city['postal_code_pattern'], ($sequence * 10) + $buildingIndex),
                    )
                    ->make([
                        'organization_id' => $organization->id,
                    ]);

                return Building::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'name' => $buildingPrototype->name,
                    ],
                    [
                        'address_line_1' => $buildingPrototype->address_line_1,
                        'address_line_2' => $buildingPrototype->address_line_2,
                        'city' => $buildingPrototype->city,
                        'postal_code' => $buildingPrototype->postal_code,
                        'country_code' => $buildingPrototype->country_code,
                    ],
                );
            });

            $tenants = collect(range(1, $volumes['tenants']))->map(function (int $tenantIndex) use ($admin, $blueprint, $locales, $organization, $sequence): User {
                $tenant = $this->upsertOrganizationUser(
                    organization: $organization,
                    email: sprintf('org%02d-tenant%02d@tenanto-demo.test', $sequence, $tenantIndex),
                    name: sprintf('%s Resident %02d', $blueprint['tenant_prefix'], $tenantIndex),
                    role: UserRole::TENANT,
                    locale: $locales[($sequence + $tenantIndex) % count($locales)],
                );

                $this->syncMembership($organization, $tenant, UserRole::TENANT, $admin);

                return $tenant;
            });

            $properties = collect(range(1, $volumes['properties']))->map(function (int $propertyIndex) use ($buildings, $manager, $organization, $sequence, $tenants, $organizationProviderGraph, $organizationUtilityServices, $volumes): Property {
                $building = $buildings[($propertyIndex - 1) % $buildings->count()];
                $tenant = $tenants[($propertyIndex - 1) % $tenants->count()];
                $propertyType = PropertyType::cases()[($propertyIndex - 1) % count(PropertyType::cases())];
                $floorArea = 42 + ($propertyIndex * 3.5);
                $propertyPrototype = Property::factory()
                    ->unit(
                        name: sprintf('Demo Unit %02d-%02d', $sequence, $propertyIndex),
                        unitNumber: sprintf('%02d', 100 + $propertyIndex),
                        type: $propertyType,
                        floorArea: $floorArea,
                    )
                    ->make([
                        'organization_id' => $organization->id,
                        'building_id' => $building->id,
                    ]);

                $property = Property::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'name' => $propertyPrototype->name,
                    ],
                    [
                        'building_id' => $propertyPrototype->building_id,
                        'unit_number' => $propertyPrototype->unit_number,
                        'type' => $propertyPrototype->type,
                        'floor_area_sqm' => $propertyPrototype->floor_area_sqm,
                    ],
                );

                PropertyAssignment::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'property_id' => $property->id,
                        'tenant_user_id' => $tenant->id,
                    ],
                    [
                        'unit_area_sqm' => $floorArea,
                        'assigned_at' => Carbon::create(2026, 1, 15)->addDays($propertyIndex),
                        'unassigned_at' => null,
                    ],
                );

                Lease::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'property_id' => $property->id,
                        'tenant_user_id' => $tenant->id,
                    ],
                    [
                        'start_date' => Carbon::create(2025, 7, 1)->addDays($propertyIndex)->toDateString(),
                        'end_date' => Carbon::create(2027, 6, 30)->addDays($propertyIndex)->toDateString(),
                        'monthly_rent' => 550 + ($propertyIndex * 25),
                        'deposit' => 900 + ($propertyIndex * 60),
                        'is_active' => true,
                    ],
                );

                $this->upsertPropertyServiceConfigurations(
                    organization: $organization,
                    property: $property,
                    utilityServices: $organizationUtilityServices,
                    providerGraph: $organizationProviderGraph,
                );

                $propertyMeters = collect();

                collect(range(1, $volumes['meters_per_property']))->each(function (int $meterIndex) use ($manager, $organization, $property, $propertyIndex, $sequence, $tenant, $propertyMeters): void {
                    $meterType = MeterType::cases()[($propertyIndex + $meterIndex - 2) % count(MeterType::cases())];
                    $meterPrototype = Meter::factory()
                        ->identified(
                            identifier: sprintf('DMO-%02d-%02d-%02d', $sequence, $propertyIndex, $meterIndex),
                            type: $meterType,
                            name: sprintf('%s Meter %02d', Str::headline($meterType->value), $meterIndex),
                            installedAt: Carbon::create(2025, 6, 1)->addDays($propertyIndex + $meterIndex)->toDateString(),
                        )
                        ->make([
                            'organization_id' => $organization->id,
                            'property_id' => $property->id,
                        ]);

                    $meter = Meter::query()->updateOrCreate(
                        [
                            'identifier' => $meterPrototype->identifier,
                        ],
                        [
                            'organization_id' => $meterPrototype->organization_id,
                            'property_id' => $meterPrototype->property_id,
                            'name' => $meterPrototype->name,
                            'type' => $meterPrototype->type,
                            'status' => MeterStatus::ACTIVE,
                            'unit' => $meterPrototype->unit,
                            'installed_at' => $meterPrototype->installed_at,
                        ],
                    );

                    $propertyMeters->push($meter);

                    collect(range(0, 11))->each(function (int $readingIndex) use ($manager, $meter, $organization, $property, $propertyIndex, $sequence, $meterIndex, $tenant): void {
                        $readingDate = now()
                            ->startOfMonth()
                            ->subMonths(11 - $readingIndex)
                            ->addDays($propertyIndex);

                        MeterReading::query()->updateOrCreate(
                            [
                                'meter_id' => $meter->id,
                                'reading_date' => $readingDate->toDateString(),
                            ],
                            [
                                'organization_id' => $organization->id,
                                'property_id' => $property->id,
                                'submitted_by_user_id' => $readingIndex < 4 ? $manager->id : $tenant->id,
                                'reading_value' => (($sequence * 100) + ($propertyIndex * 10) + ($meterIndex * 3) + (($readingIndex + 1) * 5)) + 0.125,
                                'validation_status' => MeterReadingValidationStatus::VALID,
                                'submission_method' => $readingIndex < 4
                                    ? MeterReadingSubmissionMethod::ADMIN_MANUAL
                                    : MeterReadingSubmissionMethod::TENANT_PORTAL,
                                'notes' => null,
                            ],
                        );
                    });
                });

                collect(range(0, $volumes['invoices_per_property'] - 1))->each(function (int $invoiceIndex) use ($organization, $property, $tenant, $propertyIndex, $sequence, $organizationUtilityServices, $propertyMeters): void {
                    $periodStart = now()->startOfMonth()->subMonths(2 - $invoiceIndex);
                    $periodEnd = $periodStart->copy()->endOfMonth();
                    $totalAmount = 95 + ($propertyIndex * 7.5) + ($invoiceIndex * 6.25);

                    $status = match ($invoiceIndex) {
                        0 => InvoiceStatus::PAID,
                        1 => InvoiceStatus::FINALIZED,
                        default => InvoiceStatus::OVERDUE,
                    };

                    $amountPaid = $status === InvoiceStatus::PAID ? $totalAmount : 0;

                    $invoice = Invoice::query()->updateOrCreate(
                        [
                            'invoice_number' => sprintf('DMO-INV-%02d-%02d-%02d', $sequence, $propertyIndex, $invoiceIndex + 1),
                        ],
                        [
                            'organization_id' => $organization->id,
                            'property_id' => $property->id,
                            'tenant_user_id' => $tenant->id,
                            'billing_period_start' => $periodStart->toDateString(),
                            'billing_period_end' => $periodEnd->toDateString(),
                            'status' => $status,
                            'currency' => 'EUR',
                            'total_amount' => $totalAmount,
                            'amount_paid' => $amountPaid,
                            'due_date' => $periodEnd->copy()->addDays(14)->toDateString(),
                            'finalized_at' => $periodEnd->copy()->addDay(),
                            'paid_at' => $status === InvoiceStatus::PAID ? $periodEnd->copy()->addDays(5) : null,
                            'document_path' => null,
                            'notes' => sprintf('Demo invoice %d for %s', $invoiceIndex + 1, $property->name),
                        ],
                    );

                    $this->upsertInvoiceDetailRecords(
                        organization: $organization,
                        property: $property,
                        tenant: $tenant,
                        invoice: $invoice,
                        utilityServices: $organizationUtilityServices,
                        meters: $propertyMeters,
                    );
                });

                return $property;
            });

            $project = Project::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => sprintf('%s Modernization Program', $blueprint['short_name']),
                ],
                [
                    'property_id' => $properties->first()->id,
                    'building_id' => $buildings->first()->id,
                    'created_by_user_id' => $admin->id,
                    'assigned_to_user_id' => $manager->id,
                    'description' => sprintf('Operational improvement plan for %s.', $blueprint['short_name']),
                    'type' => 'improvement',
                    'status' => 'active',
                    'priority' => 'high',
                    'start_date' => Carbon::create(2026, 2, 1)->toDateString(),
                    'due_date' => Carbon::create(2026, 6, 30)->toDateString(),
                    'completed_at' => null,
                    'budget' => 12500,
                    'actual_cost' => 4200,
                    'metadata' => ['seeded' => true],
                ],
            );

            collect(range(1, $volumes['tasks']))->each(function (int $taskIndex) use ($manager, $project, $sequence, $tenants): void {
                $task = Task::query()->updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'title' => sprintf('Demo Task %02d-%02d', $sequence, $taskIndex),
                    ],
                    [
                        'organization_id' => $project->organization_id,
                        'description' => $taskIndex === 1 ? 'Inspect shared systems.' : 'Coordinate resident communication.',
                        'status' => $taskIndex === 1 ? 'in_progress' : 'review',
                        'priority' => $taskIndex === 1 ? 'high' : 'medium',
                        'created_by_user_id' => $manager->id,
                        'due_date' => Carbon::create(2026, 4, 1)->addDays($taskIndex)->toDateString(),
                        'completed_at' => null,
                        'estimated_hours' => 6 + $taskIndex,
                        'actual_hours' => 2 + $taskIndex,
                        'checklist' => ['prepared' => true],
                    ],
                );

                $assignee = $taskIndex === 1 ? $manager : $tenants[($taskIndex - 1) % $tenants->count()];

                $assignment = TaskAssignment::query()->updateOrCreate(
                    [
                        'task_id' => $task->id,
                        'user_id' => $assignee->id,
                        'role' => 'assignee',
                    ],
                    [
                        'assigned_at' => Carbon::create(2026, 2, 10)->addDays($taskIndex),
                        'completed_at' => null,
                        'notes' => 'Seeded operational assignment.',
                    ],
                );

                TimeEntry::query()->updateOrCreate(
                    [
                        'task_id' => $task->id,
                        'user_id' => $assignee->id,
                        'assignment_id' => $assignment->id,
                        'logged_at' => Carbon::create(2026, 2, 20)->addDays($taskIndex),
                    ],
                    [
                        'hours' => 1.5 + $taskIndex,
                        'description' => 'Seeded progress update.',
                        'metadata' => ['seeded' => true],
                    ],
                );
            });

        }
    }

    private function upsertOrganizationUtilityServices(Organization $organization, int $sequence): Collection
    {
        $definitions = [
            ServiceType::ELECTRICITY->value => [
                'name' => sprintf('Org %02d Electricity', $sequence),
                'slug' => sprintf('org-%02d-electricity', $sequence),
                'pricing_model' => PricingModel::CONSUMPTION_BASED,
                'description' => 'Electricity utility for tenant consumption billing.',
            ],
            ServiceType::WATER->value => [
                'name' => sprintf('Org %02d Water', $sequence),
                'slug' => sprintf('org-%02d-water', $sequence),
                'pricing_model' => PricingModel::HYBRID,
                'description' => 'Water utility with fixed and variable components.',
            ],
            ServiceType::HEATING->value => [
                'name' => sprintf('Org %02d Heating', $sequence),
                'slug' => sprintf('org-%02d-heating', $sequence),
                'pricing_model' => PricingModel::CONSUMPTION_BASED,
                'description' => 'Heating utility for seasonal usage.',
            ],
        ];

        return collect($definitions)->mapWithKeys(function (array $definition, string $serviceType) use ($organization): array {
            $serviceTypeEnum = ServiceType::from($serviceType);

            $service = UtilityService::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'organization_id' => $organization->id,
                    'name' => $definition['name'],
                    'unit_of_measurement' => $serviceTypeEnum->defaultUnit()->value,
                    'default_pricing_model' => $definition['pricing_model'],
                    'calculation_formula' => null,
                    'is_global_template' => false,
                    'created_by_organization_id' => $organization->id,
                    'configuration_schema' => ['required' => ['rate_schedule']],
                    'validation_rules' => ['rate_schedule' => 'array'],
                    'business_logic_config' => ['auto_validation' => true],
                    'service_type_bridge' => $serviceTypeEnum,
                    'description' => $definition['description'],
                    'is_active' => true,
                ],
            );

            return [$serviceType => $service];
        });
    }

    private function upsertOrganizationProvidersAndTariffs(Organization $organization, int $sequence): Collection
    {
        $definitions = [
            ServiceType::ELECTRICITY->value => [
                'provider_name' => sprintf('Org %02d Grid', $sequence),
                'contact_info' => [
                    'phone' => sprintf('+3707001%04d', $sequence),
                    'email' => sprintf('grid%02d@tenanto-demo.test', $sequence),
                    'website' => sprintf('https://grid-%02d.tenanto-demo.test', $sequence),
                ],
                'tariff_name' => sprintf('Org %02d Peak Electricity', $sequence),
                'remote_id' => sprintf('EL-%02d-A', $sequence),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.185],
                        ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.105],
                    ],
                ],
                'active_until' => null,
            ],
            ServiceType::WATER->value => [
                'provider_name' => sprintf('Org %02d Waterworks', $sequence),
                'contact_info' => [
                    'phone' => sprintf('+3707002%04d', $sequence),
                    'email' => sprintf('water%02d@tenanto-demo.test', $sequence),
                    'website' => sprintf('https://water-%02d.tenanto-demo.test', $sequence),
                ],
                'tariff_name' => sprintf('Org %02d Water Standard', $sequence),
                'remote_id' => sprintf('WT-%02d-A', $sequence),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'supply_rate' => 0.97,
                    'sewage_rate' => 1.23,
                    'fixed_fee' => 0.85,
                ],
                'active_until' => now()->addMonths(18)->startOfDay(),
            ],
            ServiceType::HEATING->value => [
                'provider_name' => sprintf('Org %02d Heating Cooperative', $sequence),
                'contact_info' => [
                    'phone' => sprintf('+3707003%04d', $sequence),
                    'email' => sprintf('heating%02d@tenanto-demo.test', $sequence),
                    'website' => sprintf('https://heating-%02d.tenanto-demo.test', $sequence),
                ],
                'tariff_name' => sprintf('Org %02d Seasonal Heating', $sequence),
                'remote_id' => sprintf('HT-%02d-A', $sequence),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.42,
                    'fixed_fee' => 4.25,
                ],
                'active_until' => now()->addMonths(12)->startOfDay(),
            ],
        ];

        return collect($definitions)->mapWithKeys(function (array $definition, string $serviceType) use ($organization): array {
            $providerPrototype = Provider::factory()
                ->forOrganization($organization)
                ->withSupportContact(
                    name: $definition['provider_name'],
                    serviceType: ServiceType::from($serviceType),
                    contactInfo: $definition['contact_info'],
                )
                ->make();

            $provider = Provider::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => $providerPrototype->name,
                ],
                [
                    'service_type' => $providerPrototype->service_type,
                    'contact_info' => $providerPrototype->contact_info,
                ],
            );

            $tariff = Tariff::query()->updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'name' => $definition['tariff_name'],
                ],
                [
                    'remote_id' => $definition['remote_id'],
                    'configuration' => $definition['configuration'],
                    'active_from' => now()->subMonths(2)->startOfDay(),
                    'active_until' => $definition['active_until'],
                ],
            );

            return [$serviceType => ['provider' => $provider, 'tariff' => $tariff]];
        });
    }

    private function upsertPropertyServiceConfigurations(
        Organization $organization,
        Property $property,
        Collection $utilityServices,
        Collection $providerGraph,
    ): void {
        $effectiveFrom = now()->startOfMonth();

        foreach ($utilityServices as $serviceType => $utilityService) {
            $providerGraphEntry = $providerGraph->get($serviceType);

            if ($providerGraphEntry === null) {
                continue;
            }

            ServiceConfiguration::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'property_id' => $property->id,
                    'utility_service_id' => $utilityService->id,
                    'effective_from' => $effectiveFrom,
                ],
                [
                    'pricing_model' => $this->serviceConfigurationPricingModel($serviceType),
                    'rate_schedule' => $this->serviceRateSchedule($serviceType),
                    'distribution_method' => $this->serviceConfigurationDistributionMethod($serviceType),
                    'is_shared_service' => $serviceType === ServiceType::HEATING->value,
                    'effective_until' => $this->serviceConfigurationEffectiveUntil($serviceType),
                    'configuration_overrides' => $this->serviceConfigurationOverrides($serviceType),
                    'tariff_id' => $providerGraphEntry['tariff']->id,
                    'provider_id' => $providerGraphEntry['provider']->id,
                    'area_type' => $this->serviceConfigurationAreaType($serviceType),
                    'custom_formula' => $this->serviceConfigurationCustomFormula($serviceType),
                    'is_active' => true,
                ],
            );
        }
    }

    private function serviceConfigurationPricingModel(string $serviceType): PricingModel
    {
        return match ($serviceType) {
            ServiceType::ELECTRICITY->value => PricingModel::TIME_OF_USE,
            ServiceType::WATER->value => PricingModel::HYBRID,
            ServiceType::HEATING->value => PricingModel::CUSTOM_FORMULA,
            default => PricingModel::CONSUMPTION_BASED,
        };
    }

    private function serviceConfigurationDistributionMethod(string $serviceType): DistributionMethod
    {
        return match ($serviceType) {
            ServiceType::ELECTRICITY->value => DistributionMethod::BY_CONSUMPTION,
            ServiceType::WATER->value => DistributionMethod::EQUAL,
            ServiceType::HEATING->value => DistributionMethod::CUSTOM_FORMULA,
            default => DistributionMethod::EQUAL,
        };
    }

    private function serviceConfigurationEffectiveUntil(string $serviceType): ?Carbon
    {
        return match ($serviceType) {
            ServiceType::WATER->value => now()->addMonths(12)->startOfMonth(),
            default => null,
        };
    }

    private function serviceConfigurationOverrides(string $serviceType): ?array
    {
        return match ($serviceType) {
            ServiceType::ELECTRICITY->value => [
                'loss_factor' => 1.02,
            ],
            ServiceType::WATER->value => [
                'base_fee' => 1.35,
            ],
            ServiceType::HEATING->value => [
                'seasonal_index' => 1.15,
                'shared_floor_weight' => 0.35,
            ],
            default => null,
        };
    }

    private function serviceConfigurationAreaType(string $serviceType): ?string
    {
        return $serviceType === ServiceType::HEATING->value
            ? 'heated'
            : null;
    }

    private function serviceConfigurationCustomFormula(string $serviceType): ?string
    {
        return $serviceType === ServiceType::HEATING->value
            ? '({consumption} * {unit_rate}) + ({area_sqm} * {shared_floor_weight}) + {base_fee}'
            : null;
    }

    private function upsertInvoiceDetailRecords(
        Organization $organization,
        Property $property,
        User $tenant,
        Invoice $invoice,
        Collection $utilityServices,
        Collection $meters,
    ): void {
        $serviceRows = [
            ServiceType::ELECTRICITY->value => [
                'description' => 'Electricity charge',
                'quantity' => 180.00,
                'unit' => 'kWh',
                'unit_price' => 0.1850,
            ],
            ServiceType::WATER->value => [
                'description' => 'Water supply',
                'quantity' => 9.50,
                'unit' => 'm3',
                'unit_price' => 2.0500,
            ],
            ServiceType::HEATING->value => [
                'description' => 'Heating charge',
                'quantity' => 120.00,
                'unit' => 'kWh',
                'unit_price' => 0.4200,
            ],
        ];

        $snapshotMeter = $meters->first();

        foreach ($serviceRows as $serviceType => $row) {
            $utilityService = $utilityServices->get($serviceType);

            if (! $utilityService instanceof UtilityService) {
                continue;
            }

            $total = round($row['quantity'] * $row['unit_price'], 2);

            InvoiceItem::query()->updateOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'description' => $row['description'],
                ],
                [
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'],
                    'unit_price' => $row['unit_price'],
                    'total' => $total,
                    'meter_reading_snapshot' => [
                        'meter_identifier' => $snapshotMeter?->identifier,
                        'billing_period_start' => $invoice->billing_period_start?->toDateString(),
                        'billing_period_end' => $invoice->billing_period_end?->toDateString(),
                    ],
                ],
            );

            BillingRecord::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'property_id' => $property->id,
                    'utility_service_id' => $utilityService->id,
                    'invoice_id' => $invoice->id,
                    'tenant_user_id' => $tenant->id,
                    'billing_period_start' => $invoice->billing_period_start,
                    'billing_period_end' => $invoice->billing_period_end,
                ],
                [
                    'amount' => $total,
                    'consumption' => $row['quantity'],
                    'rate' => $row['unit_price'],
                    'meter_reading_start' => null,
                    'meter_reading_end' => null,
                    'notes' => sprintf('Seeded %s billing record', strtolower($row['description'])),
                ],
            );
        }
    }

    private function serviceRateSchedule(string $serviceType): array
    {
        return match ($serviceType) {
            ServiceType::ELECTRICITY->value => [
                'zones' => [
                    ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.185],
                    ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.105],
                ],
                'base_fee' => 2.10,
            ],
            ServiceType::WATER->value => [
                'fixed_fee' => 1.35,
                'unit_rate' => 2.05,
            ],
            ServiceType::HEATING->value => [
                'unit_rate' => 0.42,
                'base_fee' => 4.25,
            ],
            default => [
                'unit_rate' => 0.10,
            ],
        };
    }

    /**
     * @return array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>
     */
    private function showcaseManagerPermissionMatrix(string $organizationSlug): array
    {
        $presets = ManagerPermissionCatalog::presets();

        return match ($organizationSlug) {
            'demo-baltic-starter' => $presets['read_only']['matrix'],
            'demo-baltic-basic' => $presets['property_manager']['matrix'],
            'demo-baltic-professional' => $presets['billing_manager']['matrix'],
            'demo-baltic-enterprise' => $presets['full_access']['matrix'],
            'demo-baltic-custom' => $this->customUtilityManagerMatrix(),
            default => ManagerPermissionCatalog::defaultMatrix(),
        };
    }

    /**
     * @return array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>
     */
    private function customUtilityManagerMatrix(): array
    {
        $matrix = ManagerPermissionCatalog::defaultMatrix();

        foreach ([
            'billing',
            'meters',
            'meter_readings',
            'providers',
            'tariffs',
            'service_configurations',
            'utility_services',
        ] as $resource) {
            $matrix[$resource] = [
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => false,
            ];
        }

        $matrix['properties'] = [
            'can_create' => false,
            'can_edit' => true,
            'can_delete' => false,
        ];

        return $matrix;
    }

    private function upsertPlatformSuperadmin(): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'platform.demo@tenanto-demo.test'],
            [
                'name' => 'Platform Demo Operator',
                'role' => UserRole::SUPERADMIN,
                'status' => UserStatus::ACTIVE,
                'locale' => 'en',
                'organization_id' => null,
                'password' => bcrypt('password'),
                'system_tenant_id' => null,
                'is_super_admin' => true,
            ],
        );
    }

    private function upsertSystemTenant(User $superadmin): SystemTenant
    {
        $systemTenant = SystemTenant::query()->updateOrCreate(
            ['slug' => 'demo-baltic-platform'],
            [
                'name' => 'Baltic Demo Platform',
                'domain' => 'demo.tenanto.test',
                'status' => 'active',
                'subscription_plan' => 'enterprise',
                'settings' => ['timezone' => 'Europe/Vilnius'],
                'resource_quotas' => ['max_users' => 2500, 'max_storage_gb' => 500],
                'billing_info' => ['currency' => 'EUR'],
                'primary_contact_email' => 'platform.demo@tenanto-demo.test',
                'created_by_admin_id' => $superadmin->id,
            ],
        );

        $superadmin->forceFill([
            'system_tenant_id' => $systemTenant->id,
            'is_super_admin' => true,
        ])->save();

        return $systemTenant;
    }

    private function upsertOrganizationUser(
        Organization $organization,
        string $email,
        string $name,
        UserRole $role,
        string $locale,
    ): User {
        $userPrototype = match ($role) {
            UserRole::ADMIN => User::factory()->admin(),
            UserRole::MANAGER => User::factory()->manager(),
            UserRole::TENANT => User::factory()->tenant(),
            default => User::factory()->state(['role' => $role]),
        };

        $user = $userPrototype
            ->withLocale($locale)
            ->make([
                'organization_id' => $organization->id,
                'email' => $email,
                'name' => $name,
            ]);

        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'organization_id' => $organization->id,
                'name' => $user->name,
                'role' => $user->role,
                'status' => UserStatus::ACTIVE,
                'locale' => $user->locale,
                'password' => $user->password,
                'system_tenant_id' => $organization->system_tenant_id,
                'is_super_admin' => false,
            ],
        );
    }

    private function syncMembership(Organization $organization, User $user, UserRole $role, User $inviter): void
    {
        OrganizationUser::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            [
                'role' => $role->value,
                'permissions' => null,
                'joined_at' => now()->subMonths(2),
                'left_at' => null,
                'is_active' => true,
                'invited_by' => $inviter->id,
            ],
        );
    }

    private function postalCodeFor(string $pattern, int $seed): string
    {
        $digits = str_pad((string) $seed, substr_count($pattern, '#'), '0', STR_PAD_LEFT);
        $index = 0;

        return (string) str($pattern)->replaceMatches('/#/', function () use (&$digits, &$index): string {
            $digit = $digits[$index] ?? '0';
            $index++;

            return $digit;
        });
    }
}
