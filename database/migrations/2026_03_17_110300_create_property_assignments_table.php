<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('unit_area_sqm', 8, 2)->nullable();
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'unassigned_at']);
            $table->index(['tenant_user_id', 'unassigned_at']);
            $table->index(['organization_id', 'tenant_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_assignments');
    }
};
