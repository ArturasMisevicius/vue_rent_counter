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
        Schema::table('service_configurations', function (Blueprint $table) {
            // PERFORMANCE OPTIMIZATION: Add indexes for validation engine queries
            
            // Index for active configurations by utility service (validation rule loading)
            $table->index(['utility_service_id', 'is_active', 'effective_from'], 'idx_utility_active_effective');
            
            // Index for shared service queries (cost distribution calculations)
            $table->index(['is_shared_service', 'distribution_method', 'is_active'], 'idx_shared_distribution_active');
            
            // Index for pricing model queries (billing calculations)
            $table->index(['pricing_model', 'is_active'], 'idx_pricing_model_active');
            
            // Index for tariff-based queries (rate validation)
            $table->index(['tariff_id', 'effective_from', 'effective_until'], 'idx_tariff_effective_period');
            
            // Index for provider-based queries (external integration)
            $table->index(['provider_id', 'is_active'], 'idx_provider_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_configurations', function (Blueprint $table) {
            $table->dropIndex('idx_utility_active_effective');
            $table->dropIndex('idx_shared_distribution_active');
            $table->dropIndex('idx_pricing_model_active');
            $table->dropIndex('idx_tariff_effective_period');
            $table->dropIndex('idx_provider_active');
        });
    }
};