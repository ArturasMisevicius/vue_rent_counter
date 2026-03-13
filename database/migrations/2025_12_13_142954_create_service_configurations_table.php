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
        Schema::create('service_configurations', function (Blueprint $table) {
            $table->id();
            // NOTE: tenant_id represents the owning Organization (multi-tenancy scope),
            // not the renters stored in the `tenants` table. We intentionally do not
            // constrain it to keep it consistent with the rest of the tenant-scoped schema.
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('utility_service_id')->constrained()->onDelete('cascade');
            $table->string('pricing_model');
            $table->json('rate_schedule')->nullable();
            $table->string('distribution_method');
            $table->boolean('is_shared_service')->default(false);
            $table->datetime('effective_from');
            $table->datetime('effective_until')->nullable();
            $table->json('configuration_overrides')->nullable();
            $table->foreignId('tariff_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('provider_id')->nullable()->constrained()->onDelete('set null');
            $table->string('area_type')->nullable(); // For area-based distribution
            $table->text('custom_formula')->nullable(); // For custom formula pricing/distribution
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'property_id', 'is_active']);
            $table->index(['utility_service_id', 'is_active']);
            $table->index(['effective_from', 'effective_until']);
            $table->index(['pricing_model', 'distribution_method']);
            $table->index('is_shared_service');

            // Unique constraint to prevent duplicate active configurations
            $table->unique(['property_id', 'utility_service_id', 'effective_from'], 'unique_property_service_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_configurations');
    }
};
