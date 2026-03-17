<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\OrganizationStatus;
use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use App\Enums\PropertyType;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationRecipient;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\SystemTenant;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\Geography\BalticReferenceCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OperationalDemoDatasetSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = $this->upsertPlatformSuperadmin();
        $systemTenant = $this->upsertSystemTenant($superadmin);
        $platformNotification = $this->upsertPlatformNotification();

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

            $properties = collect(range(1, 8))->map(function (int $propertyIndex) use ($buildings, $manager, $organization, $sequence, $tenants): Property {
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

                collect(range(1, 2))->each(function (int $meterIndex) use ($manager, $organization, $property, $propertyIndex, $sequence, $tenant): void {
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

                    collect(range(1, 3))->each(function (int $readingIndex) use ($manager, $meter, $organization, $property, $propertyIndex, $sequence, $tenant): void {
                        $readingDate = Carbon::create(2026, 1, 1)
                            ->addMonths($readingIndex - 1)
                            ->addDays($propertyIndex);

                        MeterReading::query()->updateOrCreate(
                            [
                                'meter_id' => $meter->id,
                                'reading_date' => $readingDate->toDateString(),
                            ],
                            [
                                'organization_id' => $organization->id,
                                'property_id' => $property->id,
                                'submitted_by_user_id' => $readingIndex === 1 ? $manager->id : $tenant->id,
                                'reading_value' => (($sequence * 100) + ($propertyIndex * 10) + ($readingIndex * 5)) + 0.125,
                                'validation_status' => MeterReadingValidationStatus::VALID,
                                'submission_method' => $readingIndex === 1
                                    ? MeterReadingSubmissionMethod::ADMIN_MANUAL
                                    : MeterReadingSubmissionMethod::TENANT_PORTAL,
                                'notes' => null,
                            ],
                        );
                    });
                });

                Invoice::query()->updateOrCreate(
                    [
                        'invoice_number' => sprintf('DMO-INV-%02d-%02d', $sequence, $propertyIndex),
                    ],
                    [
                        'organization_id' => $organization->id,
                        'property_id' => $property->id,
                        'tenant_user_id' => $tenant->id,
                        'billing_period_start' => Carbon::create(2026, 2, 1)->toDateString(),
                        'billing_period_end' => Carbon::create(2026, 2, 28)->toDateString(),
                        'status' => $propertyIndex % 3 === 0 ? InvoiceStatus::PAID : InvoiceStatus::FINALIZED,
                        'currency' => 'EUR',
                        'total_amount' => 85 + ($propertyIndex * 7.5),
                        'amount_paid' => $propertyIndex % 3 === 0 ? 85 + ($propertyIndex * 7.5) : 0,
                        'due_date' => Carbon::create(2026, 3, 15)->addDays($propertyIndex)->toDateString(),
                        'finalized_at' => Carbon::create(2026, 3, 1)->addDays($propertyIndex),
                        'paid_at' => $propertyIndex % 3 === 0 ? Carbon::create(2026, 3, 5)->addDays($propertyIndex) : null,
                        'document_path' => null,
                        'notes' => sprintf('Demo invoice for %s', $property->name),
                    ],
                );

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

            PlatformNotificationRecipient::query()->updateOrCreate(
                [
                    'platform_notification_id' => $platformNotification->id,
                    'organization_id' => $organization->id,
                    'email' => $admin->email,
                ],
                [
                    'delivery_status' => 'sent',
                    'sent_at' => Carbon::create(2026, 3, 1)->addDays($organizationIndex),
                    'read_at' => null,
                    'failure_reason' => null,
                ],
            );
        }
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

    private function upsertPlatformNotification(): PlatformNotification
    {
        return PlatformNotification::query()->updateOrCreate(
            ['title' => 'Baltic demo dataset is ready'],
            [
                'body' => 'The seeded Baltic workspace now includes operational data for organizations, tenants, meters, and invoices.',
                'severity' => PlatformNotificationSeverity::INFO,
                'status' => PlatformNotificationStatus::SENT,
                'scheduled_for' => null,
                'sent_at' => Carbon::create(2026, 3, 1, 9, 0, 0),
            ],
        );
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
