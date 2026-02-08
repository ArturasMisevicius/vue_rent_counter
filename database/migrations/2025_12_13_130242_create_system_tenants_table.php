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
        Schema::create('system_tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable();
            $table->string('status')->default('pending');
            $table->string('subscription_plan')->default('starter');
            $table->json('settings')->nullable();
            $table->json('resource_quotas')->nullable();
            $table->json('billing_info')->nullable();
            $table->string('primary_contact_email');
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for performance
            $table->index(['status']);
            $table->index(['subscription_plan']);
            $table->index(['created_by_admin_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_tenants');
    }
};
