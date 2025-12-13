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
            $table->json('reading_values')->nullable()->after('zone');
            $table->string('input_method')->default('manual')->after('reading_values');
            $table->string('validation_status')->default('pending')->after('input_method');
            $table->string('photo_path')->nullable()->after('validation_status');
            $table->foreignId('validated_by')->nullable()->after('photo_path')->constrained('users')->onDelete('set null');
            
            // Add indexes for performance
            $table->index('input_method');
            $table->index('validation_status');
            $table->index('validated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropIndex(['input_method']);
            $table->dropIndex(['validation_status']);
            $table->dropIndex(['validated_by']);
            $table->dropForeign(['validated_by']);
            $table->dropColumn([
                'reading_values',
                'input_method',
                'validation_status',
                'photo_path',
                'validated_by'
            ]);
        });
    }
};
