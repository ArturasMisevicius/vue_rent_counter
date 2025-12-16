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
        // Enhance organizations table for better multi-tenancy
        Schema::table('organizations', function (Blueprint $table) {
            // Domain-based tenancy
            $table->string('subdomain')->nullable()->unique()->after('slug');
            $table->string('custom_domain')->nullable()->unique()->after('domain');
            $table->boolean('custom_domain_verified')->default(false)->after('custom_domain');
            
            // Tenant configuration
            $table->json('tenant_config')->nullable()->after('settings');
            $table->string('database_name')->nullable()->after('tenant_config'); // For future multi-database support
            $table->string('storage_disk')->default('tenant')->after('database_name');
            
            // Performance and monitoring
            $table->timestamp('last_backup_at')->nullable()->after('last_activity_at');
            $table->json('performance_metrics')->nullable()->after('last_backup_at');
            
            // Indexes for performance
            $table->index('subdomain', 'organizations_subdomain_index');
            $table->index('custom_domain', 'organizations_custom_domain_index');
            $table->index(['is_active', 'custom_domain_verified'], 'organizations_active_verified_index');
        });

        // Create tenant domains table for multiple domain support
        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->json('ssl_config')->nullable();
            $table->timestamps();

            $table->index(['domain', 'is_verified'], 'tenant_domains_domain_verified_index');
            $table->index(['organization_id', 'is_primary'], 'tenant_domains_org_primary_index');
        });

        // Create tenant settings table for configuration management
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, json, boolean, integer
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'key'], 'tenant_settings_org_key_unique');
            $table->index('key', 'tenant_settings_key_index');
        });

        // Create tenant storage usage tracking
        Schema::create('tenant_storage_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('storage_type'); // files, database, cache, logs
            $table->bigInteger('bytes_used')->default(0);
            $table->bigInteger('file_count')->default(0);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['organization_id', 'storage_type'], 'tenant_storage_org_type_unique');
            $table->index('calculated_at', 'tenant_storage_calculated_at_index');
        });

        // Create tenant API usage tracking
        Schema::create('tenant_api_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->date('usage_date');
            $table->string('endpoint')->nullable();
            $table->integer('request_count')->default(0);
            $table->bigInteger('response_time_total')->default(0); // microseconds
            $table->integer('error_count')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'usage_date', 'endpoint'], 'tenant_api_usage_unique');
            $table->index(['organization_id', 'usage_date'], 'tenant_api_usage_org_date_index');
        });

        // Enhance users table for better tenant isolation
        Schema::table('users', function (Blueprint $table) {
            // Add tenant context validation
            $table->json('tenant_permissions')->nullable()->after('property_id');
            $table->timestamp('tenant_joined_at')->nullable()->after('tenant_permissions');
            $table->boolean('is_tenant_admin')->default(false)->after('tenant_joined_at');
            
            // Add indexes for performance
            $table->index(['tenant_id', 'is_tenant_admin'], 'users_tenant_admin_index');
            $table->index(['tenant_id', 'role'], 'users_tenant_role_index');
        });

        // Create tenant user invitations table
        Schema::create('tenant_user_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('email');
            $table->string('role');
            $table->json('permissions')->nullable();
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['organization_id', 'email'], 'tenant_invites_org_email_index');
            $table->index('expires_at', 'tenant_invites_expires_at_index');
        });

        // Create tenant onboarding tracking
        Schema::create('tenant_onboarding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->json('steps_completed')->nullable(); // Array of completed step names
            $table->json('onboarding_data')->nullable(); // Temporary data during onboarding
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'is_completed'], 'tenant_onboarding_org_completed_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_onboarding');
        Schema::dropIfExists('tenant_user_invitations');
        Schema::dropIfExists('tenant_api_usage');
        Schema::dropIfExists('tenant_storage_usage');
        Schema::dropIfExists('tenant_settings');
        Schema::dropIfExists('tenant_domains');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_tenant_admin_index');
            $table->dropIndex('users_tenant_role_index');
            $table->dropColumn(['tenant_permissions', 'tenant_joined_at', 'is_tenant_admin']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex('organizations_subdomain_index');
            $table->dropIndex('organizations_custom_domain_index');
            $table->dropIndex('organizations_active_verified_index');
            $table->dropColumn([
                'subdomain', 'custom_domain', 'custom_domain_verified',
                'tenant_config', 'database_name', 'storage_disk',
                'last_backup_at', 'performance_metrics'
            ]);
        });
    }
};