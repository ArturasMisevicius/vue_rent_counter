<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('subscription_plan')->default('starter')->index();
            $table->json('settings')->nullable();
            $table->json('resource_quotas')->nullable();
            $table->json('billing_info')->nullable();
            $table->string('primary_contact_email');
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_tenants');
    }
};
