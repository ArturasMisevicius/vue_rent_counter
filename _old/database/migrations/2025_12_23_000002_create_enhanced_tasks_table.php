<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enhanced_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('meter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('enhanced_tasks')->nullOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['maintenance', 'reading', 'inspection', 'repair', 'installation'])->default('maintenance');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled', 'on_hold'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->default(0);
            
            $table->datetime('due_date')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            
            $table->json('metadata')->nullable(); // For flexible task-specific data
            
            $table->timestamps();
            $table->softDeletes();
            
            // Performance indexes
            $table->index(['tenant_id', 'status', 'priority'], 'tasks_tenant_status_idx');
            $table->index(['project_id', 'status'], 'tasks_project_idx');
            $table->index(['property_id', 'type'], 'tasks_property_type_idx');
            $table->index(['meter_id', 'type'], 'tasks_meter_type_idx');
            $table->index(['created_by', 'status'], 'tasks_creator_idx');
            $table->index(['parent_task_id'], 'tasks_parent_idx');
            $table->index(['due_date', 'status'], 'tasks_due_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enhanced_tasks');
    }
};