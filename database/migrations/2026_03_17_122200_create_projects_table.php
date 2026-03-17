<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('maintenance')->index();
            $table->string('status')->default('draft')->index();
            $table->string('priority')->default('medium')->index();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
