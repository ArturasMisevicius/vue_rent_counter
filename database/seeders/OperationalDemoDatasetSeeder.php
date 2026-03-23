<?php

namespace Database\Seeders;

use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\OrganizationStatus;
use App\Enums\PricingModel;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Support\Geography\BalticReferenceCatalog;
use App\Models\BillingRecord;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\ServiceConfiguration;
use App\Models\Subscription;
use App\Models\SystemTenant;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UtilityService;
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

        foreach ($this->organizationBlueprints() as $organizationIndex => $blueprint) {
            $sequence = $organizationIndex + 1;

            $organization = Organization::query()->updateOrCreate(
                ['slug' => sprintf('demo-baltic-%02d', $sequence)],
                [
                    'name' => $blueprint['name'],
                    'status' => OrganizationStatus::ACTIVE,
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

            Subscription::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'starts_at' => Carbon::create(2026, 1, 1)->addDays($organizationIndex),
                ],
                [
                    'plan' => SubscriptionPlan::PROFESSIONAL,
                    'status' => SubscriptionStatus::ACTIVE,
                    'expires_at' => Carbon::create(2027, 1, 1)->addDays($organizationIndex),
                    'is_trial' => false,
                    'property_limit_snapshot' => SubscriptionPlan::PROFESSIONAL->limits()['properties'],
                    'tenant_limit_snapshot' => SubscriptionPlan::PROFESSIONAL->limits()['tenants'],
                    'meter_limit_snapshot' => SubscriptionPlan::PROFESSIONAL->limits()['meters'],
                    'invoice_limit_snapshot' => SubscriptionPlan::PROFESSIONAL->limits()['invoices'],
                ],
            );

            OrganizationSetting::query()->updateOrCreate(
                ['organization_id' => $organization->id],
                [
                    'billing_contact_name' => sprintf('%s Billing Team', $blueprint['short_name']),
                    'billing_contact_email' => sprintf('billing-org%02d@tenanto-demo.test', $sequence),
                    'billing_contact_phone' => sprintf('+370600%04d', 1000 + $sequence),
                    'payment_instructions' => 'Pay by bank transfer and include your invoice reference.',
                    'invoice_footer' => 'Thank you for paying on time.',
                    'notification_preferences' => [
                        'invoice_reminders' => true,
                        'payment_receipts' => true,
                        'reading_deadline_alerts' => true,
                    ],
                ],
            );

            $organizationUtilityServices = $this->upsertOrganizationUtilityServices($organization, $sequence);

            $buildings = collect(range(1, 3))->map(function (int $buildingIndex) use ($blueprint, $cities, $organization, $organizationIndex, $sequence): Building {
                $city = $cities[(($organizationIndex * 3) + ($buildingIndex - 1)) % count($cities)];

                return Building::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'name' => sprintf('Demo Building %02d-%02d', $sequence, $buildingIndex),
                    ],
                    [
                        'address_line_1' => sprintf('%s %d', $blueprint['street'], 10 + $buildingIndex),
                        'address_line_2' => $buildingIndex === 3 ? 'Block C' : null,
                        'city' => $city['name'],
                        'postal_code' => $this->postalCodeFor($city['postal_code_pattern'], ($sequence * 10) + $buildingIndex),
                        'country_code' => $city['country_code'],
                    ],
                );
            });

            $tenants = collect(range(1, 8))->map(function (int $tenantIndex) use ($blueprint, $locales, $organization, $sequence): User {
                return $this->upsertOrganizationUser(
                    organization: $organization,
                    email: sprintf('org%02d-tenant%02d@tenanto-demo.test', $sequence, $tenantIndex),
                    name: sprintf('%s Resident %02d', $blueprint['tenant_prefix'], $tenantIndex),
                    role: UserRole::TENANT,
                    locale: $locales[($sequence + $tenantIndex) % count($locales)],
                );
            });

            $properties = collect(range(1, 8))->map(function (int $propertyIndex) use ($buildings, $manager, $organization, $sequence, $tenants, $organizationUtilityServices): Property {
                $building = $buildings[($propertyIndex - 1) % $buildings->count()];
                $tenant = $tenants[$propertyIndex - 1];
                $propertyType = PropertyType::cases()[($propertyIndex - 1) % count(PropertyType::cases())];
                $floorArea = 42 + ($propertyIndex * 3.5);

                $property = Property::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'name' => sprintf('Demo Unit %02d-%02d', $sequence, $propertyIndex),
                    ],
                    [
                        'building_id' => $building->id,
                        'unit_number' => sprintf('%02d', 100 + $propertyIndex),
                        'type' => $propertyType,
                        'floor_area_sqm' => $floorArea,
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
                );

                $propertyMeters = collect();

                collect(range(1, 2))->each(function (int $meterIndex) use ($manager, $organization, $property, $propertyIndex, $sequence, $tenant, $propertyMeters): void {
                    $meterType = MeterType::cases()[($propertyIndex + $meterIndex - 2) % count(MeterType::cases())];
                    $meter = Meter::query()->updateOrCreate(
                        [
                            'identifier' => sprintf('DMO-%02d-%02d-%02d', $sequence, $propertyIndex, $meterIndex),
                        ],
                        [
                            'organization_id' => $organization->id,
                            'property_id' => $property->id,
                            'name' => sprintf('%s Meter %02d', Str::headline($meterType->value), $meterIndex),
                            'type' => $meterType,
                            'status' => MeterStatus::ACTIVE,
                            'unit' => $meterType->defaultUnit(),
                            'installed_at' => Carbon::create(2025, 6, 1)->addDays($propertyIndex + $meterIndex)->toDateString(),
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

                collect(range(0, 2))->each(function (int $invoiceIndex) use ($organization, $property, $tenant, $propertyIndex, $sequence, $organizationUtilityServices, $propertyMeters): void {
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

            collect(range(1, 2))->each(function (int $taskIndex) use ($manager, $project, $sequence, $tenants): void {
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

                $assignee = $taskIndex === 1 ? $manager : $tenants[$taskIndex - 1];

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
                    'unit_of_measurement' => $serviceTypeEnum->defaultUnit(),
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

    private function upsertPropertyServiceConfigurations(
        Organization $organization,
        Property $property,
        Collection $utilityServices,
    ): void {
        $effectiveFrom = now()->startOfMonth();

        foreach ($utilityServices as $serviceType => $utilityService) {
            ServiceConfiguration::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'property_id' => $property->id,
                    'utility_service_id' => $utilityService->id,
                    'effective_from' => $effectiveFrom,
                ],
                [
                    'pricing_model' => $utilityService->default_pricing_model,
                    'rate_schedule' => $this->serviceRateSchedule($serviceType),
                    'distribution_method' => DistributionMethod::EQUAL,
                    'is_shared_service' => false,
                    'effective_until' => null,
                    'configuration_overrides' => null,
                    'tariff_id' => null,
                    'provider_id' => null,
                    'area_type' => null,
                    'custom_formula' => null,
                    'is_active' => true,
                ],
            );
        }
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
                'unit_rate' => 0.185,
            ],
            ServiceType::WATER->value => [
                'fixed_fee' => 1.35,
                'unit_rate' => 2.05,
            ],
            ServiceType::HEATING->value => [
                'unit_rate' => 0.42,
            ],
            default => [
                'unit_rate' => 0.10,
            ],
        };
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
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'organization_id' => $organization->id,
                'name' => $name,
                'role' => $role,
                'status' => UserStatus::ACTIVE,
                'locale' => $locale,
                'password' => bcrypt('password'),
                'system_tenant_id' => $organization->system_tenant_id,
                'is_super_admin' => false,
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

    /**
     * @return array<int, array{
     *     name: string,
     *     short_name: string,
     *     street: string,
     *     admin_name: string,
     *     manager_name: string,
     *     tenant_prefix: string
     * }>
     */
    private function organizationBlueprints(): array
    {
        return [
            ['name' => 'Vilnius Riverside Homes', 'short_name' => 'Vilnius Riverside', 'street' => 'Neries Quay', 'admin_name' => 'Austeja Petrauskaite', 'manager_name' => 'Mantas Vaitkus', 'tenant_prefix' => 'Vilnius'],
            ['name' => 'Kaunas Central Lofts', 'short_name' => 'Kaunas Central', 'street' => 'Laisves Avenue', 'admin_name' => 'Monika Jankauskaite', 'manager_name' => 'Tadas Kazlauskas', 'tenant_prefix' => 'Kaunas'],
            ['name' => 'Klaipeda Port Residences', 'short_name' => 'Klaipeda Port', 'street' => 'Danes Street', 'admin_name' => 'Greta Mockute', 'manager_name' => 'Rokas Butkus', 'tenant_prefix' => 'Klaipeda'],
            ['name' => 'Riga Old Town Suites', 'short_name' => 'Riga Old Town', 'street' => 'Valnu Street', 'admin_name' => 'Elina Ozola', 'manager_name' => 'Janis Berzins', 'tenant_prefix' => 'Riga'],
            ['name' => 'Jurmala Coast Apartments', 'short_name' => 'Jurmala Coast', 'street' => 'Jomas Street', 'admin_name' => 'Liga Kalnina', 'manager_name' => 'Martins Liepins', 'tenant_prefix' => 'Jurmala'],
            ['name' => 'Daugavpils Civic Center', 'short_name' => 'Daugavpils Civic', 'street' => 'Rigas Street', 'admin_name' => 'Anete Zalite', 'manager_name' => 'Edgars Sile', 'tenant_prefix' => 'Daugavpils'],
            ['name' => 'Tallinn Harbor Offices', 'short_name' => 'Tallinn Harbor', 'street' => 'Sadama Street', 'admin_name' => 'Mari Tamm', 'manager_name' => 'Rasmus Saar', 'tenant_prefix' => 'Tallinn'],
            ['name' => 'Tartu Innovation Campus', 'short_name' => 'Tartu Innovation', 'street' => 'Riia Street', 'admin_name' => 'Kertu Ots', 'manager_name' => 'Karl Pold', 'tenant_prefix' => 'Tartu'],
            ['name' => 'Parnu Seaside Residences', 'short_name' => 'Parnu Seaside', 'street' => 'Ruutli Street', 'admin_name' => 'Liis Kask', 'manager_name' => 'Henri Mets', 'tenant_prefix' => 'Parnu'],
            ['name' => 'Narva Border Plaza', 'short_name' => 'Narva Border', 'street' => 'Pushkini Street', 'admin_name' => 'Kristi Toom', 'manager_name' => 'Marko Vaher', 'tenant_prefix' => 'Narva'],
        ];
    }
}
