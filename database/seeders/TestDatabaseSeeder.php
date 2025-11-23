<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with test data.
     * 
     * This seeder orchestrates all test seeders in the correct order,
     * wraps operations in a transaction for rollback on failure,
     * and provides comprehensive error handling and logging.
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            Log::info('Starting test database seeding...');

            // 0. Seed languages and FAQ defaults early
            $this->call(LanguageSeeder::class);
            $this->call(FaqSeeder::class);

            // 1. Seed organizations, invitations, and activity logs
            $this->call(OrganizationSeeder::class);
            Log::info('✓ Organizations seeded');

            // 2. Seed providers first (no tenant dependency)
            $this->call(ProvidersSeeder::class);
            Log::info('✓ Providers seeded');

            // 3. Seed test buildings with realistic addresses
            $this->call(TestBuildingsSeeder::class);
            Log::info('✓ Test buildings seeded');

            // 4. Seed test properties (apartments and houses)
            $this->call(TestPropertiesSeeder::class);
            Log::info('✓ Test properties seeded');

            // 5. Seed users (superadmin, admins, managers, tenants)
            // Must be called after properties are created for tenant user assignments
            $this->call(UsersSeeder::class);
            Log::info('✓ Users seeded');

            // 6. Seed test tenants (renters) linked to properties
            $this->call(TestTenantsSeeder::class);
            Log::info('✓ Test tenants seeded');

            // 7. Seed test meters for each property
            $this->call(TestMetersSeeder::class);
            Log::info('✓ Test meters seeded');

            // 8. Seed test meter readings (3+ months history)
            $this->call(TestMeterReadingsSeeder::class);
            Log::info('✓ Test meter readings seeded');

            // 9. Seed test tariffs for all providers
            $this->call(TestTariffsSeeder::class);
            Log::info('✓ Test tariffs seeded');

            // 10. Seed test invoices in different states
            $this->call(TestInvoicesSeeder::class);
            Log::info('✓ Test invoices seeded');

            DB::commit();

            Log::info('Test database seeding completed successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Test database seeding failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->command->error('Test database seeding failed: ' . $e->getMessage());
            $this->command->error('All changes have been rolled back.');
            
            throw $e;
        }
    }
}
