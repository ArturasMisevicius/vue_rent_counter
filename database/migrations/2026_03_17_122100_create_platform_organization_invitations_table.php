<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_organization_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('organization_name');
            $table->string('admin_email')->index();
            $table->string('plan_type');
            $table->unsignedInteger('max_properties')->default(10);
            $table->unsignedInteger('max_users')->default(5);
            $table->string('token')->unique();
            $table->string('status')->default('pending')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_organization_invitations');
    }
};
