<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('utility_service_id')->constrained()->cascadeOnDelete();
            $table->string('pricing_model');
            $table->json('rate_schedule')->nullable();
            $table->string('distribution_method');
            $table->boolean('is_shared_service')->default(false);
            $table->dateTime('effective_from');
            $table->dateTime('effective_until')->nullable();
            $table->json('configuration_overrides')->nullable();
            $table->foreignId('tariff_id')->nullable()->constrained('tariffs')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->string('area_type')->nullable();
            $table->text('custom_formula')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'property_id', 'is_active']);
            $table->index(['utility_service_id', 'is_active']);
            $table->index(['pricing_model', 'distribution_method']);
            $table->unique(
                ['property_id', 'utility_service_id', 'effective_from'],
                'service_configurations_property_service_effective_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_configurations');
    }
};
