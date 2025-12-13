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
            $table->json('reading_structure')->nullable()->after('supports_zones');
            $table->foreignId('service_configuration_id')->nullable()->after('reading_structure')->constrained()->onDelete('set null');
            
            // Add index for performance
            $table->index('service_configuration_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meters', function (Blueprint $table) {
            $table->dropIndex(['service_configuration_id']);
            $table->dropForeign(['service_configuration_id']);
            $table->dropColumn(['reading_structure', 'service_configuration_id']);
        });
    }
};
