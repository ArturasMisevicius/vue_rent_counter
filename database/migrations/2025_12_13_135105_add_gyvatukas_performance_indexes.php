<?php

declare(strict_types=1);

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
        Schema::table('buildings', function (Blueprint $table) {
            // Index for tenant scoping and gyvatukas calculations
            $table->index(['tenant_id', 'total_apartments'], 'buildings_tenant_apartments_idx');
            
            // Index for summer average validity checks
            $table->index(['gyvatukas_last_calculated'], 'buildings_gyvatukas_calculated_idx');
            
            // Composite index for buildings with valid summer averages
            $table->index(['tenant_id', 'gyvatukas_summer_average', 'gyvatukas_last_calculated'], 'buildings_gyvatukas_valid_idx');
        });

        Schema::table('properties', function (Blueprint $table) {
            // Index for building property lookups (distribution calculations)
            $table->index(['building_id', 'area_sqm'], 'properties_building_area_idx');
            
            // Index for tenant scoping in property distribution
            $table->index(['tenant_id', 'building_id'], 'properties_tenant_building_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropIndex('buildings_tenant_apartments_idx');
            $table->dropIndex('buildings_gyvatukas_calculated_idx');
            $table->dropIndex('buildings_gyvatukas_valid_idx');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('properties_building_area_idx');
            $table->dropIndex('properties_tenant_building_idx');
        });
    }
};