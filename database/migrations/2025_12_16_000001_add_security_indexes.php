<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Security-Focused Database Indexes
 * 
 * Adds performance indexes specifically for security-related queries:
 * - Token validation and cleanup operations
 * - User authentication and authorization
 * - Security monitoring and audit queries
 * 
 * These indexes improve performance for security-critical operations
 * and prevent potential DoS attacks through slow queries.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Personal Access Tokens Security Indexes
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Token validation performance (most critical)
            $table->index(['tokenable_type', 'tokenable_id', 'expires_at'], 'pat_validation_idx');
            
            // Cleanup operations for expired tokens
            $table->index(['expires_at', 'created_at'], 'pat_cleanup_idx');
            
            // Usage tracking for security monitoring
            $table->index(['last_used_at'], 'pat_usage_idx');
            
            // Security monitoring and audit queries
            $table->index(['created_at', 'tokenable_type'], 'pat_monitoring_idx');
            
            // Token enumeration protection
            $table->index(['tokenable_id', 'name'], 'pat_user_tokens_idx');
        });

        // Users Security Indexes
        Schema::table('users', function (Blueprint $table) {
            // Authentication queries (login, API access)
            $table->index(['email', 'is_active', 'suspended_at'], 'users_auth_idx');
            
            // API eligibility checks
            $table->index(['is_active', 'email_verified_at', 'suspended_at'], 'users_api_eligible_idx');
            
            // Security monitoring queries
            $table->index(['last_login_at', 'role'], 'users_security_idx');
            
            // Tenant-based security queries
            $table->index(['tenant_id', 'role', 'is_active'], 'users_tenant_security_idx');
            
            // Superadmin monitoring
            $table->index(['is_super_admin', 'created_at'], 'users_superadmin_idx');
        });

        // Add indexes for audit and security logging tables if they exist
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'audit_user_time_idx');
                $table->index(['event_type', 'created_at'], 'audit_event_time_idx');
                $table->index(['ip_address', 'created_at'], 'audit_ip_time_idx');
            });
        }

        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->index(['failed_at'], 'failed_jobs_time_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('pat_validation_idx');
            $table->dropIndex('pat_cleanup_idx');
            $table->dropIndex('pat_usage_idx');
            $table->dropIndex('pat_monitoring_idx');
            $table->dropIndex('pat_user_tokens_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_auth_idx');
            $table->dropIndex('users_api_eligible_idx');
            $table->dropIndex('users_security_idx');
            $table->dropIndex('users_tenant_security_idx');
            $table->dropIndex('users_superadmin_idx');
        });

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('audit_user_time_idx');
                $table->dropIndex('audit_event_time_idx');
                $table->dropIndex('audit_ip_time_idx');
            });
        }

        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->dropIndex('failed_jobs_time_idx');
            });
        }
    }
};