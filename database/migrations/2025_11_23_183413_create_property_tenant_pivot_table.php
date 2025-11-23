<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates pivot table for Property-Tenant many-to-many relationship.
     * This allows properties to have multiple tenants over time (historical tracking).
     */
    public function up(): void
    {
        Schema::create('property_tenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')
                ->constrained('properties')
                ->onDelete('cascade')
                ->comment('Property ID');
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade')
                ->comment('Tenant ID');
            $table->timestamp('assigned_at')->nullable()->comment('When tenant was assigned');
            $table->timestamp('vacated_at')->nullable()->comment('When tenant vacated');
            $table->timestamps();

            // Ensure a property can only have one active tenant at a time
            $table->unique(['property_id', 'tenant_id']);

            // Indexes for performance
            $table->index('property_id');
            $table->index('tenant_id');
            $table->index('assigned_at');
        });

        // Migrate existing data from tenants.property_id to pivot table
        if (Schema::hasColumn('tenants', 'property_id')) {
            DB::statement('
                INSERT INTO property_tenant (property_id, tenant_id, assigned_at, created_at, updated_at)
                SELECT property_id, id, created_at, created_at, updated_at
                FROM tenants
                WHERE property_id IS NOT NULL
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore data to tenants.property_id if column exists
        if (Schema::hasColumn('tenants', 'property_id')) {
            DB::statement('
                UPDATE tenants t
                INNER JOIN property_tenant pt ON t.id = pt.tenant_id
                SET t.property_id = pt.property_id
                WHERE pt.vacated_at IS NULL
            ');
        }

        Schema::dropIfExists('property_tenant');
    }
};
