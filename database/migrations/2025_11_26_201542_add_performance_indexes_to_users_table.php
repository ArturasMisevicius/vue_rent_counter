<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds performance indexes for UserResource queries:
     * - tenant_id: For tenant scoping (most common filter)
     * - role: For role filtering in admin panel
     * - is_active: For active/inactive filtering
     * - Composite (tenant_id, role): For combined tenant + role queries
     * - Composite (tenant_id, is_active): For combined tenant + active status queries
     * 
     * Note: Uses raw SQL to check for existing indexes (Laravel 12 compatible)
     */
    public function up(): void
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        // Get existing indexes
        if ($driver === 'sqlite') {
            $indexes = $connection->select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='users'");
            $existingIndexes = collect($indexes)->pluck('name')->toArray();
        } else {
            $indexes = $connection->select("SHOW INDEX FROM users");
            $existingIndexes = collect($indexes)->pluck('Key_name')->unique()->toArray();
        }
        
        Schema::table('users', function (Blueprint $table) use ($existingIndexes) {
            // Single column indexes
            if (!in_array('users_tenant_id_index', $existingIndexes)) {
                $table->index('tenant_id', 'users_tenant_id_index');
            }
            
            if (!in_array('users_role_index', $existingIndexes)) {
                $table->index('role', 'users_role_index');
            }
            
            if (!in_array('users_is_active_index', $existingIndexes)) {
                $table->index('is_active', 'users_is_active_index');
            }
            
            // Composite indexes for common query patterns
            if (!in_array('users_tenant_id_role_index', $existingIndexes)) {
                $table->index(['tenant_id', 'role'], 'users_tenant_id_role_index');
            }
            
            if (!in_array('users_tenant_id_is_active_index', $existingIndexes)) {
                $table->index(['tenant_id', 'is_active'], 'users_tenant_id_is_active_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        // Get existing indexes
        if ($driver === 'sqlite') {
            $indexes = $connection->select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='users'");
            $existingIndexes = collect($indexes)->pluck('name')->toArray();
        } else {
            $indexes = $connection->select("SHOW INDEX FROM users");
            $existingIndexes = collect($indexes)->pluck('Key_name')->unique()->toArray();
        }
        
        Schema::table('users', function (Blueprint $table) use ($existingIndexes) {
            // Drop indexes in reverse order if they exist
            if (in_array('users_tenant_id_is_active_index', $existingIndexes)) {
                $table->dropIndex('users_tenant_id_is_active_index');
            }
            
            if (in_array('users_tenant_id_role_index', $existingIndexes)) {
                $table->dropIndex('users_tenant_id_role_index');
            }
            
            if (in_array('users_is_active_index', $existingIndexes)) {
                $table->dropIndex('users_is_active_index');
            }
            
            if (in_array('users_role_index', $existingIndexes)) {
                $table->dropIndex('users_role_index');
            }
            
            if (in_array('users_tenant_id_index', $existingIndexes)) {
                $table->dropIndex('users_tenant_id_index');
            }
        });
    }
};
