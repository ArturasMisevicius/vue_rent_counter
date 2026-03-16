<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Additional User Performance Indexes
 * 
 * Adds specialized indexes for common query patterns identified
 * in performance analysis.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Composite index for organization membership queries
            $table->index(['tenant_id', 'role', 'is_active'], 'users_tenant_role_active_idx');
            
            // Index for parent-child relationship queries
            $table->index(['parent_user_id', 'role', 'is_active'], 'users_parent_role_active_idx');
            
            // Index for property assignment with status
            $table->index(['property_id', 'role', 'is_active'], 'users_property_role_active_idx');
            
            // Index for recent activity queries
            $table->index(['last_login_at', 'is_active'], 'users_activity_status_idx');
            
            // Index for email verification status
            $table->index(['email_verified_at', 'is_active'], 'users_verified_active_idx');
            
            // Index for suspension queries
            $table->index(['suspended_at', 'is_active'], 'users_suspension_status_idx');
        });

        // Add indexes for organization_user pivot table if it exists
        if (Schema::hasTable('organization_user')) {
            Schema::table('organization_user', function (Blueprint $table) {
                // Composite index for active memberships
                $table->index(['organization_id', 'user_id', 'is_active'], 'org_user_active_idx');
                
                // Index for role-based queries
                $table->index(['user_id', 'role', 'is_active'], 'org_user_role_active_idx');
                
                // Index for invitation tracking
                $table->index(['invitation_token'], 'org_user_invitation_idx');
                
                // Index for membership date queries
                $table->index(['joined_at', 'is_active'], 'org_user_joined_active_idx');
            });
        }

        // Add indexes for task_assignments table if it exists
        if (Schema::hasTable('task_assignments')) {
            Schema::table('task_assignments', function (Blueprint $table) {
                // Composite index for user task queries
                $table->index(['user_id', 'status', 'role'], 'task_assign_user_status_role_idx');
                
                // Index for task completion tracking
                $table->index(['task_id', 'status', 'completed_at'], 'task_assign_completion_idx');
                
                // Index for workload queries
                $table->index(['user_id', 'assigned_at', 'status'], 'task_assign_workload_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_tenant_role_active_idx');
            $table->dropIndex('users_parent_role_active_idx');
            $table->dropIndex('users_property_role_active_idx');
            $table->dropIndex('users_activity_status_idx');
            $table->dropIndex('users_verified_active_idx');
            $table->dropIndex('users_suspension_status_idx');
        });

        if (Schema::hasTable('organization_user')) {
            Schema::table('organization_user', function (Blueprint $table) {
                $table->dropIndex('org_user_active_idx');
                $table->dropIndex('org_user_role_active_idx');
                $table->dropIndex('org_user_invitation_idx');
                $table->dropIndex('org_user_joined_active_idx');
            });
        }

        if (Schema::hasTable('task_assignments')) {
            Schema::table('task_assignments', function (Blueprint $table) {
                $table->dropIndex('task_assign_user_status_role_idx');
                $table->dropIndex('task_assign_completion_idx');
                $table->dropIndex('task_assign_workload_idx');
            });
        }
    }
};