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
        Schema::create('gyvatukas_calculation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->foreignId('calculated_by_user_id')->constrained('users')->onDelete('cascade');
            
            // Calculation inputs
            $table->date('billing_month');
            $table->string('season', 10); // 'summer' or 'winter'
            
            // Calculation results
            $table->decimal('circulation_energy', 10, 2);
            $table->decimal('total_heating_energy', 10, 2)->nullable();
            $table->decimal('hot_water_volume', 10, 3)->nullable();
            $table->decimal('water_heating_energy', 10, 2)->nullable();
            
            // Distribution data
            $table->string('distribution_method', 20)->nullable(); // 'equal' or 'area'
            $table->json('distribution_result')->nullable();
            
            // Metadata for debugging and monitoring
            $table->json('calculation_metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['building_id', 'billing_month']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('calculated_by_user_id');
            $table->index('season');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gyvatukas_calculation_audits');
    }
};
