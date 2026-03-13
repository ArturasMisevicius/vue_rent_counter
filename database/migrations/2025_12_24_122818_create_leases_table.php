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
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('renter_id')->constrained('tenants')->onDelete('cascade'); // References tenants table (renters)
            $table->unsignedBigInteger('tenant_id'); // Organization scope (BelongsToTenant trait)
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rent', 10, 2);
            $table->decimal('deposit', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['property_id', 'is_active']);
            $table->index(['tenant_id', 'is_active']); // Organization scope
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
