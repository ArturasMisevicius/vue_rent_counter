<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('task_assignments')->nullOnDelete();
            $table->decimal('hours', 8, 2);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('logged_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
