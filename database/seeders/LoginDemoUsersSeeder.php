<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\OrganizationStatus;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
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
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LoginDemoUsersSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password';

    public function run(): void
    {
        $superadmin = $this->upsertUser(
            name: 'System Superadmin',
            email: 'superadmin@example.com',
            role: UserRole::SUPERADMIN,
            organizationId: null,
        );

        $organizationPrototype = Organization::factory()->make([
            'name' => 'Tenanto Demo Organization',
            'slug' => 'tenanto-demo-organization',
            'status' => OrganizationStatus::ACTIVE,
            'owner_user_id' => null,
        ]);

        $organization = Organization::query()->updateOrCreate(
            ['slug' => $organizationPrototype->slug],
            [
                'name' => $organizationPrototype->name,
                'status' => $organizationPrototype->status,
                'owner_user_id' => null,
            ],
        );

        $organizationSettingPrototype = OrganizationSetting::factory()
            ->demoBilling(
                shortName: 'Tenanto Demo',
                email: 'billing@tenanto.test',
                phone: '+37060000000',
            )
            ->make([
                'organization_id' => $organization->id,
                'payment_instructions' => 'Pay by bank transfer or at the office.',
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

        $subscriptionStartsAt = Carbon::create(2026, 1, 1)->startOfDay();
        $subscriptionPrototype = Subscription::factory()
            ->professional()
            ->active()
            ->make([
                'organization_id' => $organization->id,
                'starts_at' => $subscriptionStartsAt,
                'expires_at' => Carbon::create(2027, 1, 1)->startOfDay(),
                'is_trial' => false,
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

        $admin = $this->upsertUser(
            name: 'Demo Admin',
            email: 'admin@example.com',
            role: UserRole::ADMIN,
            organizationId: $organization->id,
        );

        $manager = $this->upsertUser(
            name: 'Demo Manager',
            email: 'manager@example.com',
            role: UserRole::MANAGER,
            organizationId: $organization->id,
        );

        $tenantAlina = $this->upsertUser(
            name: 'Alina Petrauskienė',
            email: 'tenant.alina@example.com',
            role: UserRole::TENANT,
            organizationId: $organization->id,
        );

        $tenantMarius = $this->upsertUser(
            name: 'Marius Jonaitis',
            email: 'tenant.marius@example.com',
            role: UserRole::TENANT,
            organizationId: $organization->id,
        );

        $organization->forceFill([
            'owner_user_id' => $admin->id,
        ])->save();

        $this->syncMembership($organization, $admin, UserRole::ADMIN);
        $this->syncMembership($organization, $manager, UserRole::MANAGER);
        $this->syncMembership($organization, $tenantAlina, UserRole::TENANT);
        $this->syncMembership($organization, $tenantMarius, UserRole::TENANT);

        ManagerPermission::syncForManager(
            $manager,
            $organization,
            ManagerPermissionCatalog::presets()['property_manager']['matrix'],
        );

        $buildingPrototype = Building::factory()
            ->named('Vilnius Central Residences')
            ->atBalticAddress(
                city: ['name' => 'Vilnius', 'country_code' => 'LT', 'postal_code_pattern' => 'LT-#####'],
                street: 'Gedimino pr. 25',
                postalCode: '01103',
            )
            ->make([
                'organization_id' => $organization->id,
            ]);

        $building = Building::query()->updateOrCreate(
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

        $propertyAlinaPrototype = Property::factory()
            ->unit('Apartment 101', '101', PropertyType::APARTMENT, 58.40)
            ->make([
                'organization_id' => $organization->id,
                'building_id' => $building->id,
            ]);

        $propertyAlina = Property::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'building_id' => $building->id,
                'unit_number' => $propertyAlinaPrototype->unit_number,
            ],
            [
                'name' => $propertyAlinaPrototype->name,
                'type' => $propertyAlinaPrototype->type,
                'floor_area_sqm' => $propertyAlinaPrototype->floor_area_sqm,
            ],
        );

        $propertyMariusPrototype = Property::factory()
            ->unit('Apartment 102', '102', PropertyType::APARTMENT, 63.10)
            ->make([
                'organization_id' => $organization->id,
                'building_id' => $building->id,
            ]);

        $propertyMarius = Property::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'building_id' => $building->id,
                'unit_number' => $propertyMariusPrototype->unit_number,
            ],
            [
                'name' => $propertyMariusPrototype->name,
                'type' => $propertyMariusPrototype->type,
                'floor_area_sqm' => $propertyMariusPrototype->floor_area_sqm,
            ],
        );

        $this->assignProperty($organization, $propertyAlina, $tenantAlina);
        $this->assignProperty($organization, $propertyMarius, $tenantMarius);

        $this->seedTenantPortfolioData($organization, $manager, $propertyAlina, $tenantAlina, 1);
        $this->seedTenantPortfolioData($organization, $manager, $propertyMarius, $tenantMarius, 2);

        if ($this->command === null) {
            return;
        }

        $this->command->table(
            ['Role', 'Username', 'Password'],
            [
                [$superadmin->role->label(), $superadmin->email, self::DEFAULT_PASSWORD],
                [$admin->role->label(), $admin->email, self::DEFAULT_PASSWORD],
                [$manager->role->label(), $manager->email, self::DEFAULT_PASSWORD],
                [$tenantAlina->role->label(), $tenantAlina->email, self::DEFAULT_PASSWORD],
                [$tenantMarius->role->label(), $tenantMarius->email, self::DEFAULT_PASSWORD],
            ],
        );
    }

    private function upsertUser(string $name, string $email, UserRole $role, ?int $organizationId): User
    {
        $userPrototype = match ($role) {
            UserRole::SUPERADMIN => User::factory()->superadmin(),
            UserRole::ADMIN => User::factory()->admin(),
            UserRole::MANAGER => User::factory()->manager(),
            UserRole::TENANT => User::factory()->tenant(),
        };

        $user = $userPrototype
            ->withLocale('en')
            ->make([
                'name' => $name,
                'email' => $email,
                'organization_id' => $organizationId,
            ]);

        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $user->name,
                'password' => $user->password,
                'role' => $user->role,
                'status' => UserStatus::ACTIVE->value,
                'locale' => $user->locale,
                'organization_id' => $organizationId,
                'email_verified_at' => $user->email_verified_at,
            ],
        );
    }

    private function assignProperty(Organization $organization, Property $property, User $tenant): void
    {
        PropertyAssignment::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'property_id' => $property->id,
                'tenant_user_id' => $tenant->id,
            ],
            [
                'unit_area_sqm' => $property->floor_area_sqm,
                'assigned_at' => now()->subMonth(),
                'unassigned_at' => null,
            ],
        );
    }

    private function seedTenantPortfolioData(
        Organization $organization,
        User $manager,
        Property $property,
        User $tenant,
        int $seedIndex,
    ): void {
        Lease::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'property_id' => $property->id,
                'tenant_user_id' => $tenant->id,
            ],
            [
                'start_date' => now()->subMonths(9)->toDateString(),
                'end_date' => now()->addMonths(15)->toDateString(),
                'monthly_rent' => 620 + ($seedIndex * 40),
                'deposit' => 900 + ($seedIndex * 120),
                'is_active' => true,
            ],
        );

        $meters = collect(range(1, 2))->map(function (int $meterIndex) use ($organization, $property, $seedIndex): Meter {
            $meterType = $meterIndex === 1 ? MeterType::ELECTRICITY : MeterType::WATER;
            $meterPrototype = Meter::factory()
                ->identified(
                    identifier: sprintf('LOGIN-%02d-%02d', $seedIndex, $meterIndex),
                    type: $meterType,
                    name: sprintf('Demo %s Meter', $meterType->label()),
                    installedAt: now()->subYear()->toDateString(),
                )
                ->make([
                    'organization_id' => $organization->id,
                    'property_id' => $property->id,
                ]);

            return Meter::query()->updateOrCreate(
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
        });

        $meters->each(function (Meter $meter, int $meterOffset) use ($organization, $property, $tenant, $manager, $seedIndex): void {
            collect(range(0, 5))->each(function (int $readingIndex) use ($meter, $meterOffset, $organization, $property, $tenant, $manager, $seedIndex): void {
                $readingDate = now()
                    ->startOfMonth()
                    ->subMonths(5 - $readingIndex)
                    ->addDays($seedIndex);

                MeterReading::query()->updateOrCreate(
                    [
                        'meter_id' => $meter->id,
                        'reading_date' => $readingDate->toDateString(),
                    ],
                    [
                        'organization_id' => $organization->id,
                        'property_id' => $property->id,
                        'submitted_by_user_id' => $readingIndex < 2 ? $manager->id : $tenant->id,
                        'reading_value' => 120 + ($seedIndex * 30) + ($meterOffset * 20) + (($readingIndex + 1) * 4.25),
                        'validation_status' => MeterReadingValidationStatus::VALID,
                        'submission_method' => $readingIndex < 2
                            ? MeterReadingSubmissionMethod::ADMIN_MANUAL
                            : MeterReadingSubmissionMethod::TENANT_PORTAL,
                        'notes' => null,
                    ],
                );
            });
        });

        collect(range(0, 1))->each(function (int $invoiceIndex) use ($organization, $property, $tenant, $seedIndex): void {
            $periodStart = now()->startOfMonth()->subMonths(1 - $invoiceIndex);
            $periodEnd = $periodStart->copy()->endOfMonth();
            $status = $invoiceIndex === 0 ? InvoiceStatus::PAID : InvoiceStatus::OVERDUE;
            $totalAmount = 95 + ($seedIndex * 15) + ($invoiceIndex * 10);

            $invoice = Invoice::query()->updateOrCreate(
                [
                    'invoice_number' => sprintf('LOGIN-INV-%02d-%02d', $seedIndex, $invoiceIndex + 1),
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
                    'amount_paid' => $status === InvoiceStatus::PAID ? $totalAmount : 0,
                    'due_date' => $periodEnd->copy()->addDays(14)->toDateString(),
                    'finalized_at' => $periodEnd->copy()->addDay(),
                    'paid_at' => $status === InvoiceStatus::PAID ? $periodEnd->copy()->addDays(4) : null,
                    'document_path' => null,
                    'notes' => sprintf('Seeded login demo invoice %d', $invoiceIndex + 1),
                ],
            );

            $invoiceItems = [
                ['description' => 'Electricity charge', 'quantity' => 155.00, 'unit' => 'kWh', 'unit_price' => 0.1900],
                ['description' => 'Water supply', 'quantity' => 8.20, 'unit' => 'm3', 'unit_price' => 2.1000],
                ['description' => 'Shared services fee', 'quantity' => 1.00, 'unit' => 'month', 'unit_price' => 22.5000],
            ];

            foreach ($invoiceItems as $item) {
                InvoiceItem::query()->updateOrCreate(
                    [
                        'invoice_id' => $invoice->id,
                        'description' => $item['description'],
                    ],
                    [
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'],
                        'unit_price' => $item['unit_price'],
                        'total' => round($item['quantity'] * $item['unit_price'], 2),
                        'meter_reading_snapshot' => [
                            'billing_period_start' => $invoice->billing_period_start?->toDateString(),
                            'billing_period_end' => $invoice->billing_period_end?->toDateString(),
                        ],
                    ],
                );
            }
        });
    }

    private function syncMembership(Organization $organization, User $user, UserRole $role): void
    {
        OrganizationUser::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            [
                'role' => $role->value,
                'permissions' => null,
                'joined_at' => now(),
                'left_at' => null,
                'is_active' => true,
                'invited_by' => $organization->owner_user_id,
            ],
        );
    }
}
