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
        if (! Schema::hasTable('meter_readings')) {
            return;
        }

        Schema::table('meter_readings', function (Blueprint $table) {
            // PERFORMANCE OPTIMIZATION: Add composite indexes for common query patterns
            
            // Index for validation status queries (batch validation, status filtering)
            if (Schema::hasColumn('meter_readings', 'validation_status')) {
                $table->index(['validation_status', 'reading_date'], 'idx_validation_status_date');
            }
            
            // Index for input method queries (filtering by input type)
            if (Schema::hasColumn('meter_readings', 'input_method') && Schema::hasColumn('meter_readings', 'validation_status')) {
                $table->index(['input_method', 'validation_status'], 'idx_input_method_validation');
            }
            
            // Composite index for meter + zone + date queries (previous reading lookups)
            $table->index(['meter_id', 'zone', 'reading_date'], 'idx_meter_zone_date');
            
            // Index for tenant + date range queries (tenant-scoped reporting)
            $table->index(['tenant_id', 'reading_date', 'validation_status'], 'idx_tenant_date_validation');
            
            // Index for photo path queries (OCR reading filtering)
            if (Schema::hasColumn('meter_readings', 'photo_path')) {
                $table->index(['photo_path'], 'idx_photo_path');
            }
            
            // Index for validated_by queries (audit trail, user activity)
            if (Schema::hasColumn('meter_readings', 'validated_by') && Schema::hasColumn('meter_readings', 'validation_status')) {
                $table->index(['validated_by', 'validation_status'], 'idx_validated_by_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('meter_readings')) {
            return;
        }

        Schema::table('meter_readings', function (Blueprint $table) {
            if (Schema::hasColumn('meter_readings', 'validation_status')) {
                $table->dropIndex('idx_validation_status_date');
            }
            if (Schema::hasColumn('meter_readings', 'input_method') && Schema::hasColumn('meter_readings', 'validation_status')) {
                $table->dropIndex('idx_input_method_validation');
            }
            $table->dropIndex('idx_meter_zone_date');
            $table->dropIndex('idx_tenant_date_validation');
            if (Schema::hasColumn('meter_readings', 'photo_path')) {
                $table->dropIndex('idx_photo_path');
            }
            if (Schema::hasColumn('meter_readings', 'validated_by') && Schema::hasColumn('meter_readings', 'validation_status')) {
                $table->dropIndex('idx_validated_by_status');
            }
        });
    }
};
