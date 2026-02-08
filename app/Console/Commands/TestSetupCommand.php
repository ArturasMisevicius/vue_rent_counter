<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Set up test environment with sample data for manual testing.
 * 
 * This command creates a complete test environment with known credentials,
 * realistic test data, and comprehensive coverage of all system features.
 * Use --fresh option to reset the database before seeding.
 */
class TestSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:setup 
                            {--fresh : Drop all tables and recreate before seeding}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up test environment with sample data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Setting up test environment...');
        $this->newLine();

        // Run migrate:fresh if --fresh option is provided
        if ($this->option('fresh')) {
            $this->warn('Running migrate:fresh - this will drop all tables!');
            
            if (!$this->option('force') && !$this->confirm('Are you sure you want to continue?', true)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
            
            $this->newLine();
            $this->info('Running migrations...');
            $this->call('migrate:fresh');
            $this->newLine();
        } else {
            // Check if test users already exist
            $existingUsers = User::whereIn('email', [
                'admin@test.com',
                'manager@test.com',
                'manager2@test.com',
                'tenant@test.com',
                'tenant2@test.com',
                'tenant3@test.com',
            ])->count();

            if ($existingUsers > 0) {
                $this->warn("Test data already exists ({$existingUsers} test users found).");
                $this->warn('Use --fresh option to reset the database before seeding.');
                $this->newLine();
                
                if (!$this->option('force') && !$this->confirm('Do you want to continue anyway? (This may cause errors)', false)) {
                    $this->info('Operation cancelled.');
                    $this->info('Run with --fresh option to reset: php artisan test:setup --fresh');
                    return self::SUCCESS;
                }
                $this->newLine();
            }
        }

        // Seed test data
        $this->info('Seeding test data...');
        
        try {
            $this->call('db:seed', ['--class' => 'Database\\Seeders\\TestDatabaseSeeder']);
            $this->newLine();
        } catch (\Exception $e) {
            $this->error('Failed to seed test data: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Tip: Use --fresh option to reset the database: php artisan test:setup --fresh');
            return self::FAILURE;
        }

        // Display test credentials
        $this->displayTestCredentials();
        
        // Display data summary
        $this->displayDataSummary();

        $this->newLine();
        $this->info('✓ Test environment setup complete!');
        $this->info('You can now log in with any of the test users listed above.');

        return self::SUCCESS;
    }

    /**
     * Display test user credentials in a table format.
     */
    private function displayTestCredentials(): void
    {
        $this->info('Test User Credentials:');
        $this->newLine();

        $testUsers = [
            ['Admin', 'admin@test.com', 'password', '1', '/admin/dashboard'],
            ['Manager', 'manager@test.com', 'password', '1', '/manager/dashboard'],
            ['Manager', 'manager2@test.com', 'password', '2', '/manager/dashboard'],
            ['Tenant', 'tenant@test.com', 'password', '1', '/tenant/dashboard'],
            ['Tenant', 'tenant2@test.com', 'password', '1', '/tenant/dashboard'],
            ['Tenant', 'tenant3@test.com', 'password', '2', '/tenant/dashboard'],
        ];

        $this->table(
            ['Role', 'Email', 'Password', 'Tenant ID', 'Dashboard'],
            $testUsers
        );

        $this->newLine();
    }

    /**
     * Display summary of created test data.
     */
    private function displayDataSummary(): void
    {
        $this->info('Test Data Summary:');
        $this->newLine();

        // Count data without tenant scope interference
        $usersCount = User::withoutGlobalScopes()->count();
        $buildingsCount = Building::withoutGlobalScopes()->count();
        $propertiesCount = Property::withoutGlobalScopes()->count();
        $tenantsCount = Tenant::withoutGlobalScopes()->count();
        $metersCount = Meter::withoutGlobalScopes()->count();
        $readingsCount = MeterReading::withoutGlobalScopes()->count();

        $summary = [
            ['Users', $usersCount],
            ['Buildings', $buildingsCount],
            ['Properties', $propertiesCount],
            ['Tenants (Renters)', $tenantsCount],
            ['Meters', $metersCount],
            ['Meter Readings', $readingsCount],
        ];

        $this->table(
            ['Resource', 'Count'],
            $summary
        );

        $this->newLine();
        
        // Display breakdown by tenant
        $this->info('Data by Tenant:');
        $this->newLine();

        $tenant1Buildings = Building::withoutGlobalScopes()->where('tenant_id', 1)->count();
        $tenant1Properties = Property::withoutGlobalScopes()->where('tenant_id', 1)->count();
        $tenant1Meters = Meter::withoutGlobalScopes()->where('tenant_id', 1)->count();

        $tenant2Buildings = Building::withoutGlobalScopes()->where('tenant_id', 2)->count();
        $tenant2Properties = Property::withoutGlobalScopes()->where('tenant_id', 2)->count();
        $tenant2Meters = Meter::withoutGlobalScopes()->where('tenant_id', 2)->count();

        $tenantBreakdown = [
            ['Tenant 1', $tenant1Buildings, $tenant1Properties, $tenant1Meters],
            ['Tenant 2', $tenant2Buildings, $tenant2Properties, $tenant2Meters],
        ];

        $this->table(
            ['Tenant', 'Buildings', 'Properties', 'Meters'],
            $tenantBreakdown
        );
    }
}

