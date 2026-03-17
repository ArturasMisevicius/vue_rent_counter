<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->morphs('taggable');
            $table->foreignId('tagged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tag_id', 'taggable_type', 'taggable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};
