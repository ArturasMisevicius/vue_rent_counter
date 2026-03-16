<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optimize User Model Indexes and Constraints
 * 
 * Adds performance indexes and constraints for multi-tenant architecture
 * following Laravel 12 and project patterns.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Performance indexes for tenant isolation
                $table->index(['tenant_id', 'is_active'], 'users_tenant_active_idx');
                $table->index(['tenant_id', 'role'], 'users_tenant_role_idx');
                $table->index(['parent_user_id', 'is_active'], 'users_parent_active_idx');
                
                // Authentication and session management indexes
                $table->index(['email', 'is_active'], 'users_email_active_idx');
                $table->index(['role', 'is_active'], 'users_role_active_idx');
                $table->index(['last_login_at'], 'users_last_login_idx');
                $table->index(['suspended_at'], 'users_suspended_idx');
                
                // Property assignment index for tenant users
                $table->index(['property_id', 'is_active'], 'users_property_active_idx');
                
                // System tenant index for superadmin operations
                $table->index(['system_tenant_id'], 'users_system_tenant_idx');
                
                // Email verification index
                $table->index(['email_verified_at'], 'users_email_verified_idx');
            });
        }

        // Optimize personal_access_tokens table
        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                // Composite index for token lookups
                $table->index(['tokenable_type', 'tokenable_id', 'name'], 'pat_tokenable_name_idx');
                
                // Performance index for token cleanup
                $table->index(['last_used_at'], 'pat_last_used_idx');
                
                // Add foreign key constraint if not exists
                if (!Schema::hasColumn('personal_access_tokens', 'tokenable_id_fk')) {
                    // Note: We can't add FK constraint directly due to polymorphic relationship
                    // But we can add a partial index for users specifically
                    $table->index(['tokenable_id'], 'pat_user_tokenable_idx')
                          ->where('tokenable_type', 'App\\Models\\User');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_tenant_active_idx');
                $table->dropIndex('users_tenant_role_idx');
                $table->dropIndex('users_parent_active_idx');
                $table->dropIndex('users_email_active_idx');
                $table->dropIndex('users_role_active_idx');
                $table->dropIndex('users_last_login_idx');
                $table->dropIndex('users_suspended_idx');
                $table->dropIndex('users_property_active_idx');
                $table->dropIndex('users_system_tenant_idx');
                $table->dropIndex('users_email_verified_idx');
            });
        }

        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->dropIndex('pat_tokenable_name_idx');
                $table->dropIndex('pat_last_used_idx');
                $table->dropIndex('pat_user_tokenable_idx');
            });
        }
    }
};