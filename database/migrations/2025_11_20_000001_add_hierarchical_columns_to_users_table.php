<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make tenant_id nullable for superadmin role
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
            
            // Update role enum to include superadmin
            $table->enum('role', ['superadmin', 'admin', 'manager', 'tenant'])->default('tenant')->change();
            
            // Add property_id for tenant role
            $table->foreignId('property_id')
                  ->nullable()
                  ->after('tenant_id')
                  ->constrained('properties')
                  ->onDelete('set null');
            
            // Add parent_user_id for hierarchical relationships
            $table->foreignId('parent_user_id')
                  ->nullable()
                  ->after('property_id')
                  ->constrained('users')
                  ->onDelete('set null');
            
            // Add is_active for account activation status
            $table->boolean('is_active')
                  ->default(true)
                  ->after('role');
            
            // Add organization_name for admin role
            $table->string('organization_name')
                  ->nullable()
                  ->after('is_active');
            
            // Add indexes for performance (check if they exist first)
            if (!$this->indexExists('users', 'users_tenant_role_index')) {
                $table->index(['tenant_id', 'role'], 'users_tenant_role_index');
            }
            if (!$this->indexExists('users', 'users_parent_user_id_index')) {
                $table->index('parent_user_id', 'users_parent_user_id_index');
            }
            if (!$this->indexExists('users', 'users_property_id_index')) {
                $table->index('property_id', 'users_property_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first (only if they exist)
            $this->dropIndexIfExists($table, 'users_tenant_role_index');
            $this->dropIndexIfExists($table, 'users_parent_user_id_index');
            $this->dropIndexIfExists($table, 'users_property_id_index');
            
            // Drop foreign keys
            $table->dropForeign(['property_id']);
            $table->dropForeign(['parent_user_id']);
            
            // Drop columns
            $table->dropColumn(['property_id', 'parent_user_id', 'is_active', 'organization_name']);
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();
            
            if ($driver === 'sqlite') {
                $indexes = $connection->select("SELECT name FROM sqlite_master WHERE type='index' AND name=?", [$indexName]);
                return !empty($indexes);
            }
            
            $database = $connection->getDatabaseName();
            
            if ($driver === 'mysql') {
                $result = $connection->select(
                    "SELECT COUNT(*) as count 
                     FROM information_schema.statistics 
                     WHERE table_schema = ? 
                     AND table_name = ? 
                     AND index_name = ?",
                    [$database, $table, $indexName]
                );
            } else {
                $result = $connection->select(
                    "SELECT COUNT(*) as count 
                     FROM pg_indexes 
                     WHERE schemaname = 'public' 
                     AND tablename = ? 
                     AND indexname = ?",
                    [$table, $indexName]
                );
            }
            
            return isset($result[0]) && $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Drop an index if it exists.
     */
    private function dropIndexIfExists(Blueprint $table, string $indexName): void
    {
        try {
            $table->dropIndex($indexName);
        } catch (\Exception $e) {
            // Index doesn't exist, ignore
        }
    }
};
