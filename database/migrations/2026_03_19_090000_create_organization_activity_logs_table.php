<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('resource_type')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at'], 'organization_activity_logs_org_created_index');
            $table->index(['user_id', 'created_at'], 'organization_activity_logs_user_created_index');
            $table->index(['resource_type', 'resource_id'], 'organization_activity_logs_resource_index');
            $table->index('action', 'organization_activity_logs_action_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_activity_logs');
    }
};
