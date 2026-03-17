<?php

namespace Database\Seeders;

use App\Enums\OrganizationStatus;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Building;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

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

        $organization = Organization::query()->updateOrCreate(
            ['slug' => 'tenanto-demo-organization'],
            [
                'name' => 'Tenanto Demo Organization',
                'status' => OrganizationStatus::ACTIVE->value,
                'owner_user_id' => null,
            ],
        );

        OrganizationSetting::query()->updateOrCreate(
            ['organization_id' => $organization->id],
            [
                'billing_contact_name' => 'Tenanto Demo Team',
                'billing_contact_email' => 'billing@tenanto.test',
                'billing_contact_phone' => '+37060000000',
                'payment_instructions' => 'Pay by bank transfer or at the office.',
                'invoice_footer' => 'Thank you for paying on time.',
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

        $building = Building::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'Vilnius Central Residences',
            ],
            [
                'address_line_1' => 'Gedimino pr. 25',
                'address_line_2' => null,
                'city' => 'Vilnius',
                'postal_code' => '01103',
                'country_code' => 'LT',
            ],
        );

        $propertyAlina = Property::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'building_id' => $building->id,
                'unit_number' => '101',
            ],
            [
                'name' => 'Apartment 101',
                'type' => PropertyType::APARTMENT->value,
                'floor_area_sqm' => 58.40,
            ],
        );

        $propertyMarius = Property::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'building_id' => $building->id,
                'unit_number' => '102',
            ],
            [
                'name' => 'Apartment 102',
                'type' => PropertyType::APARTMENT->value,
                'floor_area_sqm' => 63.10,
            ],
        );

        $this->assignProperty($organization, $propertyAlina, $tenantAlina);
        $this->assignProperty($organization, $propertyMarius, $tenantMarius);

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
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => self::DEFAULT_PASSWORD,
                'role' => $role->value,
                'status' => UserStatus::ACTIVE->value,
                'locale' => 'en',
                'organization_id' => $organizationId,
                'email_verified_at' => now(),
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
}
