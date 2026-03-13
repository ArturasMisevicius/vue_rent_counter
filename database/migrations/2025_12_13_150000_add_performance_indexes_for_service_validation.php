<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Indexes for ServiceValidationEngine N+1 Query Optimization
 * 
 * These indexes are specifically designed to eliminate N+1 queries and
 * improve performance for the ServiceValidationEngine operations.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // INDEX 1: Composite index for previous reading lookups
        // Eliminates N+1 in getPreviousReading() method
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->index(
                ['meter_id', 'zone', 'reading_date', 'validation_status'],
                'idx_meter_readings_previous_lookup'
            );
        });

        // INDEX 2: Composite index for historical readings
        // Optimizes getHistoricalReadings() method
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->index(
                ['meter_id', 'reading_date', 'validation_status'],
                'idx_meter_readings_historical'
            );
        });

        // INDEX 3: Index for validation status filtering
        // Optimizes getReadingsByValidationStatus() method
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->index(
                ['validation_status', 'tenant_id', 'reading_date'],
                'idx_meter_readings_validation_filter'
            );
        });

        // INDEX 4: Index for input method filtering
        // Optimizes estimated reading queries
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->index(
                ['input_method', 'validation_status', 'reading_date'],
                'idx_meter_readings_input_method'
            );
        });

        // INDEX 5: Composite index for batch operations
        // Optimizes bulk validation operations
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'meter_id', 'validation_status'],
                'idx_meter_readings_batch_ops'
            );
        });

        // INDEX 6: Service configuration relationships
        // Optimizes meter -> service configuration lookups
        Schema::table('meters', function (Blueprint $table) {
            $table->index(
                ['service_configuration_id', 'tenant_id'],
                'idx_meters_service_config'
            );
        });

        // INDEX 7: Service configuration utility service lookup
        // Optimizes service configuration -> utility service joins
        Schema::table('service_configurations', function (Blueprint $table) {
            $table->index(
                ['utility_service_id', 'is_active', 'tenant_id'],
                'idx_service_configs_utility'
            );
        });

        // INDEX 8: Tariff and provider lookups
        Schema::table('service_configurations', function (Blueprint $table) {
            $table->index(['tariff_id'], 'idx_service_configs_tariff');
            $table->index(['provider_id'], 'idx_service_configs_provider');
        });

        // INDEX 9: Property relationships for meters
        Schema::table('meters', function (Blueprint $table) {
            $table->index(
                ['property_id', 'type', 'tenant_id'],
                'idx_meters_property_type'
            );
        });

        // INDEX 10: Audit and logging indexes
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->index(['validated_by', 'validated_at'], 'idx_meter_readings_validation_audit');
            $table->index(['entered_by', 'created_at'], 'idx_meter_readings_entry_audit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropIndex('idx_meter_readings_previous_lookup');
            $table->dropIndex('idx_meter_readings_historical');
            $table->dropIndex('idx_meter_readings_validation_filter');
            $table->dropIndex('idx_meter_readings_input_method');
            $table->dropIndex('idx_meter_readings_batch_ops');
            $table->dropIndex('idx_meter_readings_validation_audit');
            $table->dropIndex('idx_meter_readings_entry_audit');
        });

        Schema::table('meters', function (Blueprint $table) {
            $table->dropIndex('idx_meters_service_config');
            $table->dropIndex('idx_meters_property_type');
        });

        Schema::table('service_configurations', function (Blueprint $table) {
            $table->dropIndex('idx_service_configs_utility');
            $table->dropIndex('idx_service_configs_tariff');
            $table->dropIndex('idx_service_configs_provider');
        });
    }
};