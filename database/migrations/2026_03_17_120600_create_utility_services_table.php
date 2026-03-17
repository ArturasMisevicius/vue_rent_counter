<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('unit_of_measurement');
            $table->string('default_pricing_model')->index();
            $table->json('calculation_formula')->nullable();
            $table->boolean('is_global_template')->default(false);
            $table->foreignId('created_by_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->json('configuration_schema')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('business_logic_config')->nullable();
            $table->string('service_type_bridge')->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'is_active']);
            $table->index(['is_global_template', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_services');
    }
};
