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
        // METER READINGS - Critical Performance Indexes
        Schema::table('meter_readings', function (Blueprint $table) {
            // Composite index for tenant + date range queries (most common)
            $table->index(['tenant_id', 'reading_date', 'meter_id'], 'mr_tenant_date_meter_idx');
            
            // Covering index for dashboard aggregations
            $table->index(['tenant_id', 'validation_status', 'created_at', 'value'], 'mr_dashboard_covering_idx');
            
            // Meter-specific queries with zone support
            $table->index(['meter_id', 'zone', 'reading_date'], 'mr_meter_zone_date_idx');
            
            // Validation workflow queries
            $table->index(['validation_status', 'input_method', 'created_at'], 'mr_validation_workflow_idx');
            
            // Audit trail queries
            $table->index(['entered_by', 'created_at'], 'mr_audit_user_idx');
            $table->index(['validated_by', 'validated_at'], 'mr_validation_audit_idx');
        });

        // METERS - Relationship and Service Queries
        Schema::table('meters', function (Blueprint $table) {
            // Property-based meter queries
            $table->index(['property_id', 'type', 'tenant_id'], 'meters_property_type_idx');
            
            // Service configuration queries
            $table->index(['service_configuration_id', 'tenant_id'], 'meters_service_config_idx');
            
            // Zone support filtering
            $table->index(['supports_zones', 'type', 'tenant_id'], 'meters_zones_type_idx');
            
            // Installation date queries for reporting
            $table->index(['installation_date', 'tenant_id'], 'meters_install_date_idx');
        });

        // INVOICES - Billing and Status Queries
        Schema::table('invoices', function (Blueprint $table) {
            // Billing period queries (most critical for utilities)
            $table->index(['tenant_id', 'billing_period_start', 'billing_period_end'], 'invoices_billing_period_idx');
            
            // Status-based filtering with dates
            $table->index(['status', 'due_date', 'tenant_id'], 'invoices_status_due_idx');
            
            // Property-based invoice queries
            $table->index(['property_id', 'status', 'created_at'], 'invoices_property_status_idx');
            
            // Payment tracking
            $table->index(['paid_at', 'status'], 'invoices_payment_tracking_idx');
        });

        // PROPERTIES - Tenant and Building Relationships
        Schema::table('properties', function (Blueprint $table) {
            // Building-based property queries
            $table->index(['building_id', 'tenant_id'], 'properties_building_tenant_idx');
            
            // Active properties filtering
            $table->index(['tenant_id', 'created_at', 'updated_at'], 'properties_tenant_activity_idx');
        });

        // SERVICE CONFIGURATIONS - Universal Service Queries
        if (Schema::hasTable('service_configurations')) {
            Schema::table('service_configurations', function (Blueprint $table) {
                // Property service lookups
                $table->index(['property_id', 'utility_service_id'], 'sc_property_service_idx');
                
                // Tenant service configurations
                $table->index(['tenant_id', 'is_active'], 'sc_tenant_active_idx');
                
                // Pricing model queries
                $table->index(['pricing_model', 'tenant_id'], 'sc_pricing_model_idx');
            });
        }

        // UTILITY SERVICES - Global Template Queries
        if (Schema::hasTable('utility_services')) {
            Schema::table('utility_services', function (Blueprint $table) {
                // Global vs tenant-specific services
                $table->index(['is_global_template', 'is_active'], 'us_global_active_idx');
                
                // Tenant customizations
                $table->index(['tenant_id', 'parent_service_id'], 'us_tenant_parent_idx');
                
                // Service type filtering
                $table->index(['service_type', 'is_active'], 'us_type_active_idx');
            });
        }

        // TARIFFS - Rate Calculation Queries
        Schema::table('tariffs', function (Blueprint $table) {
            // Active tariff lookups (critical for billing)
            $table->index(['provider_id', 'active_from', 'active_until'], 'tariffs_active_period_idx');
            
            // Zone-based tariff queries
            $table->index(['zone', 'type', 'active_from'], 'tariffs_zone_type_idx');
            
            // Tenant tariff filtering
            $table->index(['tenant_id', 'active_from'], 'tariffs_tenant_active_idx');
        });

        // AUDIT TABLES - Historical Query Optimization
        if (Schema::hasTable('meter_reading_audits')) {
            Schema::table('meter_reading_audits', function (Blueprint $table) {
                // Audit trail by reading
                $table->index(['meter_reading_id', 'created_at'], 'mra_reading_date_idx');
                
                // User audit queries
                $table->index(['changed_by', 'created_at'], 'mra_user_date_idx');
                
                // Change type filtering
                $table->index(['change_type', 'created_at'], 'mra_type_date_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropIndex('mr_tenant_date_meter_idx');
            $table->dropIndex('mr_dashboard_covering_idx');
            $table->dropIndex('mr_meter_zone_date_idx');
            $table->dropIndex('mr_validation_workflow_idx');
            $table->dropIndex('mr_audit_user_idx');
            $table->dropIndex('mr_validation_audit_idx');
        });

        Schema::table('meters', function (Blueprint $table) {
            $table->dropIndex('meters_property_type_idx');
            $table->dropIndex('meters_service_config_idx');
            $table->dropIndex('meters_zones_type_idx');
            $table->dropIndex('meters_install_date_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_billing_period_idx');
            $table->dropIndex('invoices_status_due_idx');
            $table->dropIndex('invoices_property_status_idx');
            $table->dropIndex('invoices_payment_tracking_idx');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('properties_building_tenant_idx');
            $table->dropIndex('properties_tenant_activity_idx');
        });

        if (Schema::hasTable('service_configurations')) {
            Schema::table('service_configurations', function (Blueprint $table) {
                $table->dropIndex('sc_property_service_idx');
                $table->dropIndex('sc_tenant_active_idx');
                $table->dropIndex('sc_pricing_model_idx');
            });
        }

        if (Schema::hasTable('utility_services')) {
            Schema::table('utility_services', function (Blueprint $table) {
                $table->dropIndex('us_global_active_idx');
                $table->dropIndex('us_tenant_parent_idx');
                $table->dropIndex('us_type_active_idx');
            });
        }

        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropIndex('tariffs_active_period_idx');
            $table->dropIndex('tariffs_zone_type_idx');
            $table->dropIndex('tariffs_tenant_active_idx');
        });

        if (Schema::hasTable('meter_reading_audits')) {
            Schema::table('meter_reading_audits', function (Blueprint $table) {
                $table->dropIndex('mra_reading_date_idx');
                $table->dropIndex('mra_user_date_idx');
                $table->dropIndex('mra_type_date_idx');
            });
        }
    }
};