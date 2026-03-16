<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('added_by')->constrained('users')->cascadeOnDelete();
            
            $table->enum('role', ['manager', 'member', 'observer', 'contractor'])->default('member');
            $table->enum('status', ['active', 'inactive', 'removed'])->default('active');
            
            $table->json('permissions')->nullable(); // Project-specific permissions
            $table->decimal('hourly_rate', 8, 2)->nullable(); // For contractors
            
            $table->datetime('joined_at');
            $table->datetime('left_at')->nullable();
            
            $table->timestamps();
            
            // Unique constraint - user can only have one role per project
            $table->unique(['project_id', 'user_id'], 'project_user_unique');
            
            // Performance indexes
            $table->index(['user_id', 'status'], 'project_members_user_idx');
            $table->index(['project_id', 'role'], 'project_members_role_idx');
            $table->index(['added_by', 'joined_at'], 'project_members_adder_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};