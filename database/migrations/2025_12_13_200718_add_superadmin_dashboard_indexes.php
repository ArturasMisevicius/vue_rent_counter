<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds database indexes for superadmin dashboard performance optimization.
     * These indexes support the frequently queried columns in dashboard widgets
     * and CRUD operations.
     */
    public function up(): void
    {
        // Organizations table indexes for dashboard queries
        Schema::table('organizations', function (Blueprint $table) {
            // Index for active/inactive filtering
            if (!$this->indexExists('organizations', 'idx_organizations_status')) {
                $table->index(['is_active', 'suspended_at'], 'idx_organizations_status');
            }
            
            // Index for subscription status queries
            if (!$this->indexExists('organizations', 'idx_organizations_subscription')) {
                $table->index(['subscription_ends_at', 'is_active'], 'idx_organizations_subscription');
            }
            
            // Index for plan-based filtering
            if (!$this->indexExists('organizations', 'idx_organizations_plan')) {
                $table->index(['plan', 'is_active'], 'idx_organizations_plan');
            }
            
            // Index for growth calculations (created_at queries)
            if (!$this->indexExists('organizations', 'idx_organizations_created')) {
                $table->index(['created_at', 'is_active'], 'idx_organizations_created');
            }
            
            // Index for trial status queries
            if (!$this->indexExists('organizations', 'idx_organizations_trial')) {
                $table->index(['trial_ends_at', 'is_active'], 'idx_organizations_trial');
            }
        });

        // Subscriptions table indexes for dashboard queries
        Schema::table('subscriptions', function (Blueprint $table) {
            // Index for status-based filtering (most common query)
            if (!$this->indexExists('subscriptions', 'idx_subscriptions_status')) {
                $table->index(['status', 'expires_at'], 'idx_subscriptions_status');
            }
            
            // Index for expiry date queries (expiring soon widget)
            if (!$this->indexExists('subscriptions', 'idx_subscriptions_expiry')) {
                $table->index(['expires_at', 'status'], 'idx_subscriptions_expiry');
            }
            
            // Index for plan type filtering
            if (!$this->indexExists('subscriptions', 'idx_subscriptions_plan')) {
                $table->index(['plan_type', 'status'], 'idx_subscriptions_plan');
            }
            
            // Index for user relationship queries
            if (!$this->indexExists('subscriptions', 'idx_subscriptions_user')) {
                $table->index(['user_id', 'status'], 'idx_subscriptions_user');
            }
            
            // Index for date range queries
            if (!$this->indexExists('subscriptions', 'idx_subscriptions_dates')) {
                $table->index(['starts_at', 'expires_at'], 'idx_subscriptions_dates');
            }
        });

        // Organization activity logs indexes for dashboard and filtering
        Schema::table('organization_activity_log', function (Blueprint $table) {
            // Index for recent activity queries (most common)
            if (!$this->indexExists('organization_activity_log', 'idx_activity_logs_recent')) {
                $table->index(['created_at', 'organization_id'], 'idx_activity_logs_recent');
            }
            
            // Index for organization-specific filtering
            if (!$this->indexExists('organization_activity_log', 'idx_activity_logs_org')) {
                $table->index(['organization_id', 'created_at'], 'idx_activity_logs_org');
            }
            
            // Index for user-specific filtering
            if (!$this->indexExists('organization_activity_log', 'idx_activity_logs_user')) {
                $table->index(['user_id', 'created_at'], 'idx_activity_logs_user');
            }
            
            // Index for action type filtering
            if (!$this->indexExists('organization_activity_log', 'idx_activity_logs_action')) {
                $table->index(['action', 'created_at'], 'idx_activity_logs_action');
            }
            
            // Index for resource type filtering
            if (!$this->indexExists('organization_activity_log', 'idx_activity_logs_resource')) {
                $table->index(['resource_type', 'resource_id'], 'idx_activity_logs_resource');
            }
            
            // Composite index for complex dashboard queries
            if (!$this->indexExists('organization_activity_log', 'idx_activity_logs_dashboard')) {
                $table->index(['created_at', 'organization_id', 'user_id'], 'idx_activity_logs_dashboard');
            }
        });

        // Users table indexes for cross-organization user management
        Schema::table('users', function (Blueprint $table) {
            // Index for role-based filtering
            if (!$this->indexExists('users', 'idx_users_role')) {
                $table->index(['role', 'tenant_id'], 'idx_users_role');
            }
            
            // Index for active user queries
            if (!$this->indexExists('users', 'idx_users_active')) {
                $table->index(['is_active', 'tenant_id'], 'idx_users_active');
            }
            
            // Index for last login filtering
            if (!$this->indexExists('users', 'idx_users_last_login')) {
                $table->index(['last_login_at', 'tenant_id'], 'idx_users_last_login');
            }
            
            // Index for email verification status
            if (!$this->indexExists('users', 'idx_users_verified')) {
                $table->index(['email_verified_at', 'tenant_id'], 'idx_users_verified');
            }
        });

        // Properties table indexes for organization metrics
        Schema::table('properties', function (Blueprint $table) {
            // Index for tenant-based counting (already exists but ensure it's optimized)
            if (!$this->indexExists('properties', 'idx_properties_tenant_count')) {
                $table->index(['tenant_id', 'created_at'], 'idx_properties_tenant_count');
            }
        });

        // Buildings table indexes for organization metrics
        Schema::table('buildings', function (Blueprint $table) {
            // Index for tenant-based counting
            if (!$this->indexExists('buildings', 'idx_buildings_tenant_count')) {
                $table->index(['tenant_id', 'created_at'], 'idx_buildings_tenant_count');
            }
        });

        // Invoices table indexes for platform usage metrics
        Schema::table('invoices', function (Blueprint $table) {
            // Index for tenant-based counting and status filtering
            if (!$this->indexExists('invoices', 'idx_invoices_tenant_status')) {
                $table->index(['tenant_id', 'status', 'created_at'], 'idx_invoices_tenant_status');
            }
        });

        // System health metrics table indexes (if exists)
        if (Schema::hasTable('system_health_metrics')) {
            Schema::table('system_health_metrics', function (Blueprint $table) {
                // Index for metric type and timestamp queries
                $table->index(['metric_type', 'checked_at'], 'idx_health_metrics_type');
                
                // Index for status-based filtering
                $table->index(['status', 'checked_at'], 'idx_health_metrics_status');
            });
        }

        // Organization invitations table indexes (if exists)
        if (Schema::hasTable('organization_invitations')) {
            Schema::table('organization_invitations', function (Blueprint $table) {
                // Index for status-based filtering
                $table->index(['status', 'created_at'], 'idx_invitations_status');
                
                // Index for expiry date queries
                $table->index(['expires_at', 'status'], 'idx_invitations_expiry');
                
                // Index for plan type filtering
                $table->index(['plan_type', 'status'], 'idx_invitations_plan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop organizations indexes
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex('idx_organizations_status');
            $table->dropIndex('idx_organizations_subscription');
            $table->dropIndex('idx_organizations_plan');
            $table->dropIndex('idx_organizations_created');
            $table->dropIndex('idx_organizations_trial');
        });

        // Drop subscriptions indexes
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_subscriptions_status');
            $table->dropIndex('idx_subscriptions_expiry');
            $table->dropIndex('idx_subscriptions_plan');
            $table->dropIndex('idx_subscriptions_user');
            $table->dropIndex('idx_subscriptions_dates');
        });

        // Drop activity logs indexes
        Schema::table('organization_activity_log', function (Blueprint $table) {
            $table->dropIndex('idx_activity_logs_recent');
            $table->dropIndex('idx_activity_logs_org');
            $table->dropIndex('idx_activity_logs_user');
            $table->dropIndex('idx_activity_logs_action');
            $table->dropIndex('idx_activity_logs_resource');
            $table->dropIndex('idx_activity_logs_dashboard');
        });

        // Drop users indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_active');
            $table->dropIndex('idx_users_last_login');
            $table->dropIndex('idx_users_verified');
        });

        // Drop properties indexes
        Schema::table('properties', function (Blueprint $table) {
            if ($this->indexExists('properties', 'idx_properties_tenant_count')) {
                $table->dropIndex('idx_properties_tenant_count');
            }
        });

        // Drop buildings indexes
        Schema::table('buildings', function (Blueprint $table) {
            if ($this->indexExists('buildings', 'idx_buildings_tenant_count')) {
                $table->dropIndex('idx_buildings_tenant_count');
            }
        });

        // Drop invoices indexes
        Schema::table('invoices', function (Blueprint $table) {
            if ($this->indexExists('invoices', 'idx_invoices_tenant_status')) {
                $table->dropIndex('idx_invoices_tenant_status');
            }
        });

        // Drop system health metrics indexes
        if (Schema::hasTable('system_health_metrics')) {
            Schema::table('system_health_metrics', function (Blueprint $table) {
                $table->dropIndex('idx_health_metrics_type');
                $table->dropIndex('idx_health_metrics_status');
            });
        }

        // Drop organization invitations indexes
        if (Schema::hasTable('organization_invitations')) {
            Schema::table('organization_invitations', function (Blueprint $table) {
                $table->dropIndex('idx_invitations_status');
                $table->dropIndex('idx_invitations_expiry');
                $table->dropIndex('idx_invitations_plan');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $indexInfo) {
                if ($indexInfo->name === $index) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
};