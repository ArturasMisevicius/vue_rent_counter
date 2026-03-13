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
            $table->string('admin_email');
            $table->string('plan_type');
            $table->integer('max_properties')->default(10);
            $table->integer('max_users')->default(5);
            $table->string('token')->unique();
            $table->string('status')->default('pending'); // pending, accepted, cancelled, expired
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('status');
            $table->index('expires_at');
            $table->index('admin_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_organization_invitations');
    }
};
