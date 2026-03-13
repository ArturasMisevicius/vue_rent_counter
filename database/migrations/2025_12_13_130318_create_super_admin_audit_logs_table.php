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
        Schema::create('super_admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->foreignId('system_tenant_id')->nullable()->constrained('system_tenants')->onDelete('cascade');
            $table->json('changes')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('impersonation_session_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for performance and querying
            $table->index(['admin_id']);
            $table->index(['action']);
            $table->index(['target_type', 'target_id']);
            $table->index(['system_tenant_id']);
            $table->index(['impersonation_session_id']);
            $table->index(['created_at']);
            $table->index(['admin_id', 'created_at']);
            $table->index(['system_tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin_audit_logs');
    }
};
