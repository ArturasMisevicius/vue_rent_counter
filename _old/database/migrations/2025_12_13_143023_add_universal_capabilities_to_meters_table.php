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
        Schema::table('meters', function (Blueprint $table) {
            // Add new fields for universal capabilities
            if (!Schema::hasColumn('meters', 'reading_structure')) {
                $table->json('reading_structure')->nullable()->after('supports_zones');
            }

            if (!Schema::hasColumn('meters', 'service_configuration_id')) {
                $table->foreignId('service_configuration_id')
                    ->nullable()
                    ->after('reading_structure')
                    ->constrained()
                    ->onDelete('set null');
            }

            // Add index for performance if the column exists
            if (
                Schema::hasColumn('meters', 'service_configuration_id') &&
                !Schema::hasIndex('meters', ['service_configuration_id'])
            ) {
                $table->index('service_configuration_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meters', function (Blueprint $table) {
            if (Schema::hasColumn('meters', 'service_configuration_id')) {
                if (Schema::hasIndex('meters', ['service_configuration_id'])) {
                    $table->dropIndex(['service_configuration_id']);
                }
                $table->dropForeign(['service_configuration_id']);
                $table->dropColumn('service_configuration_id');
            }

            if (Schema::hasColumn('meters', 'reading_structure')) {
                $table->dropColumn('reading_structure');
            }
        });
    }
};
