<?php

declare(strict_types=1);

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
        Schema::table('organizations', function (Blueprint $table) {
            // Add missing super admin control fields
            if (!Schema::hasColumn('organizations', 'storage_used_mb')) {
                $table->float('storage_used_mb')->default(0)->after('created_by_admin_id');
            }
            if (!Schema::hasColumn('organizations', 'api_calls_today')) {
                $table->integer('api_calls_today')->default(0)->after('storage_used_mb');
            }
            if (!Schema::hasColumn('organizations', 'api_calls_quota')) {
                $table->integer('api_calls_quota')->default(10000)->after('api_calls_today');
            }
            if (!Schema::hasColumn('organizations', 'average_response_time')) {
                $table->float('average_response_time')->default(0)->after('api_calls_quota');
            }
        });
        
        // Add indexes for super admin queries
        Schema::table('organizations', function (Blueprint $table) {
            if (!$this->indexExists('organizations', 'org_active_suspended_idx')) {
                $table->index(['is_active', 'suspended_at'], 'org_active_suspended_idx');
            }
            if (!$this->indexExists('organizations', 'org_plan_active_idx')) {
                $table->index(['plan', 'is_active'], 'org_plan_active_idx');
            }
            if (!$this->indexExists('organizations', 'org_created_by_admin_idx')) {
                $table->index(['created_by_admin_id'], 'org_created_by_admin_idx');
            }
            if (!$this->indexExists('organizations', 'org_last_activity_idx')) {
                $table->index(['last_activity_at'], 'org_last_activity_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Drop indexes
            if ($this->indexExists('organizations', 'org_active_suspended_idx')) {
                $table->dropIndex('org_active_suspended_idx');
            }
            if ($this->indexExists('organizations', 'org_plan_active_idx')) {
                $table->dropIndex('org_plan_active_idx');
            }
            if ($this->indexExists('organizations', 'org_created_by_admin_idx')) {
                $table->dropIndex('org_created_by_admin_idx');
            }
            if ($this->indexExists('organizations', 'org_last_activity_idx')) {
                $table->dropIndex('org_last_activity_idx');
            }
            
            // Drop only the columns we added
            $columnsToCheck = [
                'storage_used_mb',
                'api_calls_today', 
                'api_calls_quota',
                'average_response_time',
            ];
            
            $columnsToRemove = [];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('organizations', $column)) {
                    $columnsToRemove[] = $column;
                }
            }
            
            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }
    
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes($table);
                
            return array_key_exists($indexName, $indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
};
