<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add tenant management columns if not exists
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->index('tenant_id');
            }
        });

        // Enhance tenants table with management fields
        Schema::table('tenants', function (Blueprint $table) {
            // Tenant identification
            $table->string('slug')->unique()->after('id');
            $table->string('domain')->nullable()->unique()->after('slug');
            
            // Status management
            $table->boolean('is_active')->default(true)->after('email');
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            
            // Subscription & limits
            $table->string('plan')->default('basic'); // basic, professional, enterprise
            $table->integer('max_properties')->default(100);
            $table->integer('max_users')->default(10);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            
            // Configuration
            $table->json('settings')->nullable(); // Tenant-specific settings
            $table->json('features')->nullable(); // Feature flags
            
            // Metadata
            $table->string('timezone')->default('Europe/Vilnius');
            $table->string('locale')->default('lt');
            $table->string('currency')->default('EUR');
            
            // Audit
            $table->timestamp('last_activity_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            
            // Indexes
            $table->index('is_active');
            $table->index('plan');
            $table->index(['is_active', 'subscription_ends_at']);
        });

        // Create tenant_activity_log table for audit trail
        Schema::create('tenant_activity_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action'); // login, logout, data_access, export, etc.
            $table->string('resource_type')->nullable(); // Property, Invoice, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['tenant_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });

        // Create tenant_invitations table
        Schema::create('tenant_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('email');
            $table->string('role'); // manager, tenant
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('invited_by');
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['tenant_id', 'email']);
            $table->index('token');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invitations');
        Schema::dropIfExists('tenant_activity_log');
        
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique('tenants_slug_unique');
            $table->dropUnique('tenants_domain_unique');
            $table->dropIndex('tenants_is_active_index');
            $table->dropIndex('tenants_plan_index');
            $table->dropIndex('tenants_is_active_subscription_ends_at_index');

            $table->dropColumn([
                'slug', 'domain', 'is_active', 'suspended_at', 'suspension_reason',
                'plan', 'max_properties', 'max_users', 'trial_ends_at', 
                'subscription_ends_at', 'settings', 'features', 'timezone',
                'locale', 'currency', 'last_activity_at', 'created_by'
            ]);
        });
    }
};
