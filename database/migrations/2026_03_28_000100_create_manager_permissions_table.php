<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_permissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource', 50);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();

            $table->index('organization_id');
            $table->index('user_id');
            $table->unique(['organization_id', 'user_id', 'resource'], 'manager_permissions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_permissions');
    }
};
