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
        // Enhance UtilityService model with universal capabilities
        if (Schema::hasTable('utility_services')) {
            Schema::table('utility_services', function (Blueprint $table) {
                if (!Schema::hasColumn('utility_services', 'configuration_schema')) {
                    $table->json('configuration_schema')->nullable()->after('calculation_formula');
                }
                if (!Schema::hasColumn('utility_services', 'validation_rules')) {
                    $table->json('validation_rules')->nullable()->after('configuration_schema');
                }
                if (!Schema::hasColumn('utility_services', 'business_logic_config')) {
                    $table->json('business_logic_config')->nullable()->after('validation_rules');
                }
                if (!Schema::hasColumn('utility_services', 'service_type_bridge')) {
                    $table->string('service_type_bridge')->nullable()->after('business_logic_config');
                }
                if (!Schema::hasColumn('utility_services', 'is_global_template')) {
                    $table->boolean('is_global_template')->default(false)->after('service_type_bridge');
                }
                if (!Schema::hasColumn('utility_services', 'created_by_tenant_id')) {
                    $table->unsignedBigInteger('created_by_tenant_id')->nullable()->after('is_global_template');
                }

                // Add indexes for performance
                if (!Schema::hasIndex('utility_services', ['tenant_id', 'is_active'])) {
                    $table->index(['tenant_id', 'is_active']);
                }
                if (!Schema::hasIndex('utility_services', ['is_global_template', 'is_active'])) {
                    $table->index(['is_global_template', 'is_active']);
                }
                if (!Schema::hasIndex('utility_services', 'service_type_bridge')) {
                    $table->index('service_type_bridge');
                }
            });
        }

        // Enhance ServiceConfiguration model with universal capabilities
        if (Schema::hasTable('service_configurations')) {
            Schema::table('service_configurations', function (Blueprint $table) {
                if (!Schema::hasColumn('service_configurations', 'area_type')) {
                    $table->string('area_type')->nullable()->after('distribution_method');
                }
                if (!Schema::hasColumn('service_configurations', 'custom_formula')) {
                    $table->text('custom_formula')->nullable()->after('area_type');
                }
                if (!Schema::hasColumn('service_configurations', 'configuration_overrides')) {
                    $table->json('configuration_overrides')->nullable()->after('custom_formula');
                }

                // Add indexes for performance
                if (!Schema::hasIndex('service_configurations', ['tenant_id', 'is_active'])) {
                    $table->index(['tenant_id', 'is_active']);
                }
                if (!Schema::hasIndex('service_configurations', ['effective_from', 'effective_until'])) {
                    $table->index(['effective_from', 'effective_until']);
                }
                if (!Schema::hasIndex('service_configurations', ['property_id', 'utility_service_id'])) {
                    $table->index(['property_id', 'utility_service_id']);
                }
            });
        }

        // Enhance MeterReading model with universal capabilities
        if (Schema::hasTable('meter_readings')) {
            Schema::table('meter_readings', function (Blueprint $table) {
                if (!Schema::hasColumn('meter_readings', 'reading_values')) {
                    $table->json('reading_values')->nullable()->after('value');
                }
                if (!Schema::hasColumn('meter_readings', 'input_method')) {
                    $table->string('input_method')->default('manual')->after('reading_values');
                }
                if (!Schema::hasColumn('meter_readings', 'validation_status')) {
                    $table->string('validation_status')->default('pending')->after('input_method');
                }
                if (!Schema::hasColumn('meter_readings', 'photo_path')) {
                    $table->string('photo_path')->nullable()->after('validation_status');
                }
                if (!Schema::hasColumn('meter_readings', 'validated_by')) {
                    $table->unsignedBigInteger('validated_by')->nullable()->after('photo_path');
                }

                // Add indexes for performance
                if (!Schema::hasIndex('meter_readings', ['tenant_id', 'validation_status'])) {
                    $table->index(['tenant_id', 'validation_status']);
                }
                if (!Schema::hasIndex('meter_readings', ['meter_id', 'reading_date'])) {
                    $table->index(['meter_id', 'reading_date']);
                }
                if (!Schema::hasIndex('meter_readings', ['input_method', 'validation_status'])) {
                    $table->index(['input_method', 'validation_status']);
                }

                // Add foreign key constraint for validated_by
                if (!Schema::hasIndex('meter_readings', 'meter_readings_validated_by_foreign')) {
                    $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        }

        // Enhance Meter model with universal capabilities
        if (Schema::hasTable('meters')) {
            Schema::table('meters', function (Blueprint $table) {
                if (!Schema::hasColumn('meters', 'reading_structure')) {
                    $table->json('reading_structure')->nullable()->after('supports_zones');
                }
                if (!Schema::hasColumn('meters', 'service_configuration_id')) {
                    $table->unsignedBigInteger('service_configuration_id')->nullable()->after('reading_structure');
                }

                // Add foreign key constraint for service_configuration_id
                if (!Schema::hasIndex('meters', 'meters_service_configuration_id_foreign')) {
                    $table->foreign('service_configuration_id')->references('id')->on('service_configurations')->onDelete('set null');
                }

                // Add index for performance
                if (!Schema::hasIndex('meters', 'service_configuration_id')) {
                    $table->index('service_configuration_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove enhancements from MeterReading model
        if (Schema::hasTable('meter_readings')) {
            Schema::table('meter_readings', function (Blueprint $table) {
                $table->dropForeign(['validated_by']);
                $table->dropIndex(['tenant_id', 'validation_status']);
                $table->dropIndex(['meter_id', 'reading_date']);
                $table->dropIndex(['input_method', 'validation_status']);
                $table->dropColumn([
                    'reading_values',
                    'input_method',
                    'validation_status',
                    'photo_path',
                    'validated_by',
                ]);
            });
        }

        // Remove enhancements from Meter model
        if (Schema::hasTable('meters')) {
            Schema::table('meters', function (Blueprint $table) {
                $table->dropForeign(['service_configuration_id']);
                $table->dropIndex(['service_configuration_id']);
                $table->dropColumn([
                    'reading_structure',
                    'service_configuration_id',
                ]);
            });
        }

        // Remove enhancements from ServiceConfiguration model
        if (Schema::hasTable('service_configurations')) {
            Schema::table('service_configurations', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'is_active']);
                $table->dropIndex(['effective_from', 'effective_until']);
                $table->dropIndex(['property_id', 'utility_service_id']);
                $table->dropColumn([
                    'area_type',
                    'custom_formula',
                    'configuration_overrides',
                ]);
            });
        }

        // Remove enhancements from UtilityService model
        if (Schema::hasTable('utility_services')) {
            Schema::table('utility_services', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'is_active']);
                $table->dropIndex(['is_global_template', 'is_active']);
                $table->dropIndex(['service_type_bridge']);
                $table->dropColumn([
                    'configuration_schema',
                    'validation_rules',
                    'business_logic_config',
                    'service_type_bridge',
                    'is_global_template',
                    'created_by_tenant_id',
                ]);
            });
        }
    }
};