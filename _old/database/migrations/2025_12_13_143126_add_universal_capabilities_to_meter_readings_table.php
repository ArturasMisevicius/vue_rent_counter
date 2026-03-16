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
        Schema::table('meter_readings', function (Blueprint $table) {
            // Add new fields for universal capabilities
            if (!Schema::hasColumn('meter_readings', 'reading_values')) {
                $table->json('reading_values')->nullable()->after('zone');
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
                $table->foreignId('validated_by')->nullable()->after('photo_path')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('meter_readings', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }
            if (!Schema::hasColumn('meter_readings', 'validation_notes')) {
                $table->text('validation_notes')->nullable()->after('validated_at');
            }
             
            // Add indexes for performance
            if (Schema::hasColumn('meter_readings', 'input_method') && !Schema::hasIndex('meter_readings', 'meter_readings_input_method_index')) {
                $table->index('input_method');
            }
            if (Schema::hasColumn('meter_readings', 'validation_status') && !Schema::hasIndex('meter_readings', 'meter_readings_validation_status_index')) {
                $table->index('validation_status');
            }
            if (Schema::hasColumn('meter_readings', 'validated_by') && !Schema::hasIndex('meter_readings', 'meter_readings_validated_by_index')) {
                $table->index('validated_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            if (Schema::hasIndex('meter_readings', 'meter_readings_input_method_index')) {
                $table->dropIndex(['input_method']);
            }
            if (Schema::hasIndex('meter_readings', 'meter_readings_validation_status_index')) {
                $table->dropIndex(['validation_status']);
            }
            if (Schema::hasIndex('meter_readings', 'meter_readings_validated_by_index')) {
                $table->dropIndex(['validated_by']);
            }

            if (Schema::hasColumn('meter_readings', 'validated_by')) {
                $table->dropForeign(['validated_by']);
            }

            $columnsToDrop = array_filter([
                Schema::hasColumn('meter_readings', 'reading_values') ? 'reading_values' : null,
                Schema::hasColumn('meter_readings', 'input_method') ? 'input_method' : null,
                Schema::hasColumn('meter_readings', 'validation_status') ? 'validation_status' : null,
                Schema::hasColumn('meter_readings', 'photo_path') ? 'photo_path' : null,
                Schema::hasColumn('meter_readings', 'validated_by') ? 'validated_by' : null,
                Schema::hasColumn('meter_readings', 'validated_at') ? 'validated_at' : null,
                Schema::hasColumn('meter_readings', 'validation_notes') ? 'validation_notes' : null,
            ]);

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
