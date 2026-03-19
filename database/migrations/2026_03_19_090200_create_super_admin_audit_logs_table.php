<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('super_admin_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->foreignId('system_tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->json('changes')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('impersonation_session_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id'], 'super_admin_audit_logs_target_index');
            $table->index(['admin_id', 'created_at'], 'super_admin_audit_logs_admin_created_index');
            $table->index(['system_tenant_id', 'created_at'], 'super_admin_audit_logs_tenant_created_index');
            $table->index('action', 'super_admin_audit_logs_action_index');
            $table->index('impersonation_session_id', 'super_admin_audit_logs_impersonation_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_audit_logs');
    }
};
