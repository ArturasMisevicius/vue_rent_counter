<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'manager', 'viewer', 'contributor'])->default('viewer');
            $table->json('permissions')->nullable();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('invited_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->unique(['organization_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['organization_id', 'role']);
            $table->index(['invited_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user');
    }
};