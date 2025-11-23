<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Default password for all seeded users.
     */
    private const DEFAULT_PASSWORD = 'password';

    /**
     * Seed all users: superadmin, admins with subscriptions, managers, and tenants.
     * 
     * This seeder consolidates all user creation logic from TestUsersSeeder
     * and HierarchicalUsersSeeder into a single, optimized seeder.
     */
    public function run(): void
    {
        $hashedPassword = Hash::make(self::DEFAULT_PASSWORD);

        // 1. Create superadmin
        $superadmin = $this->createSuperadmin($hashedPassword);

        // 2. Create admins with subscriptions
        $admins = $this->createAdmins($hashedPassword);

        // 3. Create managers (legacy role)
        $this->createManagers($hashedPassword);

        // 4. Create tenant users (requires properties to exist)
        $this->createTenantUsers($admins, $hashedPassword);
    }

    /**
     * Create superadmin user.
     */
    private function createSuperadmin(string $password): User
    {
        return User::factory()
            ->superadmin()
            ->create([
                'name' => 'System Superadmin',
                'email' => 'superadmin@example.com',
                'password' => $password,
            ]);
    }

    /**
     * Create admin users with subscriptions.
     * 
     * @return array<int, User> Array of primary admin users keyed by tenant_id (first admin per tenant)
     */
    private function createAdmins(string $password): array
    {
        $primaryAdmins = [];

        // Admin for tenant 1 - Test Organization 1 (from TestUsersSeeder)
        $primaryAdmins[1] = $this->createAdminWithSubscription(
            tenantId: 1,
            name: 'Test Admin',
            email: 'admin@test.com',
            organizationName: 'Test Organization 1',
            planType: 'professional',
            password: $password
        );

        // Admin for tenant 2 - Test Organization 2 (from TestUsersSeeder)
        $primaryAdmins[2] = $this->createAdminWithSubscription(
            tenantId: 2,
            name: 'Test Manager 2',
            email: 'manager2@test.com',
            organizationName: 'Test Organization 2',
            planType: 'basic',
            password: $password,
            expiresInMonths: 6
        );

        // Additional admin for tenant 1 - Vilnius Properties Ltd (from HierarchicalUsersSeeder)
        // Use this as primary for hierarchical tenant users
        $hierarchicalAdmin1 = $this->createAdminWithSubscription(
            tenantId: 1,
            name: 'Vilnius Properties Ltd',
            email: 'admin1@example.com',
            organizationName: 'Vilnius Properties Ltd',
            planType: 'professional',
            password: $password
        );

        // Additional admin for tenant 2 - Baltic Real Estate (from HierarchicalUsersSeeder)
        // Use this as primary for hierarchical tenant users
        $hierarchicalAdmin2 = $this->createAdminWithSubscription(
            tenantId: 2,
            name: 'Baltic Real Estate',
            email: 'admin2@example.com',
            organizationName: 'Baltic Real Estate',
            planType: 'basic',
            password: $password,
            expiresInMonths: 6
        );

        // Admin for tenant 3 - Old Town Management (expired subscription)
        $primaryAdmins[3] = $this->createAdminWithSubscription(
            tenantId: 3,
            name: 'Old Town Management',
            email: 'admin3@example.com',
            organizationName: 'Old Town Management',
            planType: 'basic',
            password: $password,
            status: 'expired',
            startsAt: now()->subYear(),
            expiresAt: now()->subDays(10)
        );

        // Store hierarchical admins for tenant user creation
        $primaryAdmins['hierarchical_1'] = $hierarchicalAdmin1;
        $primaryAdmins['hierarchical_2'] = $hierarchicalAdmin2;

        return $primaryAdmins;
    }

    /**
     * Create an admin user with a subscription.
     */
    private function createAdminWithSubscription(
        int $tenantId,
        string $name,
        string $email,
        string $organizationName,
        string $planType,
        string $password,
        string $status = 'active',
        ?\DateTimeInterface $startsAt = null,
        ?\DateTimeInterface $expiresAt = null,
        int $expiresInMonths = 12
    ): User {
        $admin = User::factory()
            ->admin($tenantId)
            ->create([
                'name' => $name,
                'email' => $email,
                'organization_name' => $organizationName,
                'password' => $password,
            ]);

        Subscription::factory()
            ->for($admin)
            ->create([
                'plan_type' => $planType,
                'status' => $status,
                'starts_at' => $startsAt ?? now(),
                'expires_at' => $expiresAt ?? now()->addMonths($expiresInMonths),
                'max_properties' => $planType === 'professional' ? 50 : 10,
                'max_tenants' => $planType === 'professional' ? 200 : 50,
            ]);

        return $admin;
    }

    /**
     * Create manager users (legacy role).
     */
    private function createManagers(string $password): void
    {
        User::factory()
            ->manager(1)
            ->create([
                'name' => 'Test Manager',
                'email' => 'manager@test.com',
                'password' => $password,
            ]);
    }

    /**
     * Create tenant users with property assignments.
     */
    private function createTenantUsers(array $admins, string $password): void
    {
        // Get valid tenant IDs (exclude hierarchical keys)
        $tenantIds = array_filter(array_keys($admins), fn($key) => is_int($key));
        
        // Fetch all properties grouped by tenant_id for efficient access
        $propertiesByTenant = Property::whereIn('tenant_id', $tenantIds)
            ->get()
            ->groupBy('tenant_id');

        // Tenant users for tenant_id 1 (from TestUsersSeeder)
        if (isset($propertiesByTenant[1]) && $propertiesByTenant[1]->count() >= 2 && isset($admins[1])) {
            $properties1 = $propertiesByTenant[1];
            
            User::factory()
                ->tenant(1, $properties1[0]->id, $admins[1]->id)
                ->create([
                    'name' => 'Test Tenant',
                    'email' => 'tenant@test.com',
                    'password' => $password,
                ]);

            User::factory()
                ->tenant(1, $properties1[1]->id, $admins[1]->id)
                ->create([
                    'name' => 'Test Tenant 2',
                    'email' => 'tenant2@test.com',
                    'password' => $password,
                ]);
        }

        // Tenant users for tenant_id 2 (from TestUsersSeeder)
        if (isset($propertiesByTenant[2]) && $propertiesByTenant[2]->count() >= 1 && isset($admins[2])) {
            $properties2 = $propertiesByTenant[2];
            
            User::factory()
                ->tenant(2, $properties2[0]->id, $admins[2]->id)
                ->create([
                    'name' => 'Test Tenant 3',
                    'email' => 'tenant3@test.com',
                    'password' => $password,
                ]);
        }

        // Tenant users for tenant_id 1 (from HierarchicalUsersSeeder)
        // Use hierarchical admin for these
        if (isset($propertiesByTenant[1]) && $propertiesByTenant[1]->count() >= 3 && isset($admins['hierarchical_1'])) {
            $properties1 = $propertiesByTenant[1];
            $hierarchicalAdmin1 = $admins['hierarchical_1'];
            
            User::factory()
                ->tenant(1, $properties1[0]->id, $hierarchicalAdmin1->id)
                ->create([
                    'name' => 'Jonas Petraitis',
                    'email' => 'jonas.petraitis@example.com',
                    'password' => $password,
                ]);

            User::factory()
                ->tenant(1, $properties1[1]->id, $hierarchicalAdmin1->id)
                ->create([
                    'name' => 'Ona Kazlauskienė',
                    'email' => 'ona.kazlauskiene@example.com',
                    'password' => $password,
                ]);

            User::factory()
                ->tenant(1, $properties1[2]->id, $hierarchicalAdmin1->id)
                ->create([
                    'name' => 'Petras Jonaitis',
                    'email' => 'petras.jonaitis@example.com',
                    'password' => $password,
                ]);

            // Inactive tenant
            User::factory()
                ->tenant(1, $properties1[0]->id, $hierarchicalAdmin1->id)
                ->inactive()
                ->create([
                    'name' => 'Deactivated Tenant',
                    'email' => 'deactivated@example.com',
                    'password' => $password,
                ]);
        }

        // Tenant users for tenant_id 2 (from HierarchicalUsersSeeder)
        // Use hierarchical admin for these
        if (isset($propertiesByTenant[2]) && $propertiesByTenant[2]->count() >= 2 && isset($admins['hierarchical_2'])) {
            $properties2 = $propertiesByTenant[2];
            $hierarchicalAdmin2 = $admins['hierarchical_2'];
            
            User::factory()
                ->tenant(2, $properties2[0]->id, $hierarchicalAdmin2->id)
                ->create([
                    'name' => 'Marija Vasiliauskaite',
                    'email' => 'marija.vasiliauskaite@example.com',
                    'password' => $password,
                ]);

            User::factory()
                ->tenant(2, $properties2[1]->id, $hierarchicalAdmin2->id)
                ->create([
                    'name' => 'Andrius Butkus',
                    'email' => 'andrius.butkus@example.com',
                    'password' => $password,
                ]);
        }
    }
}

