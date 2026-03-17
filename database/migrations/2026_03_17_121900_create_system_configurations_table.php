<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('type')->default('string')->index();
            $table->text('description')->nullable();
            $table->string('category')->nullable()->index();
            $table->json('validation_rules')->nullable();
            $table->json('default_value')->nullable();
            $table->boolean('is_tenant_configurable')->default(false)->index();
            $table->boolean('requires_restart')->default(false)->index();
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configurations');
    }
};
