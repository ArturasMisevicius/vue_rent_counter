<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop incorrect tenant_id foreign keys from utility tables.
     *
     * tenant_id in this app represents the Organization scope (multi-tenancy),
     * and is not a foreign key to the renters stored in the `tenants` table.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // SQLite doesn't support dropping foreign keys without table rebuild.
        // New installs are corrected in the create-table migrations.
        if ($driver === 'sqlite') {
            return;
        }

        try {
            Schema::table('utility_services', function ($table): void {
                $table->dropForeign(['tenant_id']);
            });
        } catch (\Throwable) {
            // Ignore when the FK is already absent.
        }

        try {
            Schema::table('service_configurations', function ($table): void {
                $table->dropForeign(['tenant_id']);
            });
        } catch (\Throwable) {
            // Ignore when the FK is already absent.
        }
    }

    public function down(): void
    {
        // Intentionally no-op.
    }
};

