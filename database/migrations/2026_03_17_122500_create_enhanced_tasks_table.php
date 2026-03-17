<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enhanced_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('meter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_enhanced_task_id')->nullable()->constrained('enhanced_tasks')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('maintenance')->index();
            $table->string('status')->default('pending')->index();
            $table->string('priority')->default('medium')->index();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->default(0);
            $table->timestamp('due_date')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enhanced_tasks');
    }
};
