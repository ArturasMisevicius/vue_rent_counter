<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create basic permissions
        $permissions = [
            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // Property management
            'view_properties',
            'create_properties',
            'edit_properties',
            'delete_properties',
            
            // Building management
            'view_buildings',
            'create_buildings',
            'edit_buildings',
            'delete_buildings',
            
            // Meter management
            'view_meters',
            'create_meters',
            'edit_meters',
            'delete_meters',
            
            // Meter reading management
            'view_meter_readings',
            'create_meter_readings',
            'edit_meter_readings',
            'delete_meter_readings',
            'approve_meter_readings',
            
            // Invoice management
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'delete_invoices',
            'finalize_invoices',
            
            // Tariff management
            'view_tariffs',
            'create_tariffs',
            'edit_tariffs',
            'delete_tariffs',
            
            // Provider management
            'view_providers',
            'create_providers',
            'edit_providers',
            'delete_providers',
            
            // Subscription management
            'view_subscriptions',
            'create_subscriptions',
            'edit_subscriptions',
            'delete_subscriptions',
            
            // System administration
            'access_admin_panel',
            'manage_system_settings',
            'view_system_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Superadmin role - all permissions
        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superadmin->givePermissionTo(Permission::all());

        // Admin role - property management permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'access_admin_panel',
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'view_properties', 'create_properties', 'edit_properties', 'delete_properties',
            'view_buildings', 'create_buildings', 'edit_buildings', 'delete_buildings',
            'view_meters', 'create_meters', 'edit_meters', 'delete_meters',
            'view_meter_readings', 'create_meter_readings', 'edit_meter_readings', 'approve_meter_readings',
            'view_invoices', 'create_invoices', 'edit_invoices', 'finalize_invoices',
            'view_tariffs', 'create_tariffs', 'edit_tariffs', 'delete_tariffs',
            'view_providers', 'create_providers', 'edit_providers', 'delete_providers',
        ]);

        // Manager role - operational permissions
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'access_admin_panel',
            'view_users', 'create_users', 'edit_users',
            'view_properties', 'edit_properties',
            'view_buildings', 'edit_buildings',
            'view_meters', 'edit_meters',
            'view_meter_readings', 'create_meter_readings', 'edit_meter_readings', 'approve_meter_readings',
            'view_invoices', 'create_invoices', 'edit_invoices',
            'view_tariffs',
            'view_providers',
        ]);

        // Tenant role - limited permissions
        $tenant = Role::firstOrCreate(['name' => 'tenant']);
        $tenant->givePermissionTo([
            'view_meter_readings', 'create_meter_readings',
            'view_invoices',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}