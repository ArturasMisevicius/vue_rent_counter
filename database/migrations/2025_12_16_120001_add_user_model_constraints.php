<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add User Model Data Integrity Constraints
 * 
 * Adds check constraints and data validation for multi-tenant architecture.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support adding CHECK constraints with ALTER TABLE
        // We'll skip constraints for SQLite and only add them for MySQL/PostgreSQL
        $driver = DB::getDriverName();
        
        if ($driver !== 'sqlite') {
            // Add check constraints for data integrity (MySQL/PostgreSQL only)
            DB::statement("
                ALTER TABLE users 
                ADD CONSTRAINT users_role_check 
                CHECK (role IN ('superadmin', 'admin', 'manager', 'tenant'))
            ");

            DB::statement("
                ALTER TABLE users 
                ADD CONSTRAINT users_tenant_hierarchy_check 
                CHECK (
                    (role = 'superadmin' AND tenant_id IS NULL) OR
                    (role IN ('admin', 'manager') AND tenant_id IS NOT NULL) OR
                    (role = 'tenant' AND tenant_id IS NOT NULL AND property_id IS NOT NULL)
                )
            ");

            DB::statement("
                ALTER TABLE users 
                ADD CONSTRAINT users_parent_relationship_check 
                CHECK (
                    (role = 'tenant' AND parent_user_id IS NOT NULL) OR
                    (role IN ('superadmin', 'admin', 'manager') AND parent_user_id IS NULL)
                )
            ");
        }

        // Add soft delete support if not exists
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
                $table->index(['deleted_at'], 'users_deleted_at_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_tenant_hierarchy_check");
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_parent_relationship_check");
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropIndex('users_deleted_at_idx');
                $table->dropSoftDeletes();
            }
        });
    }
};