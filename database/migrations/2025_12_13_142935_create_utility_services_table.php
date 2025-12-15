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
        Schema::create('utility_services', function (Blueprint $table) {
            $table->id();
            // NOTE: tenant_id represents the owning Organization (multi-tenancy scope),
            // not the renters stored in the `tenants` table. We intentionally do not
            // constrain it to keep it consistent with the rest of the tenant-scoped schema.
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('unit_of_measurement');
            $table->string('default_pricing_model');
            $table->json('calculation_formula')->nullable();
            $table->boolean('is_global_template')->default(false);
            $table->foreignId('created_by_tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
            $table->json('configuration_schema')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('business_logic_config')->nullable();
            $table->string('service_type_bridge')->nullable(); // Bridge to existing ServiceType enum
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'is_active']);
            $table->index(['is_global_template', 'is_active']);
            $table->index('service_type_bridge');
            $table->index('default_pricing_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_services');
    }
};
