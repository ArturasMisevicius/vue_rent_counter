<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SubscriptionPlan;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Smoke Test Data Seeder
 * 
 * Creates clean test data for manual testing of the Superadmin Panel:
 * - Test Organization with domain "test.tenanto.lt"
 * - Admin user assigned to this organization
 * 
 * Login Credentials:
 * - Email: smoketest.admin@tenanto.lt
 * - Password: SmokeTest2024!
 * 
 * Admin Panel URL: /admin
 */
final class SmokeTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Smoke Test Data...');

        // Create Test Organization
        $organization = Organization::updateOrCreate(
            ['domain' => 'test.tenanto.lt'],
            [
                'name' => 'Test Organization',
                'slug' => 'test-organization',
                'email' => 'info@test.tenanto.lt',
                'phone' => '+370 600 00000',
                'is_active' => true,
                'plan' => SubscriptionPlan::PROFESSIONAL,
                'subscription_plan' => SubscriptionPlan::PROFESSIONAL,
                'max_properties' => 100,
                'max_users' => 25,
                'max_storage_gb' => 10,
                'max_api_calls_per_month' => 10000,
                'current_users' => 1,
                'current_storage_gb' => 0,
                'current_api_calls' => 0,
                'subscription_ends_at' => now()->addYear(),
                'timezone' => 'Europe/Vilnius',
                'locale' => 'lt',
                'currency' => 'EUR',
                'settings' => [
                    'notifications_enabled' => true,
                    'auto_billing' => false,
                ],
                'features' => [
                    'meter_readings' => true,
                    'invoicing' => true,
                    'reports' => true,
                ],
            ]
        );

        $this->command->info("✓ Organization created: {$organization->name} (ID: {$organization->id})");

        // Create Admin User for the organization
        $adminUser = User::updateOrCreate(
            ['email' => 'smoketest.admin@tenanto.lt'],
            [
                'name' => 'Smoke Test Admin',
                'password' => Hash::make('SmokeTest2024!'),
                'role' => UserRole::ADMIN,
                'tenant_id' => $organization->id,
                'is_active' => true,
                'organization_name' => $organization->name,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("✓ Admin user created: {$adminUser->name} (ID: {$adminUser->id})");

        // Output summary
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('                    SMOKE TEST DATA CREATED                     ');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->newLine();
        
        $this->command->table(
            ['Item', 'Value'],
            [
                ['Organization Name', $organization->name],
                ['Organization ID', $organization->id],
                ['Organization Domain', $organization->domain],
                ['Organization Plan', $organization->plan->value ?? 'professional'],
                ['Organization Status', $organization->is_active ? 'Active' : 'Inactive'],
            ]
        );

        $this->command->newLine();
        $this->command->info('Admin User Credentials:');
        $this->command->table(
            ['Field', 'Value'],
            [
                ['Name', $adminUser->name],
                ['Email', 'smoketest.admin@tenanto.lt'],
                ['Password', 'SmokeTest2024!'],
                ['Role', $adminUser->role->value],
                ['Tenant ID', $adminUser->tenant_id],
            ]
        );

        $this->command->newLine();
        $this->command->info('Access URLs:');
        $this->command->table(
            ['Panel', 'URL'],
            [
                ['Admin Panel', config('app.url') . '/admin'],
                ['Login Page', config('app.url') . '/admin/login'],
            ]
        );

        $this->command->newLine();
        $this->command->info('To verify in Superadmin Panel:');
        $this->command->line('1. Login as Superadmin');
        $this->command->line('2. Navigate to System Management → Organizations');
        $this->command->line('3. Search for "Test Organization" or domain "test.tenanto.lt"');
        $this->command->line('4. Verify the organization details match the above');
        $this->command->newLine();
    }
}
