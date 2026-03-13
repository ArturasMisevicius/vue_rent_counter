<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            // Index for date range queries (active tariff lookups)
            $table->index(['active_from', 'active_until'], 'tariffs_active_dates_index');
            
            // Composite index for provider + active date queries
            $table->index(['provider_id', 'active_from'], 'tariffs_provider_active_index');
            
            // Index for configuration type filtering (JSON column)
            // Note: This creates a virtual column index for better JSON query performance
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE tariffs ADD INDEX tariffs_config_type_index ((CAST(configuration->"$.type" AS CHAR(20))))');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropIndex('tariffs_active_dates_index');
            $table->dropIndex('tariffs_provider_active_index');
            
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE tariffs DROP INDEX tariffs_config_type_index');
            }
        });
    }
};
