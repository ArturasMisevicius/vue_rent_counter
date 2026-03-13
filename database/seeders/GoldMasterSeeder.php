<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Lease;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Gold Master Seeder - Creates the exact hierarchy for testing the Truth-but-Verify workflow
 * 
 * Creates:
 * - Superadmin (superadmin@example.com)
 * - Admin (admin@example.com) who owns an Organization
 * - Manager (manager@example.com) in same Organization  
 * - Infrastructure: 2 Buildings, 5 Properties total, and 1 active Lease with Tenant (tenant@example.com)
 * - Utilities: Electricity and Water meters for leased Property
 * - All passwords: 'password'
 */
class GoldMasterSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password';
    private const TENANT_ID = 1; // Organization ID for the Gold Master setup

    /**
     * Seed the Gold Master hierarchy for testing.
     */
    public function run(): void
    {
        Log::info('Starting Gold Master seeder...');

        $hashedPassword = Hash::make(self::DEFAULT_PASSWORD);

        // 1. Create Superadmin
        $superadmin = $this->createSuperadmin($hashedPassword);
        Log::info('✓ Superadmin created: superadmin@example.com');

        // 2. Create Organization
        $organization = $this->createOrganization();
        Log::info('✓ Organization created: Gold Master Properties Ltd');

        // 3. Create Admin who owns the Organization
        $admin = $this->createAdmin($hashedPassword, $organization);
        Log::info('✓ Admin created: admin@example.com');

        // 4. Create Manager in the same Organization
        $manager = $this->createManager($hashedPassword, $admin);
        Log::info('✓ Manager created: manager@example.com');

        // 5. Create Infrastructure: 2 Buildings, 5 Properties
        [$building1, $building2] = $this->createBuildings();
        $properties = $this->createProperties($building1, $building2);
        Log::info('✓ Infrastructure created: 2 Buildings, 5 Properties');

        // 6. Create 1 active Lease with Tenant
        $leasedProperty = $properties[0]; // Use first property for the lease
        $tenant = $this->createTenantWithLease($hashedPassword, $leasedProperty, $admin);
        Log::info('✓ Tenant with active lease created: tenant@example.com');

        // 7. Add Electricity and Water meters to the leased Property
        $this->createMetersForProperty($leasedProperty);
        Log::info('✓ Electricity and Water meters added to leased property');

        Log::info('Gold Master seeder completed successfully!');
        Log::info('You can now log in as:');
        Log::info('- Superadmin: superadmin@example.com / password');
        Log::info('- Admin: admin@example.com / password');
        Log::info('- Manager: manager@example.com / password');
        Log::info('- Tenant: tenant@example.com / password');
    }

    /**
     * Create superadmin user.
     */
    private function createSuperadmin(string $password): User
    {
        return User::create([
            'name' => 'System Superadmin',
            'email' => 'superadmin@example.com',
            'password' => $password,
            'role' => UserRole::SUPERADMIN,
            'is_super_admin' => true,
            'is_active' => true,
            'tenant_id' => null, // Superadmins have no tenant scope
            'property_id' => null,
            'parent_user_id' => null,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Create organization for the Gold Master setup.
     */
    private function createOrganization(): Organization
    {
        return Organization::create([
            'id' => self::TENANT_ID,
            'name' => 'Gold Master Properties Ltd',
            'domain' => 'goldmaster.example.com',
            'email' => 'admin@example.com',
            'plan' => 'professional',
            'max_properties' => 100,
            'max_users' => 50,
            'is_active' => true,
        ]);
    }

    /**
     * Create admin user who owns the organization.
     */
    private function createAdmin(string $password, Organization $organization): User
    {
        $admin = User::create([
            'name' => 'Gold Master Admin',
            'email' => 'admin@example.com',
            'password' => $password,
            'role' => UserRole::ADMIN,
            'is_super_admin' => false,
            'is_active' => true,
            'tenant_id' => self::TENANT_ID,
            'property_id' => null,
            'parent_user_id' => null,
            'organization_name' => $organization->name,
            'email_verified_at' => now(),
        ]);

        // Create subscription for the admin
        Subscription::create([
            'user_id' => $admin->id,
            'plan_type' => SubscriptionPlanType::PROFESSIONAL->value,
            'status' => SubscriptionStatus::ACTIVE->value,
            'starts_at' => now(),
            'expires_at' => now()->addMonths(12),
            'max_properties' => 100,
            'max_tenants' => 200,
        ]);

        return $admin;
    }

    /**
     * Create manager user in the same organization.
     */
    private function createManager(string $password, User $admin): User
    {
        return User::create([
            'name' => 'Gold Master Manager',
            'email' => 'manager@example.com',
            'password' => $password,
            'role' => UserRole::MANAGER,
            'is_super_admin' => false,
            'is_active' => true,
            'tenant_id' => self::TENANT_ID,
            'property_id' => null,
            'parent_user_id' => $admin->id,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Create 2 buildings for the Gold Master setup.
     */
    private function createBuildings(): array
    {
        $building1 = Building::create([
            'name' => 'Gedimino Residence',
            'address' => 'Gedimino pr. 25, Vilnius',
            'total_apartments' => 3,
            'tenant_id' => self::TENANT_ID,
        ]);

        $building2 = Building::create([
            'name' => 'Konstitucijos Tower',
            'address' => 'Konstitucijos pr. 12, Vilnius',
            'total_apartments' => 2,
            'tenant_id' => self::TENANT_ID,
        ]);

        return [$building1, $building2];
    }

    /**
     * Create 5 properties total (3 in building1, 2 in building2).
     */
    private function createProperties(Building $building1, Building $building2): array
    {
        $properties = [];

        // 3 apartments in building 1
        for ($i = 1; $i <= 3; $i++) {
            $properties[] = Property::create([
                'address' => "{$building1->address}, Apt {$i}",
                'type' => PropertyType::APARTMENT,
                'area_sqm' => 65 + ($i * 5), // 70, 75, 80 sqm
                'unit_number' => "Apt {$i}",
                'building_id' => $building1->id,
                'tenant_id' => self::TENANT_ID,
            ]);
        }

        // 2 apartments in building 2
        for ($i = 1; $i <= 2; $i++) {
            $properties[] = Property::create([
                'address' => "{$building2->address}, Apt {$i}",
                'type' => PropertyType::APARTMENT,
                'area_sqm' => 55 + ($i * 10), // 65, 75 sqm
                'unit_number' => "Apt {$i}",
                'building_id' => $building2->id,
                'tenant_id' => self::TENANT_ID,
            ]);
        }

        return $properties;
    }

    /**
     * Create tenant user with active lease.
     */
    private function createTenantWithLease(string $password, Property $property, User $admin): User
    {
        // Create tenant user
        $tenant = User::create([
            'name' => 'Gold Master Tenant',
            'email' => 'tenant@example.com',
            'password' => $password,
            'role' => UserRole::TENANT,
            'is_super_admin' => false,
            'is_active' => true,
            'tenant_id' => self::TENANT_ID,
            'property_id' => $property->id,
            'parent_user_id' => $admin->id,
            'email_verified_at' => now(),
        ]);

        // Create tenant record (renter)
        $tenantRecord = Tenant::create([
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone' => '+370 600 12345',
            'tenant_id' => self::TENANT_ID,
        ]);

        // Create active lease
        Lease::create([
            'property_id' => $property->id,
            'renter_id' => $tenantRecord->id,
            'start_date' => Carbon::now()->subMonths(6), // Started 6 months ago
            'end_date' => Carbon::now()->addMonths(6),   // Ends in 6 months
            'monthly_rent' => 800.00,
            'deposit' => 1600.00,
            'is_active' => true,
            'tenant_id' => self::TENANT_ID, // Organization scope
        ]);

        return $tenant;
    }

    /**
     * Create electricity and water meters for the leased property.
     */
    private function createMetersForProperty(Property $property): void
    {
        $installationDate = Carbon::now()->subYears(1);

        // Electricity meter (supports day/night zones)
        Meter::create([
            'serial_number' => 'EL-GM-001',
            'type' => MeterType::ELECTRICITY,
            'property_id' => $property->id,
            'installation_date' => $installationDate,
            'supports_zones' => true,
            'tenant_id' => self::TENANT_ID,
        ]);

        // Cold water meter
        Meter::create([
            'serial_number' => 'WC-GM-001',
            'type' => MeterType::WATER_COLD,
            'property_id' => $property->id,
            'installation_date' => $installationDate,
            'supports_zones' => false,
            'tenant_id' => self::TENANT_ID,
        ]);

        // Hot water meter
        Meter::create([
            'serial_number' => 'WH-GM-001',
            'type' => MeterType::WATER_HOT,
            'property_id' => $property->id,
            'installation_date' => $installationDate,
            'supports_zones' => false,
            'tenant_id' => self::TENANT_ID,
        ]);
    }
}