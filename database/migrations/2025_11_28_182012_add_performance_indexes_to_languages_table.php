<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PERFORMANCE: Add indexes on frequently queried columns to optimize:
     * - Language filters (is_active, is_default)
     * - Language switcher sorting (display_order)
     * - Active language queries (is_active + display_order composite)
     */
    public function up(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            // Index for filtering active languages (used in scopeActive and filters)
            $table->index('is_active', 'languages_is_active_index');
            
            // Index for filtering default language (used in business logic and filters)
            $table->index('is_default', 'languages_is_default_index');
            
            // Index for sorting by display order (used in language switcher)
            $table->index('display_order', 'languages_display_order_index');
            
            // Composite index for common query: active languages ordered by display_order
            $table->index(['is_active', 'display_order'], 'languages_active_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropIndex('languages_is_active_index');
            $table->dropIndex('languages_is_default_index');
            $table->dropIndex('languages_display_order_index');
            $table->dropIndex('languages_active_order_index');
        });
    }
};
